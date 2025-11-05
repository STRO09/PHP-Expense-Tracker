<?php
require '../config/db.php';


try {

    $stmnt = $pdo->query("SELECT NOW() AS current_time");
    $row = $stmnt->fetch(PDO::FETCH_ASSOC);
    echo $row['current_time'];
} catch (PDOException $e) {
    echo " Failed to fetch time using db connection " . $e->getMessage();
}

?>