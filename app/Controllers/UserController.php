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
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $resultsPerPage = 5;
        $startFrom = ($page - 1) * $resultsPerPage;
        $postSql = ("
            SELECT 
                p.post_id,
                p.creater_id,
                p.shared_post_id,
                p.privacy,
                p.content,
                p.is_archived,
                p.is_draft,
                p.is_deleted,
                p.is_shared,
                p.created_at,
                p.updated_at,
                COUNT(DISTINCT r.react_id) AS react_count,
                COUNT(DISTINCT c.comment_id) AS comment_count
                FROM posts p
                LEFT JOIN react r ON r.post_id = p.post_id
                LEFT JOIN comment c ON c.post_id = p.post_id
                WHERE p.is_deleted = 0
                AND p.creater_id = ?
                GROUP BY p.post_id
                ORDER BY p.created_at DESC
                LIMIT $resultsPerPage OFFSET $startFrom
        ");

        $postStmt = $conn->prepare($postSql);
        $postStmt->bind_param("i", $user_id);
        $postStmt->execute();
        $postResult = $postStmt->get_result();

        $posts = [];
        while ($row = $postResult->fetch_assoc()) {
            $posts[] = $row;
        }
        $countSql = "
            SELECT COUNT(*) AS total_posts
            FROM posts
            WHERE is_deleted = 0 AND creater_id = ?
        ";

        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param("i", $user_id);
        $countStmt->execute();
        $countResult = $countStmt->get_result();

        $totalPosts = (int)$countResult->fetch_assoc()['total_posts'];
        $totalPages = ceil($totalPosts / $resultsPerPage);
        Response::json([
            "status" => true,
            "message" => "User and posts are as follow",
            "page" => $page,
            "totalPages" => $totalPages,
            "data"=>$user,
            "post"=>$posts
        ]);
    }
}
