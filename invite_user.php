<?php
header('Content-Type: application/json');
require_once './connection.php';
// require_once './MailService.php';
session_start();

// Decode JSON input
$json = file_get_contents("php://input");
$_POST = json_decode($json, true);

// Ensure request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'data' => [],
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Prepare request data
$requestData = $_POST;

// Define allowed and required parameters
$allowedParams = json_decode('[{"name":"user_email","type":"string","required":"1"},{"name":"user_password","type":"string","required":"1"},{"name":"name","type":"s","required":"1"}]', true);

// Validate required parameters
$missingParams = [];
foreach ($allowedParams as $param) {
    if ($param['required'] && empty($requestData[$param['name']])) {
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

    // Check if user already exists
    $checkQuery = 'SELECT id FROM students WHERE email = :email';
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->bindParam(':email', $requestData['user_email'], PDO::PARAM_STR);
    $checkStmt->execute();

    if ($checkStmt->fetchColumn()) {
        throw new Exception('User with this email already exists');
    }

    // Insert new user
    $query = 'INSERT INTO students (username,email, password) VALUES (:username,:user_email, :user_password)';
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_email', $requestData['user_email'], PDO::PARAM_STR);
    $stmt->bindParam(':user_password', $requestData['user_password'], PDO::PARAM_STR);
    $stmt->bindParam(':username', $requestData['name'], PDO::PARAM_STR);

    $stmt->execute();
    $userId = $pdo->lastInsertId();
    $pdo->commit();

    // $mailService = new MailService();
    // $toEmail = $requestData['user_email'];
    // $toName = $requestData['name'];
    // $subject = 'Invitation to Join Our Platform';
    // $body = "Hello $toName,<br><br>You have been invited to join our platform. Use your PMS credentials to log in.<br>Best regards,<br>Your Team";
    // $mailService->send($toEmail, $toName, $subject, $body);

    echo json_encode([
        'data' => ['user_id' => intval($userId)],
        'status' => 'success',
        'message' => "User invited successfully"
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