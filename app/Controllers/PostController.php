<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Service\ImageService;

class PostController
{
    /* =====================================================
     * Helper: Attach attachments to posts
     * ===================================================== */
    private static function attachAttachments($conn, &$posts)
    {
        if (empty($posts))
            return;

        $postIds = array_keys($posts);
        $placeholders = implode(",", array_fill(0, count($postIds), "?"));
        $types = str_repeat("i", count($postIds));

        $sql = "
            SELECT attachment_id, post_id, attachment, attachment_type
            FROM attachments
            WHERE post_id IN ($placeholders)
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$postIds);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $posts[$row['post_id']]['attachments'][] = $row;
        }
    }

    /* =====================================================
     * Get all posts
     * ===================================================== */
    public static function getPosts()
    {
        $conn = Database::connect();

        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $sql = "
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

                u.display_username,
                u.gender,
                u.profile_image,

                COUNT(DISTINCT r.react_id) AS react_count,
                COUNT(DISTINCT c.comment_id) AS comment_count,
                (COUNT(DISTINCT r.react_id) + COUNT(DISTINCT c.comment_id)) AS total_engagement
            FROM posts p
            JOIN users u ON u.user_id = p.creater_id
            LEFT JOIN reacts r ON r.post_id = p.post_id
            LEFT JOIN comments c ON c.post_id = p.post_id
            WHERE p.is_deleted = 0 AND p.is_archived = 0
            GROUP BY p.post_id
            ORDER BY total_engagement DESC, p.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $row["creator"] = [
                "id" => $row["creater_id"],
                "display_username" => $row["display_username"],
                "gender" => $row["gender"],
                "profile_image" => $row["profile_image"]
            ];

            unset(
                $row["display_username"],
                $row["gender"],
                $row["profile_image"]
            );

            $row['attachments'] = [];
            $posts[$row['post_id']] = $row;
        }

        self::attachAttachments($conn, $posts);

        $totalPosts = self::getPostCount();
        $totalPages = ceil($totalPosts / $limit);

        Response::json([
            "status" => true,
            "page" => $page,
            "totalPages" => $totalPages,
            "data" => array_values($posts)
        ]);
    }

    /* =====================================================
     * Get posts by user ID
     * ===================================================== */
    public static function getPostsByUserId()
    {
        $conn = Database::connect();
        $input = Request::json();
        $user_id = (int) ($input['user_id'] ?? 0);

        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $sql = "
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
                
                u.display_username,
                u.gender,
                u.profile_image,

                COUNT(DISTINCT r.react_id) AS react_count,
                COUNT(DISTINCT c.comment_id) AS comment_count
            FROM posts p
            JOIN users u ON u.user_id = p.creater_id
            LEFT JOIN reacts r ON r.post_id = p.post_id
            LEFT JOIN comments c ON c.post_id = p.post_id
            WHERE p.is_deleted = 0 AND p.creater_id = ?
            GROUP BY p.post_id
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $row["creator"] = [
                "id" => $row["creater_id"],
                "display_username" => $row["display_username"],
                "gender" => $row["gender"],
                "profile_image" => $row["profile_image"]
            ];

            unset(
                $row["display_username"],
                $row["gender"],
                $row["profile_image"]
            );

            $row['attachments'] = [];
            $posts[$row['post_id']] = $row;
        }

        self::attachAttachments($conn, $posts);

        $totalPosts = self::getPostCount($user_id);
        $totalPages = ceil($totalPosts / $limit);

        Response::json([
            "status" => true,
            "page" => $page,
            "totalPages" => $totalPages,
            "data" => array_values($posts)
        ]);

    }

    /* =====================================================
     * Get posts from following users
     * ===================================================== */
    public static function getFollowingPosts()
    {
        $conn = Database::connect();
        $input = Request::json();
        $user_id = (int) ($input['user_id'] ?? 0);

        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $sql = "
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

                u.display_username,
                u.gender,
                u.profile_image,

                COUNT(DISTINCT r.react_id) AS react_count,
                COUNT(DISTINCT c.comment_id) AS comment_count
            FROM posts p
            JOIN users u ON u.user_id = p.creater_id
            INNER JOIN follows f 
                ON f.following_id = p.creater_id 
                AND f.follower_id = ?
            LEFT JOIN reacts r ON r.post_id = p.post_id
            LEFT JOIN comments c ON c.post_id = p.post_id
            WHERE p.is_deleted = 0
            GROUP BY p.post_id
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $row["creator"] = [
                "id" => $row["creater_id"],
                "display_username" => $row["display_username"],
                "gender" => $row["gender"],
                "profile_image" => $row["profile_image"]
            ];

            unset(
                $row["display_username"],
                $row["gender"],
                $row["profile_image"]
            );

            $row['attachments'] = [];
            $posts[$row['post_id']] = $row;
        }

        self::attachAttachments($conn, $posts);

        $totalPosts = self::getFollowingPostCount($user_id);
        $totalPages = ceil($totalPosts / $limit);

        Response::json([
            "status" => true,
            "page" => $page,
            "totalPages" => $totalPages,
            "data" => array_values($posts)
        ]);
    }

    /* =====================================================
     * Create Post
     * ===================================================== */
    public static function createPost()
    {
        $conn = Database::connect();
        $input = Request::json();

        $creator_id = (int) (Request::input("creator_id") ?? 0);
        $content = trim(Request::input("content") ?? '');
        $privacy = Request::input("privacy") ?? 'public';
        $shared_post_id = Request::input("shared_post_id") ?? null;
        $is_draft = (int) (Request::input("is_draft") ?? 0);
        $is_archived = (int) (Request::input("is_archived") ?? 0);

        if ($creator_id === 0) {
            Response::json([
                "status" => false,
                "message" => "Invalid creator"
            ], 400);
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // =============================
            // Insert post
            // =============================
            $sql = "
            INSERT INTO posts 
            (creater_id, content, privacy, shared_post_id, is_draft, is_archived, is_deleted, is_shared, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, NOW())
        ";

            $is_shared = $shared_post_id ? 1 : 0;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "issiiii",
                $creator_id,
                $content,
                $privacy,
                $shared_post_id,
                $is_draft,
                $is_archived,
                $is_shared
            );

            $stmt->execute();

            $post_id = $conn->insert_id;

            // =============================
            // Handle attachments (optional)
            // =============================
            if (!empty($_FILES['attachments'])) {
                foreach ($_FILES['attachments']['tmp_name'] as $index => $tmpPath) {
                    if (!is_uploaded_file($tmpPath))
                        continue;

                    $fileArray = [
                        "tmp_name" => $_FILES['attachments']['tmp_name'][$index],
                        "name" => $_FILES['attachments']['name'][$index],
                        "type" => $_FILES['attachments']['type'][$index],
                        "error" => $_FILES['attachments']['error'][$index],
                        "size" => $_FILES['attachments']['size'][$index],
                    ];

                    // Upload using ImageService
                    $uploadResult = ImageService::uploadImage($fileArray, "posts-images");

                    // Store in DB
                    $attachSql = "
                        INSERT INTO attachments (post_id, attachment, attachment_type)
                        VALUES (?, ?, ?)
                    ";
                    $stmtAttach = $conn->prepare($attachSql);
                    $fileUrl = $uploadResult["secure_url"];
                    // $mime = mime_content_type($fileArray['tmp_name']) ?? "application/octet-stream";
                    $fileType = explode("/", $fileArray['type'])[0];

                    $stmtAttach->bind_param(
                        "iss",
                        $post_id,
                        $fileUrl,
                        $fileType
                    );
                    $stmtAttach->execute();
                }
            }


            // Commit transaction
            $conn->commit();

            Response::json([
                "status" => true,
                "message" => "Post created successfully",
                "post_id" => $post_id
            ], 201);

        } catch (\Throwable $e) {

            $conn->rollback();

            Response::json([
                "status" => false,
                "message" => "Failed to create post",
                "error" => $e->getMessage()
            ], 500);
        }
    }
    /* =====================================================
     *  Insert React
     * ===================================================== */
    public static function reactPost(){
        $conn = Database::connect();
        $input = Request::json();

        $user_id = (int) (Request::input("user_id") ?? 0);
        $post_id = (int)(Request::input("post_id") ?? 0);
        $reaction_type = trim(Request::input("reaction_type") ?? 'like');

        $checkReact="select reaction_type from react where user_id=? and post_id=?";
        $checkStmt=$conn->prepare($checkReact);
        $checkStmt->bind_param("ii",$user_id,$post_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if($checkResult->num_rows>0){ //check reaction_type is already exist?
            $existing=$checkResult->fetch_assoc();
            if ($existing['reaction_type'] === $reaction_type) {
                    //delete react
                    $deleteSql = "DELETE FROM react WHERE user_id = ? AND post_id = ?";
                    $deleteStmt = $conn->prepare($deleteSql);
                    $deleteStmt->bind_param("ii", $user_id, $post_id);
                    $deleteStmt->execute();
                    
                    Response::json([
                        "status" => true,
                        "message" => "Reaction removed successfully"
                    ]);
                }
                else{
                
                 // Update
                $reactUpdate= $conn->prepare(
                    "UPDATE react SET reaction_type=? WHERE post_id=? AND user_id=?"
                );
                $reactUpdate->bind_param("sii", $reaction_type, $post_id, $user_id);
                $reactUpdate->execute();

                Response::json([
                    "status"=>true,
                    "message"=>"Update Successfully"
                ]);
            }
            
        }
        //insert react
        else{
        $sql="Insert into react(post_id,user_id,reaction_type) values (?,?,?)";

        $stmtReact=$conn->prepare($sql);
        $stmtReact->bind_param("iis",$user_id,$post_id,$reaction_type);;
        $stmtReact->execute();
        Response::json([
            "status"=>true,
            "message"=>"Added Successfully"
        ]);
    
        }
    }

    /* =====================================================
     *  Insert Comment
     * ===================================================== */
    public static function commentPost(){
        $conn = Database::connect();
        $input = Request::json();

        $user_id = (int) (Request::input("user_id") ?? 0);
        $post_id = (int)(Request::input("post_id") ?? 0);
        $comment = trim(Request::input("comment") ?? null);

        $sql="Insert into comment(post_id,user_id,comment) values (?,?,?)";

        $stmtReact=$conn->prepare($sql);;
        $stmtReact->bind_param("iis",$user_id,$post_id,$comment);;
        $stmtReact->execute();
        Response::json([
            "status"=>true,
            "message"=>"Added Successfully"
        ]);
        
    }


    /* =====================================================
     * Count helpers
     * ===================================================== */
    private static function getPostCount($userId = 0)
    {
        $conn = Database::connect();

        if ($userId === 0) {
            $result = $conn->query("
                SELECT COUNT(*) AS total 
                FROM posts 
                WHERE is_deleted = 0
            ");
            return (int) $result->fetch_assoc()['total'];
        }

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total 
            FROM posts 
            WHERE is_deleted = 0 AND creater_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        return (int) $result->fetch_assoc()['total'];
    }

    private static function getFollowingPostCount($userId)
    {
        $conn = Database::connect();

        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT p.post_id) AS total
            FROM posts p
            INNER JOIN follows f ON f.following_id = p.creater_id 
                AND f.follower_id = ?
            WHERE p.is_deleted = 0
        ");

        $stmt = $conn->prepare($stmt);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        return (int) $result->fetch_assoc()['total'];
    }
}
