<?php
session_start();
$pdo = new PDO("pgsql:host=localhost;dbname=course", "postgres", "1904");

// Получение списка менеджеров со статусом "Работает" для выпадающего списка
$stmtManagers = $pdo->prepare("SELECT id_manager, fn_managed FROM public.manager WHERE status_manager = 'Работает'");
$stmtManagers->execute();
$managers = $stmtManagers->fetchAll(PDO::FETCH_ASSOC);

// Обработка данных формы, если запрос POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Получение id менеджера из формы
    $manager_id = $_POST['manager'];

    // Начинаем транзакцию
    $pdo->beginTransaction();
    try {
        // Обновление статуса менеджера на "Уволен"
        $stmtUpdate = $pdo->prepare("UPDATE public.manager SET status_manager = 'Уволен' WHERE id_manager = :id_manager");
        $stmtUpdate->bindParam(':id_manager', $manager_id);
        $stmtUpdate->execute();

        // Подтверждаем транзакцию
        $pdo->commit();

        // Перенаправление на ту же страницу после обновления статуса
        header("Location: " . $_SERVER['PHP_SELF']);
        exit(); // Завершаем выполнение скрипта
    } catch (Exception $e) {
        // В случае ошибки откатываем транзакцию
        $pdo->rollBack();
        echo "Ошибка при удалении менеджера: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stil.css"> <!-- Подключение файла стилей -->   
    <title>Удаление менеджера</title>
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

<div class="container">
    <h1>Удаление менеджера</h1>

    <form method="POST" action="">
        <label for="manager">Выберите менеджера для удаления:</label>
        <select id="manager" name="manager" required>
            <?php foreach ($managers as $manager): ?>
                <option value="<?= htmlspecialchars($manager['id_manager']) ?>"><?= htmlspecialchars($manager['fn_managed']) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="submit" value="Уволить менеджера">
    </form>

    <br>
    <a href='customer_manager.php' class='btn-back'>Назад к списку клиентов</a>
</div>

</body>
</html>
