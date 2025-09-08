<?php
header('Content-Type: application/json');
require_once './connection.php';
session_start();
$json = file_get_contents("php://input");
$_POST = json_decode($json, true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $data = ['data' => [], 'status' => 'error', 'message' => 'Invalid request method'];
    echo json_encode($data);
    exit();
}
$allowedParams = json_decode('[{"name":"name","type":"string","required":"1"},{"name":"email","type":"string","required":"1"},{"name":"competency","type":"string","required":"1"},{"name":"job_title","type":"string","required":"1"}]', true);
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

$query = "INSERT INTO learning_assignment_requests 
    (name,email, competency, job_title)
VALUES
    (:name, :email, :competency, :job_title);";
$stmt = $pdo->prepare($query);
foreach ($allowedParams as $param) {
    if (!isset($requestData[$param['name']])) {
        continue;
    }
    $value = $requestData[$param['name']];
    switch ($param['type']) {
        case 'integer':
            $stmt->bindParam(':' . $param['name'], $value, PDO::PARAM_INT);
            break;
        case 'boolean':
            $stmt->bindParam(':' . $param['name'], $value, PDO::PARAM_BOOL);
            break;
        case 'json':
            $value = json_encode($value);
            $stmt->bindParam(':' . $param['name'], $value, PDO::PARAM_STR);
            break;
        default:
            $stmt->bindParam(':' . $param['name'], $value, PDO::PARAM_STR);
    }
}

try {
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = ['data' => $result, 'status' => 'success', 'message' => 'Data fetched successfully'];
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $data = ['data' => [], 'status' => 'error', 'message' => 'Database error'];

}

echo json_encode($data);
?>