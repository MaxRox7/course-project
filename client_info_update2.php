<?php
session_start();
$pdo = new PDO("pgsql:host=localhost;dbname=course", "postgres", "1904");

// Установка режима обработки ошибок PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Обработка POST-запросов
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Проверка, какой тип действия был отправлен
    if (isset($_POST['action'])) {
        // Обработка нажатия кнопки "Приступил к работе"
        if ($_POST['action'] === 'start_work') {
            $booking_id = $_POST['booking_id'];
            $id_worker = $_POST['id_worker'];

            // Проверка, что работник выбран
            if (empty($id_worker)) {
                $error = "Ошибка: Не выбран работник.";
            } else {
                // Начало транзакции
                $pdo->beginTransaction();

                try {
                    // Обновление статуса заказа на "В процессе"
                    $stmtUpdate = $pdo->prepare("UPDATE public.booking SET status_booking = 'В процессе' WHERE id_booking = :id_booking");
                    $stmtUpdate->bindParam(':id_booking', $booking_id);
                    $stmtUpdate->execute();

                    // Получение следующего значения последовательности для id_provided_service
                    $stmtSeq = $pdo->query("SELECT nextval('public.provided_service_od_provoded_service_seq') AS next_id");
                    $next_id = $stmtSeq->fetch(PDO::FETCH_ASSOC)['next_id'];

                    // Вставка записи в provided_service
                    $stmtInsert = $pdo->prepare("INSERT INTO public.provided_service (id_provided_service, id_service, id_worker, id_booking, amount_service_provided)
                        VALUES (:id_provided_service, :id_service, :id_worker, :id_booking, :amount_service_provided)");
                    $id_service = 9; // Пример ID услуги
                    $amount_service_provided = 1; // Пример количества услуги
                    $stmtInsert->bindParam(':id_provided_service', $next_id);
                    $stmtInsert->bindParam(':id_service', $id_service);
                    $stmtInsert->bindParam(':id_worker', $id_worker);
                    $stmtInsert->bindParam(':id_booking', $booking_id);
                    $stmtInsert->bindParam(':amount_service_provided', $amount_service_provided);
                    $stmtInsert->execute();

                    // Фиксация транзакции
                    $pdo->commit();

                    // Перенаправление на ту же страницу с параметром id для обновления
                    header("Location: client_info_update.php?id=" . urlencode($_GET['id']));
                    exit();
                } catch (Exception $e) {
                    // Откат транзакции в случае ошибки
                    $pdo->rollBack();
                    $error = "Ошибка при начале работы над заказом: " . $e->getMessage();
                }
            }
        }
        // Обработка нажатия кнопки "Отменить"
        elseif ($_POST['action'] === 'cancel_order') {
            $booking_id = $_POST['booking_id'];

            // Начало транзакции
            $pdo->beginTransaction();

            try {
                // Обновление статуса заказа на "Отменен"
                $stmtUpdate = $pdo->prepare("UPDATE public.booking SET status_booking = 'Отменен' WHERE id_booking = :id_booking");
                $stmtUpdate->bindParam(':id_booking', $booking_id);
                $stmtUpdate->execute();

                // Фиксация транзакции
                $pdo->commit();

                // Перенаправление на ту же страницу с параметром id для обновления
                header("Location: client_info_update.php?id=" . urlencode($_GET['id']));
                exit();
            } catch (Exception $e) {
                // Откат транзакции в случае ошибки
                $pdo->rollBack();
                $error = "Ошибка при отмене заказа: " . $e->getMessage();
            }
        }
    }
}

// Проверка наличия параметра id в URL
if (isset($_GET['id'])) {
    $id_client = $_GET['id'] ?? null;

    // Проверяем, что id_client был передан
    if ($id_client !== null) {
        echo "<h2>Информация о заказах клиента с ID: " . htmlspecialchars($id_client) . "</h2>";
    } else {
        die("Ошибка: id_client не был передан.");
    }
} else {
    die("Ошибка: Не указан id клиента.");
}

// Запрос для получения заказов клиента с информацией о работнике и отзывах
$stmt = $pdo->prepare("
   SELECT 
       b.id_booking,
       b.date_request,           
       b.status_booking,         
       b.booking_close_date,     
       m.fn_managed,             
       m.number_managed,
       c.fn_client,
       c.number_client,
       STRING_AGG(DISTINCT w.fn_worker, ', ') AS worker_names,  -- Список имен работников
       STRING_AGG(DISTINCT r.text_review, '; ') AS text_reviews, -- Список текстов отзывов
       ROUND(AVG(r.rating)) AS avg_rating                                   -- Средний рейтинг
   FROM public.booking b
   LEFT JOIN public.manager m ON b.id_manager = m.id_manager
   LEFT JOIN public.included i ON b.id_booking = i.id_booking
   LEFT JOIN public.client_equip ce ON i.id_equip = ce.id_equip
   LEFT JOIN public.client c ON ce.id_client = c.id_client
   LEFT JOIN public.provided_service ps ON b.id_booking = ps.id_booking
   LEFT JOIN public.worker w ON ps.id_worker = w.id_worker  
   LEFT JOIN public.reviews r ON b.id_booking = r.id_booking  
   WHERE c.id_client = :id_client
   GROUP BY b.id_booking, b.date_request, b.status_booking, b.booking_close_date, m.fn_managed, m.number_managed, c.fn_client, c.number_client
   ORDER BY b.date_request;
");

// Привязка параметра
$stmt->bindParam(':id_client', $id_client);
$stmt->execute();

// Получение всех работников для использования в формах
$stmtWorkers = $pdo->prepare("SELECT id_worker, fn_worker FROM public.worker WHERE status_worker = 'Работает' ORDER BY fn_worker");
$stmtWorkers->execute();
$workers = $stmtWorkers->fetchAll(PDO::FETCH_ASSOC);

// Массив для хранения статусов заказов
$orderStatuses = [];

// Вывод результатов с кнопками
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $orderStatuses[] = $row['status_booking']; // Сохраняем статусы заказов

    echo "<div class='booking-block'>";
    echo "<h3>Номер заказа: " . htmlspecialchars($row['id_booking']) . "</h3>";
    echo "<p><strong>Дата открытия:</strong> " . htmlspecialchars($row['date_request']) . "</p>";
    echo "<p><strong>Дата закрытия:</strong> " . htmlspecialchars($row['booking_close_date']) . "</p>";
    echo "<p><strong>Имя менеджера:</strong> " . htmlspecialchars($row['fn_managed']) . " (Номер: " . htmlspecialchars($row['number_managed']) . ")</p>";
    echo "<p><strong>Имя клиента:</strong> " . htmlspecialchars($row['fn_client']) . " (Номер: " . htmlspecialchars($row['number_client']) . ")</p>";

    // Вывод информации о работниках, которые взялись за работу
    if (!empty($row['worker_names'])) {
        echo "<p><strong>Работники, которые взялись за работу:</strong> " . htmlspecialchars($row['worker_names']) . "</p>";
    } else {
        echo "<p><strong>Работники, которые взялись за работу:</strong> Не назначены</p>";
    }

    // Вывод отзыва, если он есть
    if ($row['status_booking'] === 'Завершено') {
        if (!empty($row['text_reviews'])) {
            echo "<p><strong>Отзывы:</strong> " . htmlspecialchars($row['text_reviews']) . "</p>";
            echo "<p><strong>Средний рейтинг:</strong> " . htmlspecialchars($row['avg_rating']) . "</p>";
        } else {
            echo "<p><strong>Отзывы:</strong> Отзывы отсутствуют</p>";
        }
    }

    // Отображение статуса заказа с соответствующим цветом
    if ($row['status_booking'] === 'Завершено') {
        echo "<p class='status-completed'><strong>Статус заказа:</strong> Завершено</p>";
    } elseif ($row['status_booking'] === 'В процессе') {
        echo "<p class='status-in-progress'><strong>Статус заказа:</strong> В процессе</p>";
    } elseif ($row['status_booking'] === 'Отменен') {
        echo "<p class='status-canceled'><strong>Статус заказа:</strong> Отменен</p>";
    } else {
        echo "<p class='status-pending'><strong>Статус заказа:</strong> Ожидает начала работы</p>";
    }

    // Добавление кнопки "Закрыть", если статус заказа "В процессе"
    if ($row['status_booking'] === 'В процессе') {
        echo '<form method="GET" action="close_order.php" class="close-order-form">';
        echo '<input type="hidden" name="id_booking" value="' . htmlspecialchars($row['id_booking']) . '">';
        echo '<input type="submit" class="btn-close" value="Закрыть">';
        echo '</form>';
    }

    // Проверка статуса для отображения кнопки "Приступил к работе"
    // Исключаем статус "Отменен"
    if ($row['status_booking'] !== 'В процессе' && $row['status_booking'] !== 'Завершено' && $row['status_booking'] !== 'Отменен') {
        echo '<form method="POST" action="" class="start-work-form">';
        echo '<input type="hidden" name="action" value="start_work">';
        echo '<input type="hidden" name="booking_id" value="' . htmlspecialchars($row['id_booking']) . '">';

        // Выпадающий список работников
        echo '<label for="id_worker_' . htmlspecialchars($row['id_booking']) . '">Выберите работника:</label>';
        echo '<select name="id_worker" id="id_worker_' . htmlspecialchars($row['id_booking']) . '">';
        echo '<option value="">Выберите работника</option>';
        foreach ($workers as $worker) {
            echo '<option value="' . htmlspecialchars($worker['id_worker']) . '">' . htmlspecialchars($worker['fn_worker']) . '</option>';
        }
        echo '</select>';
        
        echo '<input type="submit" class="btn-start" value="Приступил к работе">';
        echo '</form>';
    }

    // Добавление кнопки "Отменить" **только если заказ не отменен**
    if ($row['status_booking'] !== 'Отменен') {
        echo '<form method="POST" action="" class="cancel-order-form">';
        echo '<input type="hidden" name="action" value="cancel_order">';
        echo '<input type="hidden" name="booking_id" value="' . htmlspecialchars($row['id_booking']) . '">';
        echo '<input type="submit" class="btn-cancel" value="Отменить" onclick="return confirm(\'Вы уверены, что хотите отменить этот заказ?\');">';
        echo '</form>';
    }

    echo "</div>";
}

// Вывод ошибок, если они есть
if (isset($error)) {
    echo "<p class='error-message'>" . htmlspecialchars($error) . "</p>";
}

echo "<br><a href='customer_manager.php' class='btn-back'>Назад к списку клиентов</a>";
?>

<!-- CSS для стилей -->
<style>
/* Общие стили */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    color: #333;
    margin: 0;
    padding: 20px;
}

/* Заголовки */
h2 {
    color: #007BFF;
}

.booking-block {
    background-color: white;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    padding: 15px;
    margin-bottom: 20px;
}

/* Статусы заказов */
.status-completed {
    color: green;
}

.status-in-progress {
    color: orange;
}

.status-pending {
    color: red;
}

.status-canceled {
    color: gray; /* Новый стиль для отмененных заказов */
}

/* Формы */
form {
    margin-top: 10px;
    display: inline-block; /* Для расположения кнопок рядом */
}

/* Кнопки */
.btn-close,
.btn-start,
.btn-back,
.btn-cancel { /* Новый класс для кнопки "Отменить" */
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

.btn-cancel {
    background-color: #dc3545; /* Красный цвет для отмены */
}

.btn-close:hover,
.btn-start:hover,
.btn-back:hover {
    background-color: #0056b3;
}

.btn-cancel:hover {
    background-color: #c82333; /* Темно-красный при наведении */
}

/* Сообщения об ошибках */
.error-message {
    color: red;
    margin-top: 15px;
}
</style>
