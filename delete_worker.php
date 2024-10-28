<?php
session_start();
$pdo = new PDO("pgsql:host=localhost;dbname=course", "postgres", "1904");

// Установка режима обработки ошибок PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Обработка POST-запросов
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Проверка, какой тип действия был отправлен
    if (isset($_POST['action']) && $_POST['action'] === 'delete_worker') {
        $id_worker = $_POST['id_worker'];

        // Начало транзакции
        $pdo->beginTransaction();

        try {
            // Обновление статуса работника на "Уволен"
            $stmtUpdate = $pdo->prepare("UPDATE public.worker SET status_worker = 'Уволен' WHERE id_worker = :id_worker");
            $stmtUpdate->bindParam(':id_worker', $id_worker);
            $stmtUpdate->execute();

            // Фиксация транзакции
            $pdo->commit();

            // Перенаправление на ту же страницу для обновления списка
            header("Location: delete_worker.php");
            exit();
        } catch (Exception $e) {
            // Откат транзакции в случае ошибки
            $pdo->rollBack();
            $error = "Ошибка при увольнении работника: " . $e->getMessage();
        }
    }
}

// Запрос для получения работников со статусом "Работает"
$stmtWorkers = $pdo->prepare("SELECT id_worker, fn_worker FROM public.worker WHERE status_worker = 'Работает' ORDER BY fn_worker");
$stmtWorkers->execute();
$workers = $stmtWorkers->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stil.css"> <!-- Подключение файла стилей -->   
    <title>Увольнение работника</title>
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

<h2>Увольнение работника</h2>

<?php
// Вывод ошибок, если они есть
if (isset($error)) {
    echo "<p class='error-message'>" . htmlspecialchars($error) . "</p>";
}
?>

<div class="form-container">
    <form method="POST" action="">
        <input type="hidden" name="action" value="delete_worker">
        <label for="id_worker">Выберите работника для увольнения:</label>
        <select name="id_worker" id="id_worker" required>
            <option value="">Выберите работника</option>
            <?php foreach ($workers as $worker): ?>
                <option value="<?php echo htmlspecialchars($worker['id_worker']); ?>">
                    <?php echo htmlspecialchars($worker['fn_worker']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="submit" class="btn-delete" value="Уволить" onclick="return confirm('Вы уверены, что хотите уволить этого работника?');">
    </form>
</div>

<br><a href='customer_manager.php' class='btn-back'>Назад к списку клиентов</a>

</body>
</html>
