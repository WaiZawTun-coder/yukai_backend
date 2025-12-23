<?php
namespace App\Controllers;
use App\Core\Database;
use PDO;
class PostController{

    public static function postOutput() {
        $conn = Database::connect();
        header("Content-Type: application/json");
        //finding post by flitering comment and react and time stamp
        $sql =("
                SELECT p.post_id,p.creater_id,p.shared_post_id,p.privacy,p.content,p.is_archived,p.is_draft,p.is_deleted,p.is_shared,p.created_at,p.updated_at,
                count(distinct r.react_id) AS react_count,
                count(distinct c.comment_id) AS comment_count,
                TIMESTAMPDIFF(SECOND, p.created_at, NOW()) AS seconds_old,
                (
                    COUNT(DISTINCT r.react_id)
                    + COUNT(DISTINCT c.comment_id)
                    - (TIMESTAMPDIFF(SECOND, p.created_at, NOW()) * 0.00001)
                ) AS score
                FROM posts p LEFT JOIN react r ON r.post_id = p.post_id LEFT JOIN comment c ON c.post_id = p.post_id
                WHERE p.is_deleted = 0 and (select * from posts where created_at >= DATE(CURRENT_TIMESTAMP)
                GROUP BY p.post_id ORDER BY score DESC Limit 10");
                $result = $conn->query($sql);
                
                if (!$result) {
                    throw new \Exception("Query failed: " . $conn->error);
                }
                
                $posts = [];
                while ($row = $result->fetch_assoc()) {
                    $posts[] = $row;
                }

            echo json_encode([
            "success" => true,
            "count"   => count($posts),
            "data"    => $posts
        ], JSON_PRETTY_PRINT);

    }
}