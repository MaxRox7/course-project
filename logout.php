<?php
session_start();

// Разрушаем сессию пользователя
session_destroy();

// Перенаправляем пользователя на страницу авторизации (login.php)
header("Location: login.php");
exit();
?>