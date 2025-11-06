<?php
require '../config/db.php';

// db connection testing
// try {

//     $stmnt = $pdo->query("SELECT NOW() AS current_time");
//     $row = $stmnt->fetch(PDO::FETCH_ASSOC);
//     // echo $row['current_time'];
// } catch (PDOException $e) {
//     echo " Failed to fetch time using db connection " . $e->getMessage();
// }


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$sql = "SELECT e.id, e.amount, e.note, e.date, c.name AS category
        FROM expenses e
        LEFT JOIN categories c ON e.category_id = c.id
        WHERE e.user_id = :uid
        ORDER BY e.date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(array(':uid' => $_SESSION['user_id']));
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<main>
</main>

<?php include "../includes/footer.php" ?>