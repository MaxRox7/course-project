<?php
try {
    // Установка соединения с базой данных
    $pdo = new PDO("pgsql:host=localhost;dbname=course", "postgres", "1904");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Функция для выполнения хранимой процедуры и получения данных
    function executeProcedure($pdo, $procedureName, $title, $headers) {
        try {
            // Начало транзакции
            $pdo->beginTransaction();

            // Инициализация курсора (название курсора можно генерировать динамически, если нужно)
            $cursorName = 'cursor_' . $procedureName;

            // Вызов хранимой процедуры
            $stmt = $pdo->prepare("CALL $procedureName(:cursor)");
            $stmt->bindParam(':cursor', $cursorName, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 255);
            $stmt->execute();

            // Извлечение данных из курсора
            $stmt = $pdo->query("FETCH ALL FROM \"$cursorName\";");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Закрытие курсора
            $pdo->exec("CLOSE \"$cursorName\";");

            // Фиксация транзакции
            $pdo->commit();

            // Вывод результатов
            echo "<h2>" . htmlspecialchars($title) . "</h2>";
            if ($result) {
                echo "<table>";
                echo "<tr>";
                foreach ($headers as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";
                foreach ($result as $row) {
                    echo "<tr>";
                    foreach ($row as $cell) {
                        echo "<td>" . htmlspecialchars($cell) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table><br>";
            } else {
                echo "<p>Нет данных для отображения.</p>";
            }
        } catch (PDOException $e) {
            // Откат транзакции в случае ошибки
            $pdo->rollBack();
            echo "<p>Ошибка выполнения процедуры $procedureName: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

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
     
        <a href="show_service.php">Услуги</a>
        <a href="contact.php">Контакты</a>
        <a href="show_reviews.php">Отзывы</a>
    </nav>
</header>
<body>
<h1>Отчеты</h1>
<br>
<a href='customer.php' class='btn-back'>Назад к списку клиентов</a>
<?php


// Вызов процедуры для отчета о доходе от услуг
executeProcedure(
    $pdo,
    'get_service_revenue',
    'Отчет о доходе от услуг',
    ['Название услуги', 'Общая выручка']
);

// Вызов процедуры для отчета о бронированиях по дням
executeProcedure(
    $pdo,
    'get_booking_report',
    'Отчет об общем количестве бронирований по дням',
    ['Дата запроса', 'Общее количество бронирований', 'Заказов за 30 дней']
);

// Вызов процедуры для отчета о запасах на складе
executeProcedure(
    $pdo,
    'get_stock_report',
    'Отчет об общем количестве деталей на складе',
    ['Наименование', 'Количество']
);

// Вызов процедуры для отчета о технике клиента
executeProcedure(
    $pdo,
    'get_client_equip_report',
    'Отчет о технике клиента',
    ['Имя клиента', 'Название техники', 'Описание техники', 'Работник']
);
?>

</body>
</html>
