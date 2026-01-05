<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Service\ImageService;

class SaveController
{
    public static function savePost()
    {
        $conn = Database::connect();

        $post_id = (int)(Request::input("post_id") ?? 0);
        $saved_list_id = (int)(Request::input("saved_list_id") ?? 0);
        $user_id = (int)(Request::input("user_id") ?? 0);
        $name = trim(Request::input("name") ?? "");

        // post_id is always required
        if ($post_id === 0) {
            Response::json([
                "status" => false,
                "message" => "Post ID is required"
            ]);
            return;
        }

        //create new saved list
        if ($saved_list_id === 0) {

            if ($user_id === 0 || $name === "") {
                Response::json([
                    "status" => false,
                    "message" => "User ID and name are required"
                ]);
                return;
            }

            $sql = "INSERT INTO saved_lists (user_id, name, created_at)
                    VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $user_id, $name);
            $stmt->execute();

            $saved_list_id = $conn->insert_id;
            $stmt->close();
        }

        //check duplicate post
        $checkSql = "SELECT 1 FROM saved_posts
                     WHERE saved_list_id = ? AND post_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $saved_list_id, $post_id);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            Response::json([
                "status" => false,
                "message" => "Post already saved"
            ]);
            return;
        }
        $checkStmt->close();

        //save post
        $sql = "INSERT INTO saved_posts (saved_list_id, post_id, created_at)
                VALUES (?, ?, NOW())";
        $stmtSave = $conn->prepare($sql);
        $stmtSave->bind_param("ii", $saved_list_id, $post_id);
        $stmtSave->execute();
        $stmtSave->close();

        Response::json([
            "status" => true,
            "message" => "Post saved successfully"
        ]);
    }
}
