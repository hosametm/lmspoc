<?php
header('Content-Type: application/json');
require_once './connection.php';
session_start();
$json = file_get_contents("php://input");
$_POST = json_decode($json, true);
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $data = ['data' => [], 'status' => 'error', 'message' => 'Invalid request method'];
    echo json_encode($data);
    exit();
}
$allowedParams = json_decode('[{"name":"user_id","type":"integer","required":"1"},{"name":"track_id","type":"integer","required":"1"}]', true);
$missingParams = [];
$requestData = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
}
$requestData = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS);


foreach ($allowedParams as $param) {
    if (!isset($requestData[$param['name']]) && $param['required']) {
        $missingParams[] = $param['name'];
    }
}

if (!empty($missingParams)) {
    $data = ['data' => [], 'status' => 'error', 'message' => 'Parameters ' . implode(', ', $missingParams) . ' are required'];
    echo json_encode($data);
    exit();
}

$query = 'SELECT 
            sc.course_id,
            c.title AS course_name,
            sc.watched ,
            COUNT(l.id) AS lesson_count
        FROM 
            students_courses sc
        JOIN 
            courses c ON c.id = sc.course_id
        LEFT JOIN 
            chapters ch ON ch.course_id = c.id
        LEFT JOIN 
            lessons l ON l.chapter_id = ch.id
        LEFT JOIN 
        packages_courses pc ON pc.course_id = c.id
        WHERE sc.student_id = :user_id AND pc.package_id = :track_id
        GROUP BY 
            sc.student_id, sc.course_id, c.title, sc.watched
        ORDER BY 
            sc.student_id, c.title;';

$stmt = $pdo->prepare($query);
foreach ($allowedParams as $param) {
    if (!isset($requestData[$param['name']])) {
        continue;
    }
    switch ($param['type']) {
        case 'integer':
            $stmt->bindParam(':' . $param['name'], $requestData[$param['name']], PDO::PARAM_INT);
            break;
        case 'boolean':
            $stmt->bindParam(':' . $param['name'], $requestData[$param['name']], PDO::PARAM_BOOL);
            break;
        case 'json':
            $value = json_encode($requestData[$param['name']]);
            $stmt->bindParam(':' . $param['name'], $value, PDO::PARAM_STR);
            break;
        default:
            $stmt->bindParam(':' . $param['name'], $requestData[$param['name']], PDO::PARAM_STR);
    }
}

try {
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as &$row) {
        $row['watched'] = count(explode(',', $row['watched']));
        $row['lesson_count'] = (int) $row['lesson_count'];
        $row['progress'] = $row['lesson_count'] > 0 ? round(($row['watched'] / $row['lesson_count']) * 100, 2) : 0;
    }
    $data = ['data' => $result, 'status' => 'success', 'message' => 'Data fetched successfully'];
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $data = ['data' => [], 'status' => 'error', 'message' => 'Database error'];

}

echo json_encode($data);
?>