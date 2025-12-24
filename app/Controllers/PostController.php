<?php
namespace App\Controllers;
use App\Core\Database;
use App\Core\Response;

class PostController
{

    public static function post()
    //return the post data
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
        $sqlTotal = "SELECT COUNT(*) AS total_posts FROM posts WHERE is_deleted = 0";
        $resultTotal = $conn->query($sqlTotal);
        $rowTotal = $resultTotal->fetch_assoc();
        $totalPosts = (int) $rowTotal['total_posts'];
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
}