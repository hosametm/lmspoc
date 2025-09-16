<?php
header('Content-Type: application/json');
require_once './connection.php';
$query = "select AVG(precentage) as avg_progress from students_courses;";
$stmt = $pdo->query($query);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$data = ['data' => $result, 'status' => 'success', 'message' => 'Data fetched successfully'];
echo json_encode($data);
?>