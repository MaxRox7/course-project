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

    // Ваш SQL-запрос
    $sql = "
        SELECT
            r.text_review,
            r.rating,
            c.fn_client,
            c.number_client,
            b.id_booking,
            b.date_request
        FROM
            reviews r
        JOIN
            booking b ON r.id_booking = b.id_booking
        JOIN
            included i ON b.id_booking = i.id_booking
        JOIN
            client_equip ce ON i.id_equip = ce.id_equip
        JOIN
            client c ON ce.id_client = c.id_client
        ORDER BY
            b.date_request DESC
    ";

    // Выполнение запроса
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // Получение всех отзывов
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Отзывы клиентов</title>
    <link rel="stylesheet" href="css/reviews.css"> <!-- Подключение файла стилей -->
</head>
<body>

    <header>
        <h1>Smart Service</h1>
        <nav>
            <a href="login.php">Главная</a>
            <a href="index.php">Услуги</a>
            <a href="contact.php">Контакты</a>
            <a href="show_reviews.php">Отзывы</a>
        </nav>
    </header>

    <div class="container">
        <h2>Отзывы наших клиентов</h2>

        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review">
                    <p class="client-name"><?= htmlspecialchars($review['fn_client']) ?> </p>
                    <p class="review-text"><?= nl2br(htmlspecialchars($review['text_review'])) ?></p>
                    <p class="rating">
                        <?php 
                            $rating = (int)$review['rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<span class="star filled">&#9733;</span>'; // Заполненная звезда
                                } else {
                                    echo '<span class="star">&#9733;</span>'; // Пустая звезда
                                }
                            }
                        ?>
                    </p>
                    <p class="additional-info">
                        
                        Дата заказа: <?= htmlspecialchars($review['date_request']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Пока отзывов нет. Будьте первыми, кто оставит свой отзыв!</p>
        <?php endif; ?>
    </div>

</body>
</html>
