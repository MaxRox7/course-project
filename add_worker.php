<?php
// Начало кода PHP для обработки формы и вставки данных
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Настройки подключения к базе данных
    $host = 'localhost';
    $db = 'course';
    $user = 'postgres';
    $pass = '1904';

    try {
        // Подключение к базе данных
        $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Получение данных из формы
        $fn_worker = $_POST['fn_worker'];
        $number_worker = $_POST['number_worker'];
        $birth_worker = $_POST['birth_worker'];

        // SQL запрос для вставки данных
        $sql = "INSERT INTO public.worker (fn_worker, number_worker, birth_worker) VALUES (:fn_worker, :number_worker, :birth_worker)";

        // Подготовка и выполнение запроса
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':fn_worker', $fn_worker);
        $stmt->bindParam(':number_worker', $number_worker);
        $stmt->bindParam(':birth_worker', $birth_worker);
        
        // Выполнение запроса
        $stmt->execute();

        echo "<br>" . "<div class='success-message'>Данные успешно добавлены в таблицу worker.</div>";
    } catch (PDOException $e) {
        echo "<div class='error-message'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</div>";
    }

    // Закрытие подключения
    $pdo = null;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить Работника</title>
    <link rel="stylesheet" href="css/stil.css"> <!-- Подключение файла стилей -->
    <style>
        /* Основные стили для формы */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #2c3e50;
        }

        label {
            display: block;
            margin: 15px 0 5px;
            font-weight: bold;
            color: #34495e;
        }

        input[type="text"],
        input[type="date"],
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }

        input[type="text"]:focus,
        input[type="date"]:focus {
            border-color: #2980b9;
            outline: none;
        }

        input[type="submit"] {
            background-color: #2980b9;
            color: white;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #3498db;
        }

        /* Сообщения об успехе и ошибках */
        .success-message {
            color: green;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
        }

        .error-message {
            color: red;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
<header>
        <h1>Smart Service</h1>
        <nav>
            
            <a href="report.php">Отчеты</a>
            <a href="logout.php">Выход</a>
        </nav>
    </header>

    <div class="container" style="margin-top: 50px;">
        <h2>Добавить Работника</h2>
        <form action="add_worker.php" method="post">
            <label for="fn_worker">ФИО:</label>
            <input type="text" id="fn_worker" name="fn_worker" required>

            <label for="number_worker">Телефон Работника:</label>
            <input type="text" id="number_worker" name="number_worker" required>

            <label for="birth_worker">Дата Рождения:</label>
            <input type="date" id="birth_worker" name="birth_worker" required>

            <input class="button" type="submit" value="Добавить">
        </form>
    </div>
</body>
</html>
