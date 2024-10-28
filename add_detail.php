<?php
$host = 'localhost';
$dbname = 'course';
$user = 'postgres';
$password = '1904';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_detail'])) {
        // Add new detail
        $name_detail = $_POST['name_detail'];
        $amount_detail = $_POST['amount_detail'];
        $price_detail = $_POST['price_detail'];

        $stmt = $pdo->prepare("INSERT INTO details (name_detail, amount_detail, price_detail) VALUES (:name_detail, :amount_detail, :price_detail)");
        $stmt->bindParam(':name_detail', $name_detail);
        $stmt->bindParam(':amount_detail', $amount_detail);
        $stmt->bindParam(':price_detail', $price_detail);
        $stmt->execute();

        echo "<p style='color: green;'>Detail added successfully.</p>";
    } elseif (isset($_POST['update_detail'])) {
        // Update existing detail
        $id_detail = $_POST['id_detail'];
        $amount_to_add = $_POST['amount_to_add'];

        $stmt = $pdo->prepare("UPDATE details SET amount_detail = amount_detail + :amount_to_add WHERE id_detail = :id_detail");
        $stmt->bindParam(':amount_to_add', $amount_to_add);
        $stmt->bindParam(':id_detail', $id_detail);
        $stmt->execute();

        echo "<p style='color: green;'>Detail updated successfully.</p>";
    }
}

// Fetch all details for the update form
$stmt = $pdo->query("SELECT id_detail, name_detail, amount_detail FROM details");
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/stil.css"> <!-- Подключение файла стилей -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление деталями</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        h1 {
            text-align: center;
          
            margin-top: 20px;
        }
        h2 {
            text-align: center; /* Центрируем заголовок */
            color: #001f3f;
            margin-bottom: 10px;
        }
        form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 500px;
        }
        label {
            display: block;
            margin-top: 10px;
            color: #555555;
        }
        input[type="text"],
        input[type="number"],
        select {
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
            display: block;
            width: 100%;
            font-weight: bold;
        }
        button:hover {
            background-color: #0056b3; /* Светлее оттенок при наведении */
        }
        p {
            text-align: center;
            color: green; /* Цвет для сообщений об успехе */
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
<body>
<header>
        <h1>Smart Service</h1>
        <nav>
           
            <a href="report.php">Отчеты</a>
            <a href="logout.php">Выход</a>
        </nav>
    </header>
<a href='customer_manager.php' class='btn-back'>Назад к списку клиентов</a>
    <h1>Управление деталями</h1>
    
    <h2>Добавить деталь</h2>
    <form method="POST" action="">
        <label for="name_detail">Наименование детали:</label>
        <input type="text" id="name_detail" name="name_detail" required>
        
        <label for="amount_detail">Количество:</label>
        <input type="number" id="amount_detail" name="amount_detail" required>
        
        <label for="price_detail">Цена:</label>
        <input type="number" step="0.01" id="price_detail" name="price_detail" required>
        
        <button type="submit" name="add_detail">Add Detail</button>
    </form>
    
    <h2>Управление существующими деталями</h2>
    <form method="POST" action="">
        <label for="id_detail">Выбрать деталь:</label>
        <select id="id_detail" name="id_detail" required>
            <option value="">--Выбрать деталь--</option>
            <?php foreach ($details as $detail): ?>
                <option value="<?= $detail['id_detail'] ?>"><?= $detail['name_detail'] ?> (Сейчас на складе: <?= $detail['amount_detail'] ?>)</option>
            <?php endforeach; ?>
        </select>
        
        <label for="amount_to_add">Сколько добавить?</label>
        <input type="number" id="amount_to_add" name="amount_to_add" required>
        
        <button type="submit" name="update_detail">Обновить</button>
    </form>
</body>
</html>
