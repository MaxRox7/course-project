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

    // Запрос для получения всех данных из таблицы calls с именем менеджера
    $sql = "
        SELECT c.*, m.fn_managed 
        FROM calls c
        LEFT JOIN manager m ON c.id_manager = m.id_manager
        ORDER BY c.call_date DESC"; // Сортируем по дате вызова
    $stmt = $pdo->query($sql);
    $calls = $stmt->fetchAll(PDO::FETCH_ASSOC); // Извлекаем все данные в виде ассоциативного массива

    // Запрос для получения всех менеджеров со статусом "Работает"
    $sqlManagers = "SELECT id_manager, fn_managed FROM manager WHERE status_manager = 'Работает'";
    $stmtManagers = $pdo->query($sqlManagers);
    $managers = $stmtManagers->fetchAll(PDO::FETCH_ASSOC); // Получаем список менеджеров
} catch (PDOException $e) {
    // Обработка ошибок подключения
    echo 'Ошибка подключения к базе данных: ' . htmlspecialchars($e->getMessage());
    exit();
}

// Обработка назначения менеджера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_manager'])) {
    $id_call = $_POST['id_call'];
    $id_manager = $_POST['id_manager'];

    // Обновляем вызов, назначая менеджера
    $updateSql = "UPDATE calls SET id_manager = :id_manager WHERE id_call = :id_call";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        ':id_manager' => $id_manager,
        ':id_call' => $id_call
    ]);

    // Перенаправление на ту же страницу для обновления списка вызовов
    header("Location: show_calls.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список вызовов</title>
    <link rel="stylesheet" href="css/stil.css"> <!-- Подключение файла стилей -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        table {
            width: 90%;
            margin: 40px auto;
            border-collapse: collapse;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border: 1px solid #ccc;
        }
        th {
            background-color: #001f3f;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        h1 {
            text-align: center;
            margin: 20px 0;
        
        }
        footer {
            text-align: center;
            margin: 20px 0;
            color: #555;
        }
        .assign-manager {
            display: flex;
            align-items: center;
        }
        .assign-manager select {
            margin-left: 10px;
            padding: 5px;
        }
        .assign-manager input[type="submit"] {
            margin-left: 10px;
            background-color: #001f3f;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
        }
        .assign-manager input[type="submit"]:hover {
            background-color: #0056b3; /* Светлее оттенок при наведении */
        }
        .btn-close,
        .btn-start,
        .btn-back,
        .btn-cancel {
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
    </style>
</head>
<body>

    <header>
        <h1>Smart Service</h1>
        <nav>
            <a href="index.php">Главная</a>
            <a href="report.php">Отчеты</a>
            <a href="logout.php">Выход</a>
        </nav>
    </header>

<table>
    <thead>
        <tr>
            <th>Дата вызова</th>
            <th>Номер телефона</th>
            <th>ФИО</th>
            <th>Способ контакта</th>
            <th>Статус</th>
            <th>Менеджер</th> <!-- Новая колонка для отображения менеджера -->
            <th>Назначить менеджера</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($calls): ?>
            <?php foreach ($calls as $call): ?>
                <tr>
                    <td><?php echo htmlspecialchars($call['call_date']); ?></td>
                    <td><?php echo htmlspecialchars($call['number_call']); ?></td>
                    <td><?php echo htmlspecialchars($call['fn_call']); ?></td>
                    <td><?php echo htmlspecialchars($call['way_contact']); ?></td>
                    <td>
                        <?php 
                        // Проверка, назначен ли менеджер
                        echo $call['id_manager'] ? 'Обработан' : 'Не обработан';
                        ?>
                    </td>
                    <td>
                        <?php 
                        // Вывод имени менеджера, если он назначен
                        echo $call['fn_managed'] ? htmlspecialchars($call['fn_managed']) : 'Не назначен'; 
                        ?>
                    </td>
                    <td>
                        <?php if (!$call['id_manager']): // Если менеджер не назначен ?>
                            <form method="POST" class="assign-manager">
                                <input type="hidden" name="id_call" value="<?php echo htmlspecialchars($call['id_call']); ?>">
                                <select name="id_manager" required>
                                    <option value="">Выберите менеджера</option>
                                    <?php foreach ($managers as $manager): ?>
                                        <option value="<?php echo htmlspecialchars($manager['id_manager']); ?>">
                                            <?php echo htmlspecialchars($manager['fn_managed']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="submit" name="assign_manager" value="Назначить">
                            </form>
                        <?php else: ?>
                            <span>Менеджер уже назначен</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Нет данных для отображения.</td> <!-- Изменено на 7, чтобы соответствовать количеству колонок -->
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<a href='customer_manager.php' class='btn-back'>Назад к списку клиентов</a>
<footer>
    &copy; <?php echo date("Y"); ?> Smart Service. Все права защищены.
</footer>

</body>
</html>
