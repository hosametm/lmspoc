<?php
header('Content-Type: application/json');
require_once './connection.php';
session_start();

$json = file_get_contents("php://input");
$_POST = json_decode($json, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'data' => [],
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit();
}

$allowedParams = json_decode('[{"name":"user_id","type":"integer","required":"1"},{"name":"track_id","type":"integer","required":"1"}]', true);
$missingParams = [];
$filters = [
    'track_id' => FILTER_SANITIZE_NUMBER_INT,
    'user_id' => FILTER_SANITIZE_NUMBER_INT
];
$requestData = filter_var_array($_POST, $filters);

foreach ($allowedParams as $param) {
    if (!isset($requestData[$param['name']]) && $param['required']) {
        $missingParams[] = $param['name'];
    }
}

if (!empty($missingParams)) {
    echo json_encode([
        'data' => [],
        'status' => 'error',
        'message' => 'Parameters ' . implode(', ', $missingParams) . ' are required'
    ]);
    exit();
}

try {
    $pdo->beginTransaction();

    $query = 'SELECT course_id FROM packages_courses WHERE package_id = :track_id';
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':track_id', $requestData['track_id'], PDO::PARAM_INT);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($courses)) {
        throw new Exception('No courses found for the given track ID');
    }

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE student_id = :student_id AND packages = :course_id");
    $insertStmt = $pdo->prepare("INSERT INTO transactions (student_id, packages, end_date) VALUES (:student_id, :course_id, :end_date)");

    $userId = $requestData['user_id'];
    $endDate = date('Y-m-d', strtotime('+50 year'));
    $insertedCount = 0;

    foreach ($courses as $courseId) {
        $checkStmt->execute([
            ':student_id' => $userId,
            ':course_id' => $courseId
        ]);

        if ($checkStmt->fetchColumn() == 0) {
            $insertStmt->execute([
                ':student_id' => $userId,
                ':course_id' => $courseId,
                ':end_date' => $endDate
            ]);
            $insertedCount++;
        }
    }

    $pdo->commit();

    echo json_encode([
        'data' => $insertedCount,
        'status' => 'success',
        'message' => "$insertedCount course(s) assigned successfully"
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Transaction failed: " . $e->getMessage());
    echo json_encode([
        'data' => [],
        'status' => 'error',
        'message' => 'Transaction failed: ' . $e->getMessage()
    ]);
}
?>