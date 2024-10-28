<?php
// Настройки подключения к базе данных PostgreSQL
$host = 'localhost';          // Адрес сервера базы данных
$dbname = 'course';           // Имя вашей базы данных
$user = 'postgres';           // Имя пользователя PostgreSQL
$password = '1904';           // Пароль пользователя PostgreSQL

try {
    // Установка соединения с базой данных через PDO
    $dsn = "pgsql:host=$host;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Проверка, была ли отправлена форма
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Получение данных из формы
        $fn_call = htmlspecialchars(trim($_POST['fn_call']));
        $number_call = htmlspecialchars(trim($_POST['number_call']));
        $way_contact = htmlspecialchars(trim($_POST['way_contact']));
        $call_date = date('Y-m-d H:i:s'); // Автоматическое получение текущей даты и времени

        // Подготовка SQL-запроса для вставки данных
        $sql = "INSERT INTO calls (call_date, number_call, fn_call, way_contact) VALUES (:call_date, :number_call, :fn_call, :way_contact)";

        // Выполнение запроса
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':call_date' => $call_date,
            ':number_call' => $number_call,
            ':fn_call' => $fn_call,
            ':way_contact' => $way_contact
        ]);

        // Успешное добавление
        echo '<p style="color: green;">Ваши контактные данные успешно отправлены!</p>';
    }
} catch (PDOException $e) {
    // Обработка ошибок подключения
    echo 'Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage());
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Контакты</title>
    <link rel="stylesheet" href="css/stil.css"> <!-- Подключение файла стилей -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .container {
            display: flex; /* Используем flexbox для выравнивания элементов */
            justify-content: center; /* Центрируем содержимое */
            align-items: flex-start; /* Выравнивание по верхнему краю */
            width: 90%;
            max-width: 1000px; /* Увеличиваем ширину контейнера для слогана и формы */
            margin: 40px auto;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .slogan {
            flex: 1; /* Позволяем слогану занимать оставшееся пространство */
            padding: 20px;
            font-size: 1.5em; /* Увеличиваем размер шрифта */
            color: #001f3f; /* Цвет текста слогана */
            font-weight: bold; /* Увеличиваем жирность текста */
            border-right: 2px solid #ccc; /* Разделительная линия */
            text-align: center; /* Центрируем текст */
            margin-right: 20px; /* Отступ справа */
        }
        .form-container {
            flex: 2; /* Форме даём больше места */
            padding: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            margin-top: 10px;
            display: block;
            color: #555555;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #cccccc;
            border-radius: 5px;
        }
        .radio-group {
            margin-top: 10px;
        }
        input[type="radio"] {
            margin-right: 5px;
        }
        input[type="submit"] {
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
        input[type="submit"]:hover {
            background-color: #0056b3; /* Светлее оттенок при наведении */
        }
        header {
            background-color: #001f3f; /* Цвет шапки */
            color: white;
            padding: 15px 20px;
            text-align: center;
        }
        nav a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }
        nav a:hover {
    background-color: rgba(255, 255, 255, 0.2); /* Подсветка фона при наведении */
}
        .image-container {
            text-align: center; /* Центрируем изображение */
            margin-top: 20px; /* Отступ сверху */
        }
        .image-container img {
            max-width: 100%; /* Адаптивная ширина изображения */
            height: auto; /* Автоматическая высота */
            border-radius: 10px; /* Скругленные углы изображения */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Тень для изображения */
        }
    </style>
</head>
<body>

<header>
    <h1>Smart Service</h1>
    <nav>
        <a href="login.php">Главная</a>
        <a href="show_service.php">Услуги</a>
        <a href="contact.php">Контакты</a>
        <a href="show_reviews.php">Отзывы</a>
    </nav>
</header>

<div class="container">
    <div class="slogan">
        Получи диагностику уже сегодня!
        <div class="image-container">
    <img src="uploads/diag.png" alt="Сервисный центр">
</div>
    </div>
    <div class="form-container">
        <h1>Контактная информация</h1>
        <form method="POST" action="">
            <label for="fn_call">ФИО:</label>
            <input type="text" id="fn_call" name="fn_call" required>

            <label for="number_call">Номер телефона:</label>
            <input type="text" id="number_call" name="number_call" required>

            <label>Способ контакта:</label>
            <div class="radio-group">
                <label><input type="radio" name="way_contact" value="WhatsApp" required> WhatsApp</label>
                <label><input type="radio" name="way_contact" value="Telegram" required> Telegram</label>
                <label><input type="radio" name="way_contact" value="Звонок" required> Звонок</label>
            </div>

            <input type="submit" value="Отправить">
        </form>
    </div>
</div>

<!-- Контейнер для изображения -->
<footer>
        &copy; <?php echo date("Y"); ?> Smart Service. Все права защищены.
    </footer>

</body>
</html>
