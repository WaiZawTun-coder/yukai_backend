<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
class PrivacyController {
    
    
    
    public static function getDefault() {
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$userId) {
            return json_encode([
                "status" => false,
                "message" => "Not logged in"
            ], 401);
        }
        
        $conn = Database::connect();
        // Select the yukai database
        $conn->select_db("yukai");
        
        $sql = "SELECT default_privacy FROM user_privacy_settings WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Error in getDefault: " . $conn->error);
            return json_encode([
                "status" => true,
                "default_privacy" => 'public'
            ]);
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $privacy = $row['default_privacy'];
        } else {
            $privacy = 'public'; // Default value
        }
        
        return json_encode([
            "status" => true,
            "default_privacy" => $privacy
        ]);
    }
    
    // Update user's default privacy setting
    public static function updateDefault() {
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$userId) {
            return json_encode([
                "status" => false,
                "message" => "Not authenticated"
            ], 401);
        }
        
        // Get privacy from request
        $data = json_decode(file_get_contents('php://input'), true);
        $privacy = $data['privacy'] ?? 'public';
        
        // Validate
        $allowed = ['public', 'friends', 'only me'];
        if (!in_array($privacy, $allowed)) {
            return json_encode([
                "status" => false,
                "message" => "Invalid privacy value"
            ], 400);
        }
        
        $conn = Database::connect();
        // Select the yukai database
        $conn->select_db("yukai");
        
        // Insert or update
        $sql = "INSERT INTO user_privacy_settings (user_id, default_privacy) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE default_privacy = ?";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Error in updateDefault: " . $conn->error);
            return json_encode([
                "status" => false,
                "message" => "Database error"
            ], 500);
        }
        
        $stmt->bind_param("iss", $userId, $privacy, $privacy);
        $success = $stmt->execute();
        
        if ($success) {
            return json_encode([
                "status" => true,
                "message" => "Default privacy updated",
                "default_privacy" => $privacy
            ]);
        } else {
            return json_encode([
                "status" => false,
                "message" => "Failed to update"
            ], 500);
        }
    }
}
