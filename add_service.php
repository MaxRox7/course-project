<?php
// Настройки подключения к базе данных
$host = 'localhost';      // Хост (обычно 'localhost')
$db   = 'course';         // Имя базы данных
$user = 'postgres';       // Имя пользователя
$pass = '1904';           // Пароль

// Настройка DSN и опций PDO
$dsn = "pgsql:host=$host;dbname=$db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Подключение к базе данных
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Обработка формы для добавления новой услуги
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_service = $_POST['name_service'] ?? '';
    $price_service = $_POST['price_service'] ?? 0;

    // Вставка новой услуги в таблицу
    $stmt = $pdo->prepare("INSERT INTO service (name_service, price_service) VALUES (?, ?)");
    $stmt->execute([$name_service, $price_service]);
}

// Получение существующих услуг
$stmt = $pdo->query("SELECT * FROM service");
$services = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<link rel="stylesheet" href="css/stil.css"> <!-- Подключение файла стилей -->
    <meta charset="UTF-8">
    <title>Услуги</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
       
        }
        form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin: 0 auto 20px;
            max-width: 400px;
        }
        label {
            display: block;
            margin-top: 10px;
            color: #555555;
        }
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #cccccc;
            border-radius: 5px;
        }
        button {
            background-color: #001f3f; /* Цвет кнопки */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
            width: 100%;
            font-weight: bold;
        }
        button:hover {
            background-color: #0056b3; /* Светлее оттенок при наведении */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #cccccc;
        }
        th {
            background-color: #f2f2f2;
            color: #001f3f;
        }
        tr:hover {
            background-color: #f5f5f5; /* Цвет строки при наведении */
        }

        nav a:hover {
    background-color: rgba(255, 255, 255, 0.2); /* Подсветка фона при наведении */
}
.btn-back {
            display: inline-block;
            background-color: #007bff; /* Цвет кнопки */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none; /* Убираем подчеркивание */
            margin-bottom: 20px; /* Отступ снизу */
            margin-top: 40px;
        }
        .btn-back:hover {
            background-color: #0056b3; /* Светлее оттенок при наведении */
        }
    </style>
</head>
<header>
        <h1>Smart Service</h1>
        <nav>
            
            <a href="report.php">Отчеты</a>
            <a href="logout.php">Выход</a>
        </nav>
    </header>
<body>
<a href='customer_manager.php' class='btn-back'>Назад к списку клиентов</a>
    <h1>Добавить новую услугу</h1>
    <form method="post">
        <label for="name_service">Название услуги:</label>
        <input type="text" name="name_service" id="name_service" required>
        
        <label for="price_service">Цена услуги:</label>
        <input type="number" name="price_service" id="price_service" step="0.01" required>
        
        <button type="submit">Добавить</button>
    </form>

    <h1>Список услуг</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Цена</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $service): ?>
                <tr>
                    <td><?php echo htmlspecialchars($service['id_service']); ?></td>
                    <td><?php echo htmlspecialchars($service['name_service']); ?></td>
                    <td><?php echo htmlspecialchars($service['price_service']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
