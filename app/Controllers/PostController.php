<?php
namespace App\Controllers;
use App\Core\Database;
use App\Core\Response;
use App\Core\Request;;

class PostController
{

    public static function getPosts()
    //return the post
    {
        $conn = Database::connect();
        // header("Content-Type: application/json");
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $resultsPerPage = 5;
        $startFrom = ($page - 1) * $resultsPerPage;

        //finding post by flitering comment and react and time stamp
        $sql = ("
                    SELECT p.post_id,p.creater_id,p.shared_post_id,p.privacy,p.content,p.is_archived,p.is_draft,p.is_deleted,p.is_shared,p.created_at,p.updated_at,
                    count(distinct r.react_id) AS react_count,
                    count(distinct c.comment_id) AS comment_count,
                    (COUNT(DISTINCT c.comment_id) + COUNT(DISTINCT r.react_id)) AS total_engagement
                    FROM posts p LEFT JOIN react r ON r.post_id = p.post_id LEFT JOIN comment c ON c.post_id = p.post_id
                    WHERE p.is_deleted = 0 
                    GROUP BY p.post_id ORDER BY total_engagement DESC,p.created_at DESC 
                    LIMIT $resultsPerPage OFFSET $startFrom");
        $result = $conn->query($sql);
        $totalPosts = (int) self::getPostCount();
        $totalPages = ceil($totalPosts / $resultsPerPage);

        // if ($result->num_rows==0) {
        //     Response::json([
        //         "status"=>false,
        //         "message"=>"Post is not found"
        //     ],404);
        // }

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }

        Response::json([
            "status" => true,
            "page" => $page,
            "totalPages" => $totalPages,
            "data" => $posts
        ]);

    }
    // return the post depends on user_id
    public static function getPostsByUserId(){
        $conn = Database::connect();
        $input = Request::json();
        $user_id = (int)($input['user_id'] ?? 0);

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
        

        $totalPosts = (int)self::getPostCount($user_id);
        $totalPages = ceil($totalPosts / $resultsPerPage);
        Response::json([
            "status" => true,
            "message" => "User and posts are as follow",
            "page" => $page,
            "totalPages" => $totalPages,
            "data"=>$posts
        ]);

    }
    // retrun the post depends on the following_id
    public static function getFollowingPosts(){

        $conn = Database::connect();
        $input = Request::json();
        $user_id = (int)($input['user_id'] ?? 0); // username or userId

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
                INNER JOIN follows f on f.following_id=p.creater_id 
                AND f.follower_id=?
                LEFT JOIN react r ON r.post_id = p.post_id
                LEFT JOIN comment c ON c.post_id = p.post_id
                WHERE p.is_deleted = 0
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
        $totalPosts = (int)self::getFollowingPostCount($user_id);
        $totalPages = ceil($totalPosts / $resultsPerPage);
        Response::json([
            "status" => true,
            "message" => "User and posts are as follow",
            "page" => $page,
            "totalPages" => $totalPages,
            "data"=>$posts
        ]);
    }

    private static function getPostCount($userId = 0){
        $conn = Database::connect();
        // no user id
        if($userId = 0){
            $sqlTotal = "SELECT COUNT(*) AS total_posts FROM posts WHERE is_deleted = 0";
            $resultTotal = $conn->query($sqlTotal);
            $rowTotal = $resultTotal->fetch_assoc();
        }
        // with user id
        else {
            $countSql = "
            SELECT COUNT(*) AS total_posts
            FROM posts
            WHERE is_deleted = 0 AND creater_id = ?
        ";

        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param("i", $user_id);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        }
    }
    private static function getFollowingPostCount($userId=0){
         $conn = Database::connect();
        
        $sql = "
            SELECT COUNT(DISTINCT p.post_id) AS total_posts
            FROM posts p
            INNER JOIN follows f ON f.following_id = p.creater_id 
                AND f.follower_id = ?
            WHERE p.is_deleted = 0
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
    }
}