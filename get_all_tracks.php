<?php
header('Content-Type: application/json');
require_once './connection.php';
$query = 'select p.*, count(pc.course_id) as course_count from packages p left join packages_courses pc on p.id = pc.package_id
left JOIN courses c on pc.course_id = c.id group by p.id';
$stmt = $pdo->query($query);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
$data = ['data' => $result, 'status' => 'success', 'message' => 'Data fetched successfully'];
echo json_encode($data);
?>