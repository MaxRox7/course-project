<?php
session_start(); // Запуск сессии
$pdo = new PDO("pgsql:host=localhost;dbname=course", "postgres", "1904");
echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Service - Менеджер</title>
    <link rel="stylesheet" href="css/stil.css">
</head>
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    color: #333;
    margin: 0;
    padding: 0;
}

header {
    background-color: #001f3f;
    color: white;
    padding: 15px 20px;
    text-align: center;
}

nav a {
    color: white;
    margin: 0 15px;
    text-decoration: none;
    font-weight: bold;
}

nav a:hover {
    text-decoration: underline;
}

.container {
    max-width: 800px;
    margin: 20px auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

h2 {
    text-align: center;
    color: #2c3e50;
}

.client-link {
    display: block;
    padding: 10px;
    margin: 5px 0;
    background: #ecf0f1;
    border-radius: 5px;
    text-decoration: none;
    color: #2980b9;
    transition: background 0.3s;
}

.client-link:hover {
    background: #bdc3c7;
}

.button {
    display: inline-block;
    background-color: #2980b9;
    color: white;
    padding: 10px 15px;
    margin: 10px 0;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    transition: background-color 0.3s;
}

.button:hover {
    background-color: #3498db;
}
</style>
<body>
    <header>
        <h1>Smart Service</h1>
        <nav>
          
            <a href="report.php">Отчеты</a>
            <a href="logout.php">Выход</a>
        </nav>
    </header>
    
    <div class="container" style="margin-top: 20px;">
        <h2>Вы вошли, как менеджер</h2>
        
        <p>Сейчас вы можете создавать заказы</p>

  

     
        <a class="button" href="customer_update.php">Изменять</a>';
        
        // Получение данных пользователей
        $stmt = $pdo->prepare("SELECT * FROM users INNER JOIN client ON client.id_user = users.id_user");
        $stmt->execute();
        
        // Вывод списка клиентов
        echo "<h2>Список клиентов:</h2>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            echo '<a class="client-link" href="client_info.php?id='.$row['id_client'].'">'.htmlspecialchars($row['fn_client']).'</a>';
        }
        echo "<br>";
        
        echo "<a class='button' href='add_worker.php'>Добавить сотрудника</a>";
        echo "<br>";
        echo "<a class='button' href='delete_worker.php'>Уволить сотрудника</a>";
        echo "<br>";
        echo "<a class='button' href='add_detail.php'>Добавить деталь</a>";
        echo "<br>";
        echo "<a class='button' href='add_service.php'>Добавить услугу</a>";
        echo "<br>";
        echo "<a class='button' href='show_calls.php'>Оставленные заявки</a>";
        echo "<br>";
        echo "<a class='button' href='add_manager.php'>Добавить менеджера</a>";
        echo "<br>";
        echo "<a class='button' href='delete_manager.php'>Уволить менеджера</a>";
        echo "</div>";

?>
</body>
</html>
