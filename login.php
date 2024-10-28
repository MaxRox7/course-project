<?php
session_start();
// Подключение к базе данных
$pdo = new PDO("pgsql:host=localhost; dbname=course", "postgres", "1904");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["username"])) {
        // Обработка формы входа
        $login = trim($_POST["username"]);
        $password = $_POST["password"];

        // Проверка логина и пароля в базе данных
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        $stmt->execute([$login, hash('sha256', $password)]);
        $user = $stmt->fetch();

        if ($user) {
            // Успешная аутентификация
            // Обратите внимание, что session_start() уже вызван в начале
            $_SESSION["user"] = $user;
            $_SESSION["username"] = $login;
            $_SESSION["password"] = hash('sha256', $password);
            header("Location: index.php");
            exit();
        } else {
            $_SESSION["error"] = "Неверное имя пользователя или пароль.";
            header("Location: login.php");
            exit();
        }
    } elseif (isset($_POST["register"])) {
        // Обработка формы регистрации
        $login = trim($_POST["login"]);
        $email = trim($_POST["email"]);
        $name = trim($_POST["name"]);
        $phone = trim($_POST["phone"]);
        $password = $_POST["password"];
        $confirmPassword = $_POST["confirm_password"];
        $consent = isset($_POST["consent"]);

        // Валидация данных
        if (empty($login) || empty($email) || empty($password) || empty($confirmPassword) || !$consent) {
            $_SESSION["error"] = "Заполните все поля и подтвердите согласие на обработку персональных данных.";
            header("Location: login.php");
            exit();
        } elseif ($password !== $confirmPassword) {
            $_SESSION["error"] = "Пароли не совпадают.";
            header("Location: login.php");
            exit();
        } else {
            // Проверка уникальности логина и email
            $stmtLogin = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmtLogin->execute([$login]);
            $userLogin = $stmtLogin->fetch();

            $stmtEmail = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmtEmail->execute([$email]);
            $userEmail = $stmtEmail->fetch();

            if ($userLogin) {
                $_SESSION["error"] = "Пользователь с таким логином уже существует.";
                header("Location: login.php");
                exit();
            } elseif ($userEmail) {
                $_SESSION["error"] = "Пользователь с таким email уже существует.";
                header("Location: login.php");
                exit();
            } else {
                // Хеширование пароля
                $hashedPassword = hash('sha256', $password);

                // Вставка данных в таблицу users
                $stmtInsert = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:login, :email, :hashedPassword)");
                $stmtInsert->execute([
                    'login' => $login,
                    'email' => $email,
                    'hashedPassword' => $hashedPassword,
                ]);

                $id_user = $pdo->lastInsertId();

                // Вставка значений в таблицу client
                $stmtInsertClient = $pdo->prepare("INSERT INTO client (id_user, fn_client, number_client) VALUES (:id_user, :name, :phone)");
                $stmtInsertClient->execute([
                    'id_user' => $id_user,
                    'name' => $name,
                    'phone' => $phone
                ]);

                $_SESSION["success"] = "Регистрация успешна. Теперь вы можете войти в систему.";
                header("Location: login.php");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Service - Вход и Регистрация</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header>
        <h1>Smart Service</h1>
        <nav>
            <a href="login.php">Главная</a>
            <a href="show_service.php">Услуги</a>
            <a href="contact.php">Контакты</a>
            <a href="show_reviews.php">Отзывы</a>
        </nav>
    </header>

    <div class="container" style="margin-top: 100px;">
        <div class="form-container">
            <div class="tabs">
                <div class="tab active" onclick="showForm('loginForm', this)">Вход</div>
                <div class="tab" onclick="showForm('registerForm', this)">Регистрация</div>
            </div>

            <?php
            if (isset($_SESSION["error"])) {
                echo '<div id="message" class="error-message">' . htmlspecialchars($_SESSION["error"]) . '</div>';
                unset($_SESSION["error"]);
            }
            if (isset($_SESSION["success"])) {
                echo '<div id="message" class="success-message">' . htmlspecialchars($_SESSION["success"]) . '</div>';
                unset($_SESSION["success"]);
            }
            ?>

            <!-- Форма авторизации -->
            <form id="loginForm" class="active" method="post" action="login.php">
                <label for="username">Имя пользователя:</label>
                <input type="text" name="username" required>

                <label for="password">Пароль:</label>
                <input type="password" name="password" required>

                <input type="submit" value="Войти">
            </form>

            <!-- Форма регистрации -->
            <form id="registerForm" method="post" action="login.php">
                <label for="login">Логин:</label>
                <input type="text" name="login" required>

                <label for="email">Email:</label>
                <input type="email" name="email" required>

                <label for="password">Пароль:</label>
                <input type="password" name="password" required>

                <label for="confirm_password">Повторите пароль:</label>
                <input type="password" name="confirm_password" required>

                <label for="name">Имя:</label>
                <input type="text" name="name" required>

                <label for="phone">Номер телефона:</label>
                <input type="text" name="phone" required>

                <input type="checkbox" name="consent" required> Согласие на обработку персональных данных<br><br>

                <input type="submit" name="register" value="Зарегистрироваться">
            </form>
        </div>
    </div>

    <footer>
        &copy; <?php echo date("Y"); ?> Smart Service. Все права защищены.
    </footer>

    <script src="js/scripts.js"></script>
    <script>
        // Определяем, какую форму показать при загрузке страницы
        document.addEventListener('DOMContentLoaded', () => {
            <?php
            if (isset($_GET['action']) && $_GET['action'] === 'register') {
                echo "showForm('registerForm', document.querySelector('.tab:nth-child(2)'));";
            } else {
                echo "showForm('loginForm', document.querySelector('.tab:nth-child(1)'));";
            }
            ?>
        });
    </script>
</body>
</html>
