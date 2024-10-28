<?php
// Database connection parameters
$host = 'localhost';
$dbname = 'course';
$user = 'postgres';
$password = '1904';

try {
    // Create a PDO instance
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve values from the form
        $fn_manager = $_POST['fn_manager'];
        $number_manager = $_POST['number_manager'];
        $birth_manager = $_POST['birth_manager'];
        $secret_password = $_POST['secret_password'];

        // Check if the secret password is correct
        if ($secret_password === 'asu') {
            // Prepare SQL insert statement
            $sql = "INSERT INTO public.manager (fn_managed, number_managed, birth_manager) VALUES (:fn_manager, :number_manager, :birth_manager)";

            // Prepare the statement
            $stmt = $pdo->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':fn_manager', $fn_manager);
            $stmt->bindParam(':number_manager', $number_manager);
            $stmt->bindParam(':birth_manager', $birth_manager);

            // Execute the statement
            $stmt->execute();

            echo "<p style='color: green;'>Manager added successfully.</p>";
        } else {
            echo "<p style='color: red;'>Invalid secret password.</p>";
        }
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить Менеджера</title>
    <link rel="stylesheet" href="css/stil.css"> <!-- Подключение файла стилей -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #001f3f;
        }
        form {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: auto;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input[type="text"],
        input[type="date"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            width: 100%;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        p {
            text-align: center;
        }
    </style>
</head>
<header>
    <h1>Smart Service</h1>
    <nav>
        
        <a href="report.php">Отчеты</a>
    
        <a href="login.php">Выход</a>
    </nav>
</header>
<body>
    <br>
    <h2>Добавить Менеджера</h2>
    <form method="POST" action="">
        <label for="fn_manager">Полное имя:</label>
        <input type="text" name="fn_manager" id="fn_manager" required>

        <label for="number_manager">Номер менеджера:</label>
        <input type="text" name="number_manager" id="number_manager" required>

        <label for="birth_manager">Дата рождения:</label>
        <input type="date" name="birth_manager" id="birth_manager" required>

        <label for="secret_password">Секретный пароль:</label>
        <input type="password" name="secret_password" id="secret_password" required>

        <input type="submit" value="Добавить Менеджера">
    </form>
</body>
</html>
