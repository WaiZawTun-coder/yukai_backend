<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;

class UserController
{
    //return the user data
    public static function user()
    {
        $conn = Database::connect();
        $input = Request::json();
        $user_id = (int)($input['user_id'] ?? 0);

        $userSql = "
            SELECT user_id, username, display_username, gender, email,
                   phone_number, profile_image, cover_image, birthday,
                   location, is_active, last_seen, default_audience
                   FROM users
                   WHERE user_id = ?
        ";

        $stmt = $conn->prepare($userSql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            Response::json([
                "status" => false,
                "message" => "User not found"
            ], 404);
        
        }

        $user = $result->fetch_assoc();
        
        Response::json([
            "status" => true,
            "message" => "User are as follow",
            "data"=>$user
        ]);
    }
}
