<?php
session_start();
$pdo = new PDO("pgsql:host=localhost;dbname=course", "postgres", "1904");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Проверка наличия параметров в POST-запросе
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $selected_details = $_POST['details'] ?? [];
    $selected_services = $_POST['services'] ?? [];

    // Начало транзакции
    $pdo->beginTransaction();

    try {
        // Обновление статуса заказа на "Завершено"
        $stmtClose = $pdo->prepare("UPDATE public.booking SET status_booking = 'Завершено', booking_close_date = NOW() WHERE id_booking = :id_booking");
        $stmtClose->bindParam(':id_booking', $booking_id);
        $stmtClose->execute();

        // Здесь можно сохранить детали и услуги, если требуется, например:
        foreach ($selected_details as $detail_id) {
            // Здесь может быть логика для записи деталей
            // Например, если у вас есть таблица для отслеживания использованных деталей
        }

        foreach ($selected_services as $service_id) {
            // Здесь может быть логика для записи услуг
            // Например, если у вас есть таблица для отслеживания использованных услуг
        }

        // Фиксация транзакции
        $pdo->commit();

        // Перенаправление на ту же страницу с параметром id для обновления
        header("Location: client_info_update.php?id=" . urlencode($_GET['id']));
        exit();
    } catch (Exception $e) {
        // Откат транзакции в случае ошибки
        $pdo->rollBack();
        $error = "Ошибка при завершении заказа: " . $e->getMessage();
    }
} else {
    die("Ошибка: Не указаны необходимые данные.");
}
?>
