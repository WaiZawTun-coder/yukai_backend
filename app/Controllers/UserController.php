<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\Request;

class UserController
{
    //return the user data
    public static function getUser()
    {
        $conn = Database::connect();
        $input = Request::json();
        $username = $_GET["username"];

        $user = Auth::getUser();
        $userId = $user["user_id"];

        if (trim($username) === "") {
            Response::json([
                "status" => false,
                "message" => "Invalid Username"
            ]);
        }

        $userSql = "SELECT 
    u.user_id,
    u.username,
    u.display_name,
    u.gender,
    u.email,
    u.phone_number,
    u.profile_image,
    u.cover_image,
    u.bio,
    u.birthday,
    u.location,
    u.is_active,
    u.last_seen,
    u.default_audience,

    -- followers
    (
        SELECT COUNT(*) 
        FROM follows f 
        WHERE f.following_user_id = u.user_id
    ) AS follower_count,

    -- following
    (
        SELECT COUNT(*) 
        FROM follows f 
        WHERE f.follower_user_id = u.user_id
    ) AS following_count,

    -- friends
    (
        SELECT COUNT(*) 
        FROM friends fr
        WHERE fr.user_1_id = u.user_id
           OR fr.user_2_id = u.user_id
    ) AS friends_count,

    -- friend status (NULL if own profile)
    CASE
        WHEN u.user_id = ? THEN NULL
        ELSE (
            SELECT fr.status
            FROM friends fr
            WHERE 
                (fr.user_1_id = ? AND fr.user_2_id = u.user_id)
             OR (fr.user_1_id = u.user_id AND fr.user_2_id = ?)
            LIMIT 1
        )
    END AS friend_status,

    -- who sent request (NULL if own profile)
    CASE
        WHEN u.user_id = ? THEN NULL
        ELSE (
            SELECT 
                CASE
                    WHEN fr.user_1_id = ? THEN 'sent'
                    WHEN fr.user_2_id = ? THEN 'received'
                END
            FROM friends fr
            WHERE 
                (fr.user_1_id = ? AND fr.user_2_id = u.user_id)
             OR (fr.user_1_id = u.user_id AND fr.user_2_id = ?)
            LIMIT 1
        )
    END AS request_direction

FROM users u
WHERE u.username = ?
";

        $stmt = $conn->prepare($userSql);
        $stmt->bind_param(
            "iiiiiiiis",
            $userId, // CASE own profile (friend_status)

            $userId, // friend_status subquery
            $userId,

            $userId, // CASE own profile (direction)

            $userId, // direction CASE WHEN
            $userId,

            $userId, // direction subquery
            $userId,

            $username
        );
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            Response::json([
                "status" => false,
                "message" => "User not found"
            ], 404);

        }

        $user = $result->fetch_assoc();

        Response::json(
            [
                "status" => true,
                "message" => "Users are as follow",
                "data" => $user
            ]

        );
    }
}
