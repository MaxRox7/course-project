<?php
session_start(); // Начало сессии

// Подключение к базе данных
function getDatabaseConnection() {
    try {
        return new PDO("pgsql:host=localhost;dbname=course", "postgres", "1904");
    } catch (PDOException $e) {
        die("Ошибка подключения: " . $e->getMessage());
    }
}

// Обработка отправки отзыва
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_review'])) {
    $pdo = getDatabaseConnection();
    $reviewText = $_POST['text_review'];
    $rating = $_POST['rating'];
    $idBooking = $_POST['id_booking'];

    // Проверка на существующий отзыв
    $stmt_review_check = $pdo->prepare("SELECT * FROM reviews WHERE id_booking = ?");
    $stmt_review_check->execute([$idBooking]);
    $existingReview = $stmt_review_check->fetch();

    if (!$existingReview) {
        // Вставка нового отзыва
        $stmt_insert_review = $pdo->prepare("INSERT INTO reviews (id_booking, rating, text_review) VALUES (?, ?, ?)");
        if ($stmt_insert_review->execute([$idBooking, $rating, $reviewText])) {
            // Перенаправление на ту же страницу после добавления отзыва
            header("Location: index.php");
            exit();
        } else {
            echo "<div class='error-message'>Ошибка при добавлении отзыва.</div>";
        }
    } else {
        echo "<div class='error-message'>Вы уже оставили отзыв на этот заказ.</div>";
    }
}

// Если пользователь уже авторизован
if (isset($_SESSION["user"])) {
    $pdo = getDatabaseConnection();
    $user = $_SESSION["user"];

    // Отображение информации о клиенте и роли
    $stmt22 = $pdo->prepare("SELECT c.*, u.role FROM client c JOIN users u ON c.id_user = u.id_user WHERE c.id_user = :id_user");
    $stmt22->bindParam(":id_user", $user["id_user"]);
    $stmt22->execute();
    
    $role = null; // Переменная для хранения роли
    $id_client = null; // Переменная для хранения id_client

    // HTML начинается здесь
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Smart Service - Личный кабинет</title>
        <link rel="stylesheet" href="css/stil.css">
    </head>
    <body>
        <header>
            <h1>Smart Service</h1>
            <nav>
              
            
                <a href="index.php">Услуги</a>
                <a href="contact.php">Контакты</a>
                <a href="logout.php">Выход</a>
            </nav>
        </header>

        <div class="container" style="margin-top: 50px;">
            <h2>Добро пожаловать!</h2>

            <?php
            // Отображение информации о клиенте и роли
            while ($row = $stmt22->fetch(PDO::FETCH_ASSOC)) {
                echo "<h3>Ваши данные:</h3>";

                echo "<p>Имя: " . htmlspecialchars($row['fn_client']) . "</p>";
                echo "<p>Email: " . htmlspecialchars($user["email"]) . "</p>";
                echo "<p>Телефон: " . htmlspecialchars($row['number_client']) . "</p>";
                
                
                // Сохраняем роль и id_client для дальнейшей проверки
                $role = $row['role'];
                $id_client = $row['id_client'];
            }
            
            // Перенаправление на соответствующую страницу в зависимости от роли
            if ($role === 'manager') {
                header("Location: customer_manager.php");
                exit();
            } elseif ($role === 'worker') {
                header("Location: customer.php");
                exit();
            }

            // Запрос для получения заказов пользователя по id_client
            $stmt1 = $pdo->prepare("
                SELECT 
                    b.id_booking,
                    b.date_request,           
                    b.status_booking,         
                    b.booking_close_date,     
                    m.fn_managed,             
                    m.number_managed,         
                    COALESCE(SUM(ps.amount_service_provided * s.price_service::numeric), 0) AS total_service_cost,  
                    COALESCE(SUM(ud.amount_used_detail * d.price_detail::numeric), 0) AS total_used_detail_cost,  
                    COALESCE(SUM(ps.amount_service_provided * s.price_service::numeric), 0) + COALESCE(SUM(ud.amount_used_detail * d.price_detail::numeric), 0) AS total_cost,  
                    ce.name_equip,           
                    c.fn_client,              
                    c.number_client,
                    STRING_AGG(DISTINCT s.name_service, ', ') AS service_names,  
                    STRING_AGG(DISTINCT d.name_detail, ', ') AS detail_names      
                FROM public.booking b
                LEFT JOIN public.manager m ON b.id_manager = m.id_manager
                LEFT JOIN public.provided_service ps ON b.id_booking = ps.id_booking
                LEFT JOIN public.service s ON ps.id_service = s.id_service
                LEFT JOIN public.used_detail ud ON b.id_booking = ud.id_booking
                LEFT JOIN public.details d ON ud.id_detail = d.id_detail
                LEFT JOIN public.included i ON b.id_booking = i.id_booking
                LEFT JOIN public.client_equip ce ON i.id_equip = ce.id_equip
                LEFT JOIN public.client c ON ce.id_client = c.id_client
                WHERE c.id_client = :id_client
                GROUP BY 
                    b.id_booking, 
                    b.date_request, 
                    b.status_booking, 
                    b.booking_close_date, 
                    m.fn_managed, 
                    m.number_managed, 
                    ce.name_equip, 
                    c.fn_client, 
                    c.number_client
                ORDER BY b.date_request;
            ");
            $stmt1->bindParam(':id_client', $id_client); // Используем id_client для запроса
            $stmt1->execute();

            // Проверка на наличие заказов
            if ($stmt1->rowCount() > 0) {
                // Вывод результатов
                while ($row1 = $stmt1->fetch(PDO::FETCH_ASSOC)) {
                    echo "<div class='booking-block'>";
                    echo "<h3>Номер заказа: " . htmlspecialchars($row1['id_booking']) . "</h3>";
                    echo "<p>Дата открытия: " . htmlspecialchars($row1['date_request'] ?? 'Данные отсутствуют') . "</p>";
                    echo "<p>Статус бронирования: " . htmlspecialchars($row1['status_booking'] ?? 'Данные отсутствуют') . "</p>";
                    echo "<p>Дата закрытия: " . htmlspecialchars($row1['booking_close_date'] ?? 'Данные отсутствуют') . "</p>";
                    echo "<p>Имя менеджера: " . htmlspecialchars($row1['fn_managed'] ?? 'Данные отсутствуют') . " (Номер: " . htmlspecialchars($row1['number_managed'] ?? 'Данные отсутствуют') . ")</p>";
                    echo "<p>Имя клиента: " . htmlspecialchars($row1['fn_client'] ?? 'Данные отсутствуют') . " (Номер: " . htmlspecialchars($row1['number_client'] ?? 'Данные отсутствуют') . ")</p>";
                    echo "<p>Имя оборудования: " . htmlspecialchars($row1['name_equip'] ?? 'Данные отсутствуют') . "</p>";
                    echo "<h4>Сумма услуг: " . htmlspecialchars($row1['total_service_cost'] ?? '0') . " руб.</h4>";
                    echo "<h4>Сумма деталей: " . htmlspecialchars($row1['total_used_detail_cost'] ?? '0') . " руб.</h4>";
                    echo "<h4>Общая стоимость (услуги + детали): " . htmlspecialchars($row1['total_cost'] ?? '0') . " руб.</h4>";

                    // Проверка статуса заказа
                    if ($row1['status_booking'] === 'Завершено') {
                        echo "<p>Наименования услуг: " . htmlspecialchars($row1['service_names'] ?? 'Данные отсутствуют') . "</p>";
                        echo "<p>Наименования деталей: " . htmlspecialchars($row1['detail_names'] ?? 'Данные отсутствуют') . "</p>";

                        // Проверка на существующий отзыв
                        $stmt_review = $pdo->prepare("SELECT * FROM reviews WHERE id_booking = ?");
                        $stmt_review->execute([$row1['id_booking']]);
                        $review = $stmt_review->fetch();

                        if ($review) {
                            // Отображение отзыва
                            echo "<h4>Ваш отзыв:</h4>";
                            echo "<p>Рейтинг: " . htmlspecialchars($review['rating']) . " звезды</p>";
                            echo "<p>" . htmlspecialchars($review['text_review']) . "</p>";
                        } else {
                            // Форма для написания отзыва
                            echo "<form method='POST' action=''>"
                                . "<input type='hidden' name='id_booking' value='" . htmlspecialchars($row1['id_booking']) . "'>"
                                . "<textarea name='text_review' placeholder='Ваш отзыв...' required></textarea>"
                                . "<select name='rating' required>";
                            for ($i = 1; $i <= 5; $i++) {
                                echo "<option value='$i'>" . str_repeat("★", $i) . " (" . $i . " звезда" . ($i > 1 ? "ы" : "") . ")</option>";
                            }
                            echo "</select>"
                                . "<button type='submit' name='submit_review'>Написать отзыв</button>"
                                . "</form>";
                        }

                        // Кнопка для скачивания чека
                        echo "<form method='GET' action='generate_receipt.php' style='margin-top: 10px;'>
                                <input type='hidden' name='id_booking' value='" . htmlspecialchars($row1['id_booking']) . "'>
                                <button type='submit'>Скачать чек</button>
                              </form>";
                    } else {
                        echo "<p>Заказ еще не завершен.</p>";
                    }

                    echo "</div>"; // Закрытие блока заказа
                }
            } else {
                echo "<p>У вас нет заказов.</p>";
            }
            ?>
        </div>
    </body>
    </html>
<?php
} else {
    // Если пользователь не авторизован, перенаправляем на страницу входа
    header("Location: login.php");
    exit();
}
?>
