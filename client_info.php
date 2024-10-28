<?php
session_start();
$pdo = new PDO("pgsql:host=localhost;dbname=course", "postgres", "1904");

// Проверка наличия параметра id в URL
if (isset($_GET['id'])) {
    $id_client = $_GET['id'] ?? null;

    // Проверяем, что id_client был передан
    if ($id_client !== null) {
        echo "Информация о клиенте с id_client = " . htmlspecialchars($id_client) . "<br>";
    } else {
        echo "Ошибка: id_client не был передан.";
    }
}

// Запрос для получения подробной информации о клиенте
$stmt = $pdo->prepare("
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
       c.number_client
   FROM public.booking b
   LEFT JOIN public.manager m ON b.id_manager = m.id_manager
   LEFT JOIN public.provided_service ps ON b.id_booking = ps.id_booking
   LEFT JOIN public.service s ON ps.id_service = s.id_service
   LEFT JOIN public.worker w ON ps.id_worker = w.id_worker
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

// Привязка параметра
$stmt->bindParam(':id_client', $id_client);
$stmt->execute();

// Вывод результатов
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<div class='booking-block'>";
    echo "<h3>Номер заказа: " . htmlspecialchars($row['id_booking']) . "</h3>";
    echo "<p>Дата открытия: " . htmlspecialchars($row['date_request']) . "</p>";
    echo "<p>Статус бронирования: " . htmlspecialchars($row['status_booking']) . "</p>";
    echo "<p>Дата закрытия: " . htmlspecialchars($row['booking_close_date']) . "</p>";
    echo "<p>Имя менеджера: " . htmlspecialchars($row['fn_managed']) . " (Номер: " . htmlspecialchars($row['number_managed']) . ")</p>";
    echo "<p>Имя клиента: " . htmlspecialchars($row['fn_client']) . " (Номер: " . htmlspecialchars($row['number_client']) . ")</p>";
    echo "<p>Имя оборудования: " . htmlspecialchars($row['name_equip']) . "</p>";
    echo "<h4>Сумма услуг: " . htmlspecialchars($row['total_service_cost']) . " руб.</h4>";
    echo "<h4>Сумма деталей: " . htmlspecialchars($row['total_used_detail_cost']) . " руб.</h4>";
    echo "<h4>Общая стоимость (услуги + детали): " . htmlspecialchars($row['total_cost']) . " руб.</h4>";
    echo "</div>";
}

// Вывод техники
$stmt3 = $pdo->prepare("SELECT client_equip.id_client, 
    string_agg(name_equip, ',') AS equipment_list
    FROM client_equip
    INNER JOIN client ON client_equip.id_client = client.id_client
    WHERE client.id_client = :id
    GROUP BY client_equip.id_client");
$stmt3->bindParam(":id", $id_client);
$stmt3->execute();

while ($row3 = $stmt3->fetch(PDO::FETCH_ASSOC)) {
    echo "<p>Техника клиента: " . htmlspecialchars($row3['equipment_list']) . "</p>";
}

// Получение списка менеджеров со статусом "Работает" для выпадающего списка
$stmtManagers = $pdo->prepare("SELECT id_manager, fn_managed FROM public.manager WHERE status_manager = 'Работает'");
$stmtManagers->execute();
$managers = $stmtManagers->fetchAll(PDO::FETCH_ASSOC);

// Обработка данных формы, если запрос POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Получение данных из формы
    $manager_id = $_POST['manager'];
    $equipment = $_POST['equipment'];
    $sn = $_POST['sn'];
    $description = $_POST['description'];

    // Начинаем транзакцию
    $pdo->beginTransaction();
    try {
        // Шаг 1: Создание нового заказа в таблице booking
        $stmtBooking = $pdo->prepare("INSERT INTO public.booking (id_manager, date_request, status_booking) VALUES (:id_manager, CURRENT_DATE, 'В обработке') RETURNING id_booking");
        $stmtBooking->bindParam(':id_manager', $manager_id);
        $stmtBooking->execute();
        $new_booking_id = $stmtBooking->fetchColumn();

        // Шаг 2: Добавление новой техники в client_equip
        $stmtEquip = $pdo->prepare("INSERT INTO public.client_equip (id_client, name_equip, sn_equip, description_equip) VALUES (:id_client, :name_equip, :sn_equip, :description_equip)");
        $stmtEquip->bindParam(':id_client', $id_client);
        $stmtEquip->bindParam(':name_equip', $equipment);
        $stmtEquip->bindParam(':sn_equip', $sn);
        $stmtEquip->bindParam(':description_equip', $description);
        $stmtEquip->execute();

        // Шаг 3: Получение id_equip для добавления в таблицу included
        $lastInsertId = $pdo->lastInsertId();

        // Шаг 4: Добавление записи в included
        $stmtIncluded = $pdo->prepare("INSERT INTO public.included (id_booking, id_equip) VALUES (:id_booking, :id_equip)");
        $stmtIncluded->bindParam(':id_booking', $new_booking_id);
        $stmtIncluded->bindParam(':id_equip', $lastInsertId);
        $stmtIncluded->execute();

        // Подтверждаем транзакцию
        $pdo->commit();
        
        // Перенаправление на ту же страницу после добавления данных
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . urlencode($id_client));
        exit(); // Завершаем выполнение скрипта
    } catch (Exception $e) {
        // В случае ошибки откатываем транзакцию
        $pdo->rollBack();
        echo "Ошибка при добавлении данных: " . htmlspecialchars($e->getMessage());
    }
}

// Форма для добавления новой техники и выбора менеджера
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Информация о клиенте</title>
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
        h1, h3, h4 {
            color: #007BFF;
        }

        /* Контейнеры */
        .container {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        /* Блоки заказов */
        .booking-block {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #007BFF;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        /* Кнопки */
        .button {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #0056b3;
        }

        /* Форма */
        form {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #007BFF;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        /* Поля формы */
        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        input[type="submit"] {
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Информация о клиенте</h1>

    <!-- Ваш код для вывода информации о клиенте и заказах -->

    <h2>Создание нового заказа</h2>

    <form method="POST" action="">
        <label for="manager">Выберите менеджера:</label>
        <select id="manager" name="manager" required>
            <?php foreach ($managers as $manager): ?>
                <option value="<?= htmlspecialchars($manager['id_manager']) ?>"><?= htmlspecialchars($manager['fn_managed']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="equipment">Техника:</label>
        <input type="text" id="equipment" name="equipment" required>

        <label for="sn">Серийный номер техники:</label>
        <input type="text" id="sn" name="sn" required>

        <label for="description">Описание техники:</label>
        <textarea id="description" name="description" required></textarea>

        <input type="submit" value="Создать заказ">
    </form>

    <br>
    <a class="button" href='customer_manager.php'>Назад к списку клиентов</a>
</div>

</body>
</html>
