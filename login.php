<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Гостевой вход
    if (isset($_POST['guest'])) {
      $_SESSION["user"] = [
            'login' => 'guest',
            'role' => 'Гость'
        ];

        header("Location: /items.php");
        exit;
    }

    // Обычный вход
    $login = $_POST["login"];
    $password = $_POST["password"];

    $stmt = $pdo->prepare('SELECT * FROM "Users" WHERE login = ?');
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    // Маппинг id роли в название
$roleMap = [
    1 => 'Администратор',
    2 => 'Менеджер',
    3 => 'Пользователь',
    4 => 'Гость',
];

if ($user && $password === $user["password"]) {
    $roleId = (int) $user["id_role"]; // или как называется колонка: $user["role_id"]
    $_SESSION["user"] = [
        'login' => $user["login"],
        'role'  => $roleMap[$roleId] ?? 'Гость'
    ];
    header("Location: /items.php");
    exit;
} else {
        echo "<p class='error'>Неверный логин или пароль</p>";
    }
}
?>

<style>
    body {
        font-family: "Times New Roman", Times, serif;
        background: #f2f4f8;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    form {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        width: 300px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    input {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        outline: none;
        transition: 0.2s;
    }

    input:focus {
        border-color: #4a90e2;
        box-shadow: 0 0 0 2px rgba(74,144,226,0.2);
    }

    button {
        padding: 10px;
        background: #4a90e2;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.2s;
    }

    button:hover {
        background: #357bd8;
    }

    .error {
        color: red;
        margin-bottom: 10px;
        text-align: center;
    }
    a {
        color: #4a90e2;
        text-decoration: none;
        font-weight: bold;
        padding: 10px;
    }
.logo {
    width: 120px;
    margin: 0 auto 10px;
    display: block;
}
</style>

<form method="POST">  
    <img src="img/Logo.png" alt="Logo" class="logo">
    <p>Вход</p>
    <input name="login" placeholder="Логин">
    <input name="password" placeholder="Пароль">
    <button type="submit">Войти</button>
    <button type="submit" name="guest">Войти как гость</button>
    <a href="register.php">Зарегистрироваться</a>
</form>
