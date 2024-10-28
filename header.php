<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">--><?php
session_start();

// Отображение email авторизованного пользователя и кнопки выхода
if (isset($_SESSION["user_id"])) {
    echo "Добро пожаловать, " . $_SESSION["user_name"];
    echo '<a href="logout.php">Выход</a>';
} else {
    // Вывод ссылки для входа, если пользователь не авторизован
    echo '<a href="login.php">Вход</a>';
}
