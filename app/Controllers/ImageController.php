<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\JWT;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Generator;
use App\Service\TokenService;
use App\Service\PasswordService;
use App\Service\ImageService;
use DateTime;

class SearchController
{
    public static function uploadImage()
    {
        $conn = Database::connect();
        $user_id = (int) (Request::input("user_id") ?? 0);
        
        if ($user_id <= 0) {
            Response::json([
                "status" => false,
                "message" => "Invalid user ID"
            ], 400);
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            Response::json([
                "status" => false,
                "message" => "No image uploaded or upload error"
            ], 400);
            return;
        }

        $file = $_FILES['image'];
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($fileType, $allowedTypes)) {
            Response::json([
                "status" => false,
                "message" => "Invalid file type. Allowed: " . implode(', ', $allowedTypes)
            ], 400);
            return;
        }

        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            Response::json([
                "status" => false,
                "message" => "File too large. Max size is 5MB"
            ], 400);
            return;
        }

        // Create uploads directory if it doesn't exist
        $uploadDir = 'uploads/profile_images/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filename
        $uniqueName = uniqid('profile_', true) . '.' . $fileType;
        $uploadPath = $uploadDir . $uniqueName;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Create URL for the image
            $imageUrl = '/' . $uploadPath;
            
            Response::json([
                "status" => true,
                "message" => "Image uploaded successfully",
                "data" => [
                    "image_url" => $imageUrl
                ]
            ], 200);
        } else {
            Response::json([
                "status" => false,
                "message" => "Failed to upload image"
            ], 500);
        }
    }
}