<?php
// Подключение библиотеки TFPDF
require('libs/tfpdf.php');

// Подключение к базе данных
try {
    $pdo = new PDO('pgsql:host=localhost;dbname=course', 'postgres', '1904');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Ошибка подключения: ' . $e->getMessage();
    exit();
}

// Получение параметра id_booking из GET-запроса
$id_booking = $_GET['id_booking'];

// SQL-запрос для получения данных о бронировании
$sql = "
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
WHERE b.id_booking = :id_booking
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
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_booking', $id_booking, PDO::PARAM_INT);
    $stmt->execute();

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo "Booking not found.";
        exit();
    }

    // Создание PDF
    $pdf = new tFPDF();
    $pdf->AddPage();
    
    // Add a Unicode font (uses UTF-8)
    $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
    $pdf->SetFont('DejaVu','',14);
    
    // Название сервиса в правом верхнем углу
    $pdf->SetXY(150, 10); // Set position to the top right
    $pdf->Cell(0, 10, 'Smart Service', 0, 1, 'C');
    
    // Заголовок чека
    $pdf->SetXY(10, 20); // Reset position for the title
    $pdf->Cell(0, 10, 'Чек', 0, 1, 'C');
    $pdf->Ln(10);

    // Таблица заголовков
    $pdf->SetFont('DejaVu','',12);
    $pdf->Cell(40, 10, 'Поле', 1);
    $pdf->Cell(150, 10, 'Значение', 1);
    $pdf->Ln();

    // Добавление информации о бронировании в таблицу
    $pdf->Cell(40, 10, 'Номер заказа:', 1);
    $pdf->Cell(150, 10, $booking['id_booking'], 1);
    $pdf->Ln();
    
    $pdf->Cell(40, 10, 'Дата обращения:', 1);
    $pdf->Cell(150, 10, $booking['date_request'], 1);
    $pdf->Ln();
    
    $pdf->Cell(40, 10, 'Статус заказа:', 1);
    $pdf->Cell(150, 10, $booking['status_booking'], 1);
    $pdf->Ln();
    
    $pdf->Cell(40, 10, 'Дата закрытия:', 1);
    $pdf->Cell(150, 10, $booking['booking_close_date'], 1);
    $pdf->Ln();
    
    $pdf->Cell(40, 10, 'ФИО клиента:', 1);
    $pdf->Cell(150, 10, $booking['fn_client'], 1);
    $pdf->Ln();
    
    $pdf->Cell(40, 10, 'Номер клиента:', 1);
    $pdf->Cell(150, 10, $booking['number_client'], 1);
    $pdf->Ln();
    
    // Добавление информации об оборудовании
    $pdf->Cell(40, 10, 'Техника:', 1);
    $pdf->Cell(150, 10, $booking['name_equip'] ?: 'Не указано', 1);
    $pdf->Ln();

    // Добавление информации о предоставленных услугах
    $pdf->Cell(40, 10, 'Услуги:', 1);
    $pdf->MultiCell(150, 10, $booking['service_names'] ?: 'Не указано', 1);
    $pdf->Ln(5); // Add extra space after MultiCell

    // Добавление информации о деталях
    $pdf->Cell(40, 10, 'Детали:', 1);
    $pdf->MultiCell(150, 10, $booking['detail_names'] ?: 'Не указано', 1);
    $pdf->Ln(5); // Add extra space after MultiCell

    // Добавление общей стоимости
    $pdf->Cell(40, 10, 'Общая стоимость:', 1);
    $pdf->Cell(150, 10, number_format($booking['total_cost'], 2, ',', ' ') . ' ₽', 1);
    $pdf->Ln(10);

    // Опускание места для подписей ниже
    $pdf->Ln(20); // Add extra space before signatures
    $pdf->Cell(95, 10, 'Подпись клиента: _____________________', 0, 0);
    $pdf->Cell(95, 10, 'Подпись представителя сервиса: _____________________', 0, 1);
    
    // Вывод PDF
    $pdf->Output();
} catch (PDOException $e) {
    echo 'Query error: ' . $e->getMessage();
}
?>
