<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../middleware/route_guard.php';
require_once __DIR__ . '/../../utilities/jwt.php';
require_once __DIR__ . '/../../utilities/dbconfig.php';


$newRefreshToken = bin2hex(random_bytes(32));
$newRefreshHash  = hash('sha256', $newRefreshToken);
echo $newRefreshHash;

header("Content-Type: application/json");

// read input
$input = json_decode(file_get_contents("php://input"), true);
$refreshToken = $input['refreshToken'] ?? null;

if (!$refreshToken) {
    http_response_code(401);
    echo json_encode(["status" => false, "message" => "Refresh token required"]);
    exit;
}

// hash incoming refresh token
$refreshHash = hash('sha256', $refreshToken);

// get user by refresh token
$sql = "SELECT user_id, refresh_token_expire_time FROM users WHERE refresh_token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $refreshHash);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    http_response_code(401);
    echo json_encode(["status" => false, "message" => "Invalid refresh token"]);
    exit;
}

if (strtotime($user['refresh_token_expire_time']) < time()) {
    http_response_code(401);
    echo json_encode(["message" => "Refresh token expired"]);
    exit;
}

$user_id = $user['user_id'];

// ROTATE refresh token
$newRefreshToken = bin2hex(random_bytes(32));
$newRefreshHash  = hash('sha256', $newRefreshToken);

$update = $conn->prepare(
    "UPDATE users SET refresh_token = ? WHERE user_id = ?"
);
$update->bind_param("si", $newRefreshHash, $user_id);
$update->execute();

// generate new access token
$newAccessToken = JWT::encode(
    ["user_id" => $user_id],
    $_ENV["JWT_SECRET"],
    900 // 15 minutes
);

echo json_encode([
    "status" => true,
    "accessToken" => $newAccessToken,
    "refreshToken" => $newRefreshToken
]);
