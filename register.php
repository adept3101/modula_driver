<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $login = $_POST["login"];
    $password = $_POST["password"];
    $lastname = $_POST["lastname"] ?? '';
    $firstname = $_POST["firstname"] ?? '';
    $surname = $_POST["surname"] ?? '';

    $stmt = $pdo->prepare('INSERT INTO "Users" (lastname, firstname, surname, login, password) VALUES (?, ?, ?, ?, ?)');
    try {
        $stmt->execute([$lastname, $firstname, $surname, $login, $password]);
        echo "<p class='success'>Регистрация успешна. <a href='login.php'>Войти</a></p>";
    } catch (Exception $e) {
    echo "<p class='error'>Ошибка: " . $e->getMessage() . "</p>";
    }
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
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
        text-align: center;
        margin-bottom: 10px;
    }

    .success {
        color: green;
        text-align: center;
        margin-bottom: 10px;
    }

    a {
        color: #4a90e2;
        text-decoration: none;
        font-weight: bold;
        padding: 10px;
    }

    a:hover {
        text-decoration: underline;
    }
</style>

<form method="POST">
    <p>Регистрация</p>
    
    <input name="lastname" placeholder="Фамилия" required>
    <input name="firstname"placeholder="Имя" required>
    <input name="surname" placeholder="Отчество" required>
    <input name="login" placeholder="Логин" required>
    <input name="password" placeholder="Пароль" required>
    <button type="submit">Регистрация</button>
    <a href="login.php">Войти</a>
</form>
