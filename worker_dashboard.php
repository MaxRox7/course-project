<?php
session_start();

$pdo = new PDO("pgsql:host=localhost;dbname=course", "postgres", "1904");

if (isset($_SESSION["worker"])) {
    $worker = $_SESSION["worker"];

    // Query to get all bookings
    $stmt = $pdo->prepare("SELECT * FROM booking");
    $stmt->execute();

    echo "Worker Dashboard - All Bookings: <br>";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Booking ID: " . $row['id_booking'] . "<br>";
        // Add more details as needed
        echo "<br>";
    }

    echo '<a href="logout.php">Выход</a>';
} else {
    header("Location: login.php");
}
?>