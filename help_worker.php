<?php
try {
    // Установка соединения с базой данных
    $pdo = new PDO("pgsql:host=localhost;dbname=course", "postgres", "1904");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Переменные для хранения результатов
    $clientEquipResult = [];
    $stockResult = [];

    // Запрос для отчета о технике клиента
    $queryClientEquip = "
        SELECT c.fn_client, ce.name_equip, ce.description_equip, w.fn_worker AS worker_name
        FROM client_equip ce
        JOIN client c ON ce.id_client = c.id_client
        LEFT JOIN included i ON ce.id_equip = i.id_equip
        LEFT JOIN booking b ON i.id_booking = b.id_booking
        LEFT JOIN provided_service ps ON b.id_booking = ps.id_booking
        LEFT JOIN worker w ON ps.id_worker = w.id_worker
    ";
    $stmtClientEquip = $pdo->query($queryClientEquip);
    $clientEquipResult = $stmtClientEquip->fetchAll(PDO::FETCH_ASSOC);

    // Запрос для отчета о деталях на складе
    $queryStock = "
        SELECT d.name_detail, d.amount_detail AS current_stock
        FROM details d
    ";
    $stmtStock = $pdo->query($queryStock);
    $stockResult = $stmtStock->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stil.css"> <!-- Подключение файла стилей -->
    <title>Отчеты</title>
    <style>
        .btn-close,
        .btn-start,
        .btn-back,
        .btn-cancel {
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
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
        }
        h2 {
            color: #001f3f;
            text-align: center;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            color: #001f3f;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<header>
        <h1>Smart Service</h1>
        <nav>
            
            <a href="help_worker.php">Информация</a>
            <a href="logout.php">Выход</a>
        </nav>
    </header>
<body>

<!-- Отчет о технике клиента -->
<section id="client_equipment">
    <h2>Отчет о технике клиента</h2>
    <?php if (!empty($clientEquipResult)): ?>
        <table>
            <tr>
                <th>Имя клиента</th>
                <th>Название техники</th>
                <th>Описание техники</th>
                <th>Имя работника</th>
            </tr>
            <?php foreach ($clientEquipResult as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['fn_client']); ?></td>
                    <td><?= htmlspecialchars($row['name_equip']); ?></td>
                    <td><?= htmlspecialchars($row['description_equip']); ?></td>
                    <td><?= htmlspecialchars($row['worker_name'] ?? 'Не назначен'); ?></td> <!-- Проверка на NULL -->
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p style="text-align: center;">Нет данных для отображения.</p>
    <?php endif; ?>
</section>

<br>

<!-- Отчет о деталях на складе -->
<section id="stock_report">
    <h2>Отчет о деталях на складе</h2>
    <?php if (!empty($stockResult)): ?>
        <table>
            <tr>
                <th>Наименование</th>
                <th>Количество</th>
            </tr>
            <?php foreach ($stockResult as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name_detail']); ?></td>
                    <td><?= htmlspecialchars($row['current_stock']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p style="text-align: center;">Нет данных для отображения.</p>
    <?php endif; ?>
</section>

<br>
<a href='customer.php' class='btn-back'>Назад к списку клиентов</a>

</body>
</html>
