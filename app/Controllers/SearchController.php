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

class SearchController{
    public static function search(){
        $conn = Database::connect();
        // $authUser = Auth::getUser();
        $user_id = (int)(Request::input("user_id")?? 0);

        $keyword = trim(Request::input("keyword") ?? "");
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 5;
        $offset = ($page - 1) * $limit;

        if ($keyword === '') {
            Response::json([
                "status" => false,
                "message" => "Keyword is required"
            ]);
            return;
        }

        $search_word = "%{$keyword}%";

        $data = [
            "users" => [],
            "posts" => []
        ];

        /* =======================
           SEARCH USERS
        ======================= */

        $sql = "
            SELECT user_id, username, display_name, profile_image
            FROM users
            WHERE display_name LIKE ?
            AND is_active = 1
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $search_word, $limit, $offset);
        $stmt->execute();
        $users_result = $stmt->get_result();

        while ($user = $users_result->fetch_assoc()) {
            $data["users"][] = [
                "type" => "user",
                "id" => $user["user_id"],
                "display_name" => $user["display_name"],
                "username" => $user["username"],
                "profile_image" => $user["profile_image"]
            ];
        }

        /* =======================
           SEARCH POSTS (PUBLIC) AND Friend Posts
        ======================= */

        $sql = "
            SELECT 
                p.post_id,
                p.creator_user_id,
                p.shared_post_id,
                p.privacy,
                p.content,
                p.is_archived,
                p.is_draft,
                p.is_deleted,
                p.is_shared,
                p.created_at,
                p.updated_at,

                u.display_name,
                u.gender,
                u.profile_image,

                COUNT(DISTINCT r.post_react_id) AS react_count,
                COUNT(DISTINCT c.post_comment_id) AS comment_count,

                CASE 
                    WHEN COUNT(ur.post_react_id) > 0 THEN 1
                    ELSE 0
                END AS is_liked,

                MAX(ur.reaction) AS reaction

            FROM posts p
            JOIN users u ON u.user_id = p.creator_user_id

            LEFT JOIN post_reacts r ON r.post_id = p.post_id
            LEFT JOIN post_comments c ON c.post_id = p.post_id
            LEFT JOIN post_reacts ur 
                ON ur.post_id = p.post_id 
                AND ur.user_id = ?

            WHERE 
                p.is_deleted = 0
                AND p.is_draft = 0
                AND p.content LIKE ?
                AND (
                    p.privacy = 'public'
                    OR (
                        p.privacy = 'friends'
                        AND EXISTS (
                            SELECT 1
                            FROM friends fr
                            WHERE
                                fr.status = 'accepted'
                                AND (
                                    (fr.user_1_id = ? AND fr.user_2_id = p.creator_user_id)
                                    OR
                                    (fr.user_2_id = ? AND fr.user_1_id = p.creator_user_id)
                                )
                        )
                    )
                )

            GROUP BY p.post_id
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
    

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isiiii",
            $user_id,      
            $search_word,  
            $user_id,      
            $user_id,     
            $limit,
            $offset
        );
        $stmt->execute();

        $posts_result = $stmt->get_result();

        $posts = [];

        while ($row = $posts_result->fetch_assoc()) {

            $row["creator"] = [
                "id" => $row["creator_user_id"],
                "display_name" => $row["display_name"],
                "gender" => $row["gender"],
                "profile_image" => $row["profile_image"]
            ];

            unset(
                $row["display_name"],
                $row["gender"],
                $row["profile_image"]
            );

            $row["attachments"] = [];
            $posts[$row["post_id"]] = $row;
        }

        PostController::attachAttachments($conn, $posts);

        $data["posts"] = array_values($posts);

        
        $totalPosts = self::postCountByKeywords($search_word,$user_id);
        $totalPages = ceil($totalPosts / $limit);

        /* =======================
           RESPONSE
        ======================= */

        Response::json([
            "status" => true,
            "keyword" => $keyword,
            "page" => $page,
            "totalPages" => $totalPages,
            "data" => $data
        ]);
    }

    private static function postCountByKeywords($search_word, $user_id) 
    {
        $conn = Database::connect();

        $sql = "
            SELECT COUNT(*) AS total
            FROM posts p
            WHERE 
                p.is_deleted = 0
                AND p.is_draft = 0
                AND p.content LIKE ?
                AND (
                    p.privacy = 'public'
                    OR (
                        p.privacy = 'friends'
                        AND EXISTS (
                            SELECT 1
                            FROM friends fr
                            WHERE
                                fr.status = 'accepted'
                                AND (
                                    (fr.user_1_id = ? AND fr.user_2_id = p.creator_user_id)
                                    OR
                                    (fr.user_2_id = ? AND fr.user_1_id = p.creator_user_id)
                                )
                        )
                    )
                )
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $search_word, $user_id, $user_id);
        $stmt->execute();

        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }

}