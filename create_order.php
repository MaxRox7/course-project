<?php

echo "<br>" . '<a href="customer_manager.php">Назад</a>';

try {
    // Подключение к базе данных
    $dsn = 'pgsql:host=localhost;dbname=course'; 
    $username = 'postgres';
    $password = '1904';

    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Обработка данных формы, если запрос POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $client_id = $_POST['client_id'];
        $equipment = $_POST['equipment'];
        $sn = $_POST['sn'];
        $description = $_POST['description'];

        // Подготовка SQL-запроса для вставки данных
        $stmt = $pdo->prepare("INSERT INTO public.client_equip (id_client, name_equip, sn_equip, description_equip) VALUES (:id_client, :name_equip, :sn_equip, :description_equip)");

        // Привязка параметров
        $stmt->bindParam(':id_client', $client_id);
        $stmt->bindParam(':name_equip', $equipment);
        $stmt->bindParam(':sn_equip', $sn);
        $stmt->bindParam(':description_equip', $description);

        // Выполнение запроса
        if ($stmt->execute()) {
            echo "Данные успешно добавлены!";
        } else {
            echo "Ошибка при добавлении данных.";
        }
    }

    // Запрос для получения клиентов
    $stmt = $pdo->query("SELECT id_client, fn_client FROM public.client");
    
    // Создание формы с выпадающим списком и полями для заказа
    echo '<form method="POST" action="">'; // Пустой action отправляет на тот же файл
    
    echo '<label for="client_id">Выберите клиента:</label>';
    echo '<select name="client_id" id="client_id" required>';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<option value="' . htmlspecialchars($row['id_client']) . '">' . htmlspecialchars($row['fn_client']) . '</option>';
    }
    echo '</select><br>';

    echo '<label for="equipment">Техника:</label>';
    echo '<input type="text" id="equipment" name="equipment" required><br>';

    echo '<label for="sn">Серийный номер техники:</label>';
    echo '<input type="text" id="sn" name="sn" required><br>';
    
    echo '<label for="description">Описание техники:</label>';
    echo '<textarea id="description" name="description" required></textarea><br>';
    
    echo '<input type="submit" value="Создать заказ">';
    echo '</form>';

} catch (PDOException $e) {
    echo 'Ошибка подключения: ' . $e->getMessage();
}
?>
