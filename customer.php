<?php
session_start();

// Подключение к базе данных
try {
    $pdo = new PDO("pgsql:host=localhost;dbname=course", "postgres", "1904");
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Service - Работник</title>
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
        .btn-close,
.btn-start,
.btn-back,
.btn-cancel { /* Новый класс для кнопки "Отменить" */
    background-color: #007BFF;
    color: white;
    border: none;
    padding: 10px 15px;
    text-decoration: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
    margin-right: 5px; /* Отступ между кнопками */
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
           
            <a href="help_worker.php">Информация</a>
            <a href="logout.php">Выход</a>
        </nav>
    </header>
    
    <div class="container" style="margin-top: 50px;">
        <h2>Вы вошли, как работник</h2>';

$stmt = $pdo->prepare("SELECT * FROM users INNER JOIN client ON client.id_user = users.id_user");
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo "<h3>Список клиентов:</h3>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        echo '<a class="client-link" href="client_info_update.php?id=' . htmlspecialchars($row['id_client']) . '">' . htmlspecialchars($row['fn_client']) . '</a>';
    }
} else {
    echo "<div class='error-message'>Клиенты не найдены.</div>";
}

echo "<br>";



echo "<br><br><br>";
echo '</div>';

echo '<footer>
    &copy; ' . date("Y") . ' Smart Service. Все права защищены.
</footer>
</body>
</html>';
?>
