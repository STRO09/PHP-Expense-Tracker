<?php
require '../config/db.php';
session_start();

// db connection testing
// try {

//     $stmnt = $pdo->query("SELECT NOW() AS current_time");
//     $row = $stmnt->fetch(PDO::FETCH_ASSOC);
//     // echo $row['current_time'];
// } catch (PDOException $e) {
//     echo " Failed to fetch time using db connection " . $e->getMessage();
// }


if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

?>

<?php include '../includes/header.php'; ?>
<main>
</main>

<?php include "../includes/footer.php" ?>