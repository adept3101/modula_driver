<?php
session_start();
require 'db.php';

$username = $_SESSION['user']['login'] ?? 'Гость';
$role     = $_SESSION['user']['role']  ?? 'Гость';

// Права по ролям
$canView   = true; // все
$canSearch = in_array($role, ['Менеджер', 'Администратор']);
$canAdd    = $role === 'Администратор';
$canDelete = $role === 'Администратор';

// --- Выход ---
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// --- AJAX: поиск + сортировка ---
if (isset($_GET['ajax'])) {
    if (!$canSearch) {
        http_response_code(403);
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');

    $allowed_cols = ['id_point', 'index', 'city', 'street', 'house'];
    $allowed_dirs = ['asc', 'desc'];

    $search   = trim($_GET['search'] ?? '');
    $city     = trim($_GET['city']   ?? '');
    $sort_col = in_array($_GET['sort'] ?? '', $allowed_cols) ? $_GET['sort'] : 'id_point';
    $sort_dir = in_array(strtolower($_GET['dir'] ?? ''), $allowed_dirs) ? strtolower($_GET['dir']) : 'desc';

    $params = [];
    $where  = [];

    if ($search !== '') {
        $where[]          = "(CAST(id_point AS TEXT) ILIKE :search
                              OR \"index\" ILIKE :search
                              OR city     ILIKE :search
                              OR street   ILIKE :search
                              OR house    ILIKE :search)";
        $params['search'] = '%' . $search . '%';
    }
    if ($city !== '') {
        $where[]        = 'city = :city';
        $params['city'] = $city;
    }

    $sql = 'SELECT * FROM points';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= " ORDER BY {$sort_col} {$sort_dir}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $points = $stmt->fetchAll();

    foreach ($points as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['id_point']) ?></td>
            <td><?= htmlspecialchars($p['index'])    ?></td>
            <td><?= htmlspecialchars($p['city'])     ?></td>
            <td><?= htmlspecialchars($p['street'])   ?></td>
            <td><?= htmlspecialchars($p['house'])    ?></td>
            <?php if ($canDelete): ?>
            <td>
                <button class="btn-delete"
                        onclick="deletePoint(<?= (int)$p['id_point'] ?>)">
                    Удалить
                </button>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach;
    exit;
}

// --- AJAX: удаление ---
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    header('Content-Type: application/json');
    if (!$canDelete) {
        echo json_encode(['success' => false, 'error' => 'Нет доступа']);
        exit;
    }
    $id   = (int) ($_POST['id_point'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM points WHERE id_point = :id');
    $stmt->execute(['id' => $id]);
    echo json_encode(['success' => true]);
    exit;
}

// --- Добавление ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!$canAdd) {
        header('Location: items.php');
        exit;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO points (id_point, "index", city, street, house)
         VALUES (:id_point, :index, :city, :street, :house)'
    );
    $stmt->execute([
        'id_point' => $_POST['id_point'],
        'index'    => $_POST['index'],
        'city'     => $_POST['city'],
        'street'   => $_POST['street'],
        'house'    => $_POST['house'],
    ]);
    header('Location: items.php');
    exit;
}

// --- Данные для первоначальной отрисовки ---
$stmt = $pdo->prepare("SELECT * FROM points ORDER BY id_point DESC");
$stmt->execute();
$points = $stmt->fetchAll();

$cities = $canSearch
    ? $pdo->query("SELECT DISTINCT city FROM points ORDER BY city")->fetchAll()
    : [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Пункты</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 40px; }

        .container {
            max-width: 1100px; margin: auto; background: white;
            padding: 30px; border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        h1 { margin-bottom: 20px; }

        form.add-form {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 30px;
        }

        input {
            padding: 10px; border: 1px solid #ccc;
            border-radius: 6px; width: 100%; box-sizing: border-box;
        }

        button.add-btn {
            grid-column: span 5; padding: 12px; border: none;
            background: #2563eb; color: white; border-radius: 6px;
            cursor: pointer; font-size: 16px;
        }
        button.add-btn:hover { background: #1d4ed8; }

        .search-block { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
        .search-block input  { flex: 1; min-width: 160px; }
        .search-block select {
            padding: 10px; border: 1px solid #ccc; border-radius: 6px;
            background: white; min-width: 140px;
        }

        table { width: 100%; border-collapse: collapse; }
        table th, table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        table th {
            background: #f1f1f1; user-select: none; white-space: nowrap;
        }
        table th.sortable {
            cursor: pointer;
        }
        table th.sortable:hover { background: #e4e4e4; }
        th .sort-arrow { margin-left: 4px; color: #888; }
        tr:nth-child(even) { background: #fafafa; }

        .btn-delete {
            padding: 5px 10px; background: #ef4444; color: white;
            border: none; border-radius: 5px; cursor: pointer; font-size: 13px;
        }
        .btn-delete:hover { background: #dc2626; }

        .role-badge {
            font-size: 11px; padding: 2px 8px; border-radius: 10px;
            font-weight: bold; margin-left: 6px;
        }
        .role-Администратор { background: #fef3c7; color: #92400e; }
        .role-Менеджер      { background: #dbeafe; color: #1e40af; }
        .role-Пользователь  { background: #d1fae5; color: #065f46; }
        .role-Гость         { background: #f3f4f6; color: #6b7280; }

        .top-bar {
            position: fixed; top: 20px; right: 20px;
            display: inline-flex; align-items: center; gap: 10px;
            background: #fff; padding: 8px 10px 8px 14px;
            border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .avatar {
            width: 32px; height: 32px; border-radius: 8px;
            background: rgba(74,144,226,0.12); display: flex;
            align-items: center; justify-content: center;
            font-size: 13px; font-weight: bold; color: #4a90e2;
        }
        .username { font-size: 14px; font-weight: bold; color: #333; }
        .logout-btn {
            padding: 8px 14px; background: #4a90e2; color: white;
            border: none; border-radius: 8px; cursor: pointer;
            font-weight: bold; font-size: 14px; transition: 0.2s;
        }
        .logout-btn:hover { background: #357bd8; }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="avatar">
        <?= mb_strtoupper(mb_substr($username, 0, 1, 'UTF-8'), 'UTF-8') ?>
    </div>
    <span class="username">
        <?= htmlspecialchars($username) ?>
        <span class="role-badge role-<?= htmlspecialchars($role) ?>">
            <?= htmlspecialchars($role) ?>
        </span>
    </span>
    <form method="POST" style="margin:0">
        <input type="hidden" name="action" value="logout">
        <button type="submit" class="logout-btn">Выйти</button>
    </form>
</div>

<div class="container">

<?php if ($canAdd): ?>
    <h1>Добавление адресов</h1>
    <form class="add-form" method="POST">
        <input type="hidden" name="action" value="add">
        <input type="text" name="id_point" placeholder="ID"     required>
        <input type="text" name="index"    placeholder="Индекс" required>
        <input type="text" name="city"     placeholder="Город"  required>
        <input type="text" name="street"   placeholder="Улица"  required>
        <input type="text" name="house"    placeholder="Дом"    required>
        <button type="submit" class="add-btn">Добавить</button>
    </form>
<?php endif; ?>

<?php if ($canSearch): ?>
<div class="search-block">
    <input type="text" id="search" placeholder="Поиск...">
    <select id="filter">
        <option value="">Все города</option>
        <?php foreach ($cities as $c): ?>
            <option value="<?= htmlspecialchars($c['city']) ?>">
                <?= htmlspecialchars($c['city']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<?php endif; ?>

<table>
    <thead>
    <tr>
        <th <?= $canSearch ? 'data-col="id_point" class="sortable"' : '' ?>>
            ID <span class="sort-arrow" id="arrow-id_point"><?= $canSearch ? '↕' : '' ?></span>
        </th>
        <th <?= $canSearch ? 'data-col="index" class="sortable"' : '' ?>>
            Индекс <span class="sort-arrow" id="arrow-index"><?= $canSearch ? '↕' : '' ?></span>
        </th>
        <th <?= $canSearch ? 'data-col="city" class="sortable"' : '' ?>>
            Город <span class="sort-arrow" id="arrow-city"><?= $canSearch ? '↕' : '' ?></span>
        </th>
        <th <?= $canSearch ? 'data-col="street" class="sortable"' : '' ?>>
            Улица <span class="sort-arrow" id="arrow-street"><?= $canSearch ? '↕' : '' ?></span>
        </th>
        <th <?= $canSearch ? 'data-col="house" class="sortable"' : '' ?>>
            Дом <span class="sort-arrow" id="arrow-house"><?= $canSearch ? '↕' : '' ?></span>
        </th>
        <?php if ($canDelete): ?><th>Действия</th><?php endif; ?>
    </tr>
    </thead>
    <tbody id="tableBody">
    <?php foreach ($points as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['id_point']) ?></td>
            <td><?= htmlspecialchars($p['index'])    ?></td>
            <td><?= htmlspecialchars($p['city'])     ?></td>
            <td><?= htmlspecialchars($p['street'])   ?></td>
            <td><?= htmlspecialchars($p['house'])    ?></td>
            <?php if ($canDelete): ?>
            <td>
                <button class="btn-delete"
                        onclick="deletePoint(<?= (int)$p['id_point'] ?>)">
                    Удалить
                </button>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</div>

<?php if ($canSearch): ?>
<script>
const searchInput  = document.getElementById('search');
const filterSelect = document.getElementById('filter');

let sortCol = 'id_point';
let sortDir = 'desc';

document.querySelectorAll('th[data-col]').forEach(th => {
    th.addEventListener('click', () => {
        const col = th.dataset.col;
        if (sortCol === col) {
            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            sortCol = col;
            sortDir = 'asc';
        }
        updateArrows();
        loadData();
    });
});

function updateArrows() {
    document.querySelectorAll('th[data-col]').forEach(th => {
        const col   = th.dataset.col;
        const arrow = document.getElementById('arrow-' + col);
        if (!arrow) return;
        if (col === sortCol) {
            arrow.textContent = sortDir === 'asc' ? '↑' : '↓';
            arrow.style.color = '#2563eb';
        } else {
            arrow.textContent = '↕';
            arrow.style.color = '#888';
        }
    });
}

function loadData() {
    const search = encodeURIComponent(searchInput.value);
    const city   = encodeURIComponent(filterSelect.value);
    const url    = `items.php?ajax=1&search=${search}&city=${city}&sort=${sortCol}&dir=${sortDir}`;

    fetch(url)
        .then(r => r.text())
        .then(html => { document.getElementById('tableBody').innerHTML = html; });
}

searchInput.addEventListener('keyup', loadData);
filterSelect.addEventListener('change', loadData);

<?php if ($canDelete): ?>
function deletePoint(id) {
    if (!confirm('Удалить запись #' + id + '?')) return;
    const body = new URLSearchParams({ action: 'delete', id_point: id });
    fetch('items.php', { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                loadData();
            } else {
                alert('Ошибка: ' + (data.error || 'неизвестная'));
            }
        });
}
<?php endif; ?>

updateArrows();
</script>
<?php endif; ?>

</body>
</html>
