<?php
// Настройки подключения к базе данных PostgreSQL
$host = 'localhost';          // Адрес сервера базы данных
$dbname = 'course';   // Имя вашей базы данных
$user = 'postgres';           // Имя пользователя PostgreSQL
$password = '1904';  // Пароль пользователя PostgreSQL

try {
    // Установка соединения с базой данных через PDO
    $dsn = "pgsql:host=$host;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Ваш SQL-запрос для получения услуг
    $sql = "SELECT * FROM service ORDER BY name_service";

    // Выполнение запроса
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // Получение всех услуг
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Обработка ошибок подключения
    echo 'Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage());
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<link rel="stylesheet" href="css/styles.css">
    <meta charset="UTF-8">
    <title>Услуги сервисного центра</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        h1 {
            text-align: center;
            
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dddddd;
        }
        th {
            background-color: #f7f7f7;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .service-name {
            font-weight: bold;
        }
        @media (max-width: 600px) {
            .container {
                width: 95%;
                padding: 15px;
            }
            th, td {
                font-size: 0.9em;
                padding: 10px;
            }
        }
    </style>
</head>
<header>
        <h1>Smart Service</h1>
        <nav>
            <a href="login.php">Главная</a>
            <a href="show_service.php">Услуги</a>
            <a href="contact.php">Контакты</a>
            <a href="show_reviews.php">Отзывы</a>
        </nav>
    </header>
<body>

<div class="container">
    <h1>Перечень услуг</h1>

    <?php if (!empty($services)): ?>
        <table>
            <thead>
                <tr>
                    <th class="service-name">Название услуги</th>
                    <th>Описание</th>
                    <th>Цена</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?= htmlspecialchars($service['name_service']) ?></td>
                        <td><?= htmlspecialchars($service['description_service']) ?></td>
                        <td><?= htmlspecialchars($service['price_service']) ?> ₽</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Услуги не найдены.</p>
    <?php endif; ?>
</div>
<footer>
        &copy; <?php echo date("Y"); ?> Smart Service. Все права защищены.
    </footer>
</body>
</html>