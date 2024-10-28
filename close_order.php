<?php
// Подключение к базе данных
try {
    $pdo = new PDO('pgsql:host=localhost;dbname=course', 'postgres', '1904');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Ошибка подключения: " . $e->getMessage();
    exit;
}

// Проверяем, был ли передан параметр id_booking
if (isset($_GET['id_booking'])) {
    $id_booking = $_GET['id_booking'];

    // Проверяем, является ли id_booking числом
    if (is_numeric($id_booking)) {
        $id_booking = (int)$id_booking; // Приводим к типу int для безопасности

        // Получаем данные для выбора
        $details_stmt = $pdo->query("SELECT id_detail, name_detail FROM public.details");
        $details = $details_stmt->fetchAll(PDO::FETCH_ASSOC);

        $services_stmt = $pdo->query("SELECT id_service, name_service FROM public.service");
        $services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Получаем работника, связанного с заказом
        $worker_stmt = $pdo->prepare("
            SELECT w.id_worker, w.fn_worker 
            FROM public.provided_service ps 
            JOIN public.worker w ON ps.id_worker = w.id_worker 
            WHERE ps.id_booking = :id_booking
        ");
        $worker_stmt->bindParam(':id_booking', $id_booking, PDO::PARAM_INT);
        $worker_stmt->execute();
        $worker = $worker_stmt->fetch(PDO::FETCH_ASSOC);

        // Проверяем, была ли отправлена форма
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $used_details = $_POST['used_detail'] ?? [];
            $provided_services = $_POST['provided_service'] ?? [];
            $amount_used_detail = $_POST['amount_used_detail'] ?? []; // Получаем количество деталей
            $worker_id = $worker['id_worker']; // Используем работника, который уже связан с заказом

            // Обработка потраченных деталей
            foreach ($used_details as $detail_id) {
                $amount = $amount_used_detail[$detail_id] ?? 1; // Получаем количество из массива
                $insert_used_detail_stmt = $pdo->prepare("
                    INSERT INTO public.used_detail (id_detail, id_booking, amount_used_detail) 
                    VALUES (:id_detail, :id_booking, :amount_used_detail)
                ");
                $insert_used_detail_stmt->bindParam(':id_detail', $detail_id, PDO::PARAM_INT);
                $insert_used_detail_stmt->bindParam(':id_booking', $id_booking, PDO::PARAM_INT);
                $insert_used_detail_stmt->bindParam(':amount_used_detail', $amount, PDO::PARAM_INT);
                $insert_used_detail_stmt->execute();
            }

            // Обработка оказанных услуг
            foreach ($provided_services as $service_id) {
                $insert_provided_service_stmt = $pdo->prepare("
                    INSERT INTO public.provided_service (id_service, id_booking, id_worker, amount_service_provided) 
                    VALUES (:id_service, :id_booking, :id_worker, 1)  -- Здесь можно изменить количество, если нужно
                ");
                $insert_provided_service_stmt->bindParam(':id_service', $service_id, PDO::PARAM_INT);
                $insert_provided_service_stmt->bindParam(':id_booking', $id_booking, PDO::PARAM_INT);
                $insert_provided_service_stmt->bindParam(':id_worker', $worker_id, PDO::PARAM_INT);
                $insert_provided_service_stmt->execute();
            }

            // Обновляем статус заказа (например, "Закрыт")
            $update_status_stmt = $pdo->prepare("
                UPDATE public.booking 
                SET status_booking = 'Завершено',
                booking_close_date = CURRENT_DATE 
                WHERE id_booking = :id_booking
            ");
            $update_status_stmt->bindParam(':id_booking', $id_booking, PDO::PARAM_INT);
            $update_status_stmt->execute();

            // Перенаправляем на customer.php
            header("Location: customer.php");
            exit(); // Завершаем выполнение скрипта
        }
        ?>

        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <title>Закрытие заказа</title>
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
                h1 {
                    color: #007BFF;
                }

                h2 {
                    margin-top: 20px;
                    color: #007BFF;
                }

                /* Контейнеры */
                .container {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 20px;
                }

                .list {
                    width: 45%;
                }

                .selected-list {
                    border: 1px solid #000;
                    min-height: 200px;
                    padding: 5px;
                    border-radius: 5px;
                    background-color: #fff;
                }

                /* Кнопки */
                .button {
                    margin-top: 10px;
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

                /* Кнопка для закрытия окна */
                .close-button {
                    background-color: #dc3545;
                }

                .close-button:hover {
                    background-color: #c82333;
                }

                /* Сообщения об ошибках */
                .error-message {
                    color: red;
                    margin-top: 15px;
                }
            </style>
            <script>
                function moveSelectedItems(sourceSelect, targetSelect) {
                    const selectedOptions = Array.from(sourceSelect.selectedOptions);
                    selectedOptions.forEach(option => {
                        const detailId = option.value;
                        const detailName = option.text;

                        // Создаем новый элемент <div> для отображения названия детали и поля для ввода количества
                        const optionElement = document.createElement('div');
                        optionElement.innerHTML = `
                            ${detailName} 
                            <input type="number" name="amount_used_detail[${detailId}]" value="1" min="1" style="width: 60px;">
                        `;

                        targetSelect.appendChild(optionElement);
                        sourceSelect.remove(option.index);
                    });
                }

                function moveAllItems(sourceSelect, targetSelect) {
                    const allOptions = Array.from(sourceSelect.options);
                    allOptions.forEach(option => {
                        const detailId = option.value;
                        const detailName = option.text;

                        const optionElement = document.createElement('div');
                        optionElement.innerHTML = `
                            ${detailName} 
                            <input type="number" name="amount_used_detail[${detailId}]" value="1" min="1" style="width: 60px;">
                        `;

                        targetSelect.appendChild(optionElement);
                    });
                    sourceSelect.innerHTML = ""; // Очищаем исходный список
                }

                function moveSelectedServiceItems(sourceSelect, targetSelect) {
                    const selectedOptions = Array.from(sourceSelect.selectedOptions);
                    selectedOptions.forEach(option => {
                        const serviceId = option.value;
                        const serviceName = option.text;

                        // Создаем новый элемент <div> для отображения названия услуги
                        const optionElement = document.createElement('div');
                        optionElement.innerHTML = `
                            ${serviceName} 
                            <input type="number" name="provided_service[${serviceId}]" value="1" min="1" style="width: 60px;">
                        `;

                        targetSelect.appendChild(optionElement);
                        sourceSelect.remove(option.index);
                    });
                }

                function moveAllServiceItems(sourceSelect, targetSelect) {
                    const allOptions = Array.from(sourceSelect.options);
                    allOptions.forEach(option => {
                        const serviceId = option.value;
                        const serviceName = option.text;

                        const optionElement = document.createElement('div');
                        optionElement.innerHTML = `
                            ${serviceName} 
                            <input type="number" name="provided_service[${serviceId}]" value="1" min="1" style="width: 60px;">
                        `;

                        targetSelect.appendChild(optionElement);
                    });
                    sourceSelect.innerHTML = ""; // Очищаем исходный список
                }
            </script>
        </head>
        <body>
            <h1>Закрытие заказа №<?php echo htmlspecialchars($id_booking); ?></h1>

            <form action="close_order.php?id_booking=<?php echo htmlspecialchars($id_booking); ?>" method="POST">
                <h2>Потраченные детали:</h2>
                <div class="container">
                    <div class="list">
                        <select id="details" multiple>
                            <?php foreach ($details as $detail): ?>
                                <option value="<?php echo htmlspecialchars($detail['id_detail']); ?>">
                                    <?php echo htmlspecialchars($detail['name_detail']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="moveSelectedItems(details, selectedDetails)">→</button>
                        <button type="button" onclick="moveAllItems(details, selectedDetails)">→ Все</button>
                    </div>
                    <div class="list">
                        <h3>Выбранные детали:</h3>
                        <div id="selectedDetails" class="selected-list"></div>
                        <button type="button" onclick="moveSelectedItems(selectedDetails, details)">←</button>
                        <button type="button" onclick="moveAllItems(selectedDetails, details)">← Все</button>
                    </div>
                </div>

                <h2>Оказанные услуги:</h2>
                <div class="container">
                    <div class="list">
                        <select id="services" multiple>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo htmlspecialchars($service['id_service']); ?>">
                                    <?php echo htmlspecialchars($service['name_service']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="moveSelectedServiceItems(services, selectedServices)">→</button>
                        <button type="button" onclick="moveAllServiceItems(services, selectedServices)">→ Все</button>
                    </div>
                    <div class="list">
                        <h3>Выбранные услуги:</h3>
                        <div id="selectedServices" class="selected-list"></div>
                        <button type="button" onclick="moveSelectedServiceItems(selectedServices, services)">←</button>
                        <button type="button" onclick="moveAllServiceItems(selectedServices, services)">← Все</button>
                    </div>
                </div>

                <h2>Работник:</h2>
                <p>
                    <?php if ($worker): ?>
                        <strong><?php echo htmlspecialchars($worker['fn_worker']); ?></strong>
                    <?php else: ?>
                        <strong>Работник не найден</strong>
                    <?php endif; ?>
                </p>

                <input type="hidden" name="id_booking" value="<?php echo htmlspecialchars($id_booking); ?>">
                <input type="submit" value="Закрыть заказ" class="button">
            </form>

            <br>
            <a href="customer.php" class="button btn-back">Назад к списку клиентов</a>
        </body>
        </html>
        <?php
    } else {
        echo "<p class='error-message'>Некорректный ID заказа.</p>";
        exit; // Завершаем выполнение скрипта при ошибке
    }
} else {
    echo "<p class='error-message'>Параметр id_booking не был передан.</p>";
    exit; // Завершаем выполнение скрипта при отсутствии параметра
}
?>
