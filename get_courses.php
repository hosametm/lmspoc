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
$allowedParams = json_decode('[]', true);
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

// Default pagination values
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int) $_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Count total courses for pagination info
$totalQuery = 'SELECT COUNT(*) as total FROM courses';
$totalStmt = $pdo->query($totalQuery);
$totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
$total = $totalResult ? (int) $totalResult['total'] : 0;

// Main query with pagination
$query = 'SELECT c.id, c.title, c.title_ar,c.duration, p.title AS track_title , COUNT(l.id) AS lesson_count
        FROM courses c
        LEFT JOIN 
            chapters ch ON ch.course_id = c.id
        LEFT JOIN 
            lessons l ON l.chapter_id = ch.id
        LEFT JOIN packages_courses pc ON c.id = pc.course_id
        LEFT JOIN packages p ON pc.package_id = p.id
        WHERE c.status = 1
        GROUP BY c.id, c.title, c.title_ar, c.duration, p.title
        LIMIT :limit OFFSET :offset';

$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

try {
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = [
        'data' => $result,
        'status' => 'success',
        'message' => 'Data fetched successfully',
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ];
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $data = ['data' => [], 'status' => 'error', 'message' => 'Database error'];

}

echo json_encode($data);
?>