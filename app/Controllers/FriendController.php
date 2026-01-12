<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Core\Auth;

class FriendController
{
    public static function getFriends()
    {
        $conn = Database::connect();
        $user = Auth::getUser();
        $user_id = $user["user_id"];

        [$page, $limit, $offset] = self::getPageParams();

        /* ---------- COUNT ---------- */
        $countSql = "
        SELECT COUNT(*) AS total
        FROM friends f
        WHERE (f.user_1_id = ? OR f.user_2_id = ?)
        AND f.status = 'accepted'
    ";

        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param("ii", $user_id, $user_id);
        $countStmt->execute();
        $total = (int) $countStmt->get_result()->fetch_assoc()['total'];
        $total_pages = ceil($total / $limit);

        /* ---------- DATA ---------- */
        $sql = "
        SELECT 
            u.user_id,
            u.username,
            u.display_name,
            u.gender,
            u.profile_image
        FROM friends f
        JOIN users u 
          ON u.user_id = IF(f.user_1_id = ?, f.user_2_id, f.user_1_id)
        WHERE (f.user_1_id = ? OR f.user_2_id = ?)
        AND f.status = 'accepted'
        LIMIT ? OFFSET ?
    ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iiiii",
            $user_id,
            $user_id,
            $user_id,
            $limit,
            $offset
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $friends = [];
        while ($row = $result->fetch_assoc()) {
            $friends[] = $row;
        }

        Response::json([
            "status" => true,
            "page" => $page,
            "total_pages" => $total_pages,
            "total" => $total,
            "data" => $friends
        ]);
    }


    public static function sendFriendRequest()
    {
        $conn = Database::connect();
        $input = Request::json();

        $user = Auth::getUser();
        $user_1_id = (int) $user["user_id"]; // sender
        $user_2_id = (int) ($input['user_id'] ?? 0); // receiver

        if ($user_1_id === 0 || $user_2_id === 0 || $user_1_id === $user_2_id) {
            Response::json([
                "status" => false,
                "message" => "Invalid user"
            ]);
            return;
        }

        // check existing relation (both directions)
        $checkSql = "
        SELECT friend_id, status
        FROM friends
        WHERE (user_1_id=? AND user_2_id=?)
           OR (user_1_id=? AND user_2_id=?)
        LIMIT 1
    ";
        $check = $conn->prepare($checkSql);
        $check->bind_param("iiii", $user_1_id, $user_2_id, $user_2_id, $user_1_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $row = $result->fetch_assoc();

            if ($row["status"] === "pending") {
                Response::json([
                    "status" => false,
                    "message" => "Friend request already exists"
                ]);
                return;
            }

            if ($row["status"] === "accepted") {
                Response::json([
                    "status" => false,
                    "message" => "You are already friends"
                ]);
                return;
            }

            // reuse row → set to pending again
            $updateSql = "
            UPDATE friends
            SET status='pending', user_1_id=?, user_2_id=?
            WHERE friend_id=?
        ";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("iii", $user_1_id, $user_2_id, $row["friend_id"]);

        } else {

            // no record → insert new
            $insertSql = "
            INSERT INTO friends (user_1_id, user_2_id, status)
            VALUES (?, ?, 'pending')
        ";
            $stmt = $conn->prepare($insertSql);
            $stmt->bind_param("ii", $user_1_id, $user_2_id);
        }

        $stmt->execute();

        // auto follow sender -> receiver
        self::addFollow($conn, true, $user_1_id, $user_2_id);

        Response::json([
            "status" => true,
            "message" => "Friend request sent"
        ]);
    }


    public static function responseFriendRequest()
    {
        $conn = Database::connect();
        $input = Request::json();

        $user = Auth::getUser();
        $type = (string) ($input['status'] ?? '');
        if (in_array($type, ['accepted', 'rejected'])) {
            $sender_id = (int) ($input["user_id"] ?? 0);
            $receiver_id = $user["user_id"];
        } else {
            $sender_id = $user["user_id"];
            $receiver_id = (int) ($input["user_id"] ?? 0);
        }
        // $sender_id = $user["user_id"];
        // $receiver_id = (int) ($input['user_id'] ?? 0);

        if (!in_array($type, ['accepted', 'rejected', 'canceled'])) {
            Response::json([
                "status" => false,
                "message" => "invalid input"
            ]);
        }
        $acceptFri = "Update friends set status=? where user_1_id=? and user_2_id=? and status='pending'";
        $updateFriList = $conn->prepare($acceptFri);
        $updateFriList->bind_param("sii", $type, $sender_id, $receiver_id);
        $updateFriList->execute();

        if ($type === 'canceled') {
            $message = "Friend requent canceled";
        } else if ($type === 'accepted') {
            $message = "Friend request accepted";
            self::addFollow($conn, true, $receiver_id, $sender_id);
        } else if ($type === "rejected") {
            $message = "Friend request rejected ";
        }

        Response::json([
            "status" => true,
            "message" => $message,
            "type" => $type
        ]);

    }
    public static function getFriendRequest()
    {
        $conn = Database::connect();
        $input = Request::json();
        $sender_id = (int) ($input['user_1_id'] ?? 0);
        $friendRequentList =
            "SELECT f.user_2_id as requsted_user_id,
                u.display_name,
                u.profile_image,
                u.cover_image,
                f.created_at
                FROM friends f
                JOIN users u ON f.user_2_id = u.user_id
                WHERE f.user_1_id =? 
                AND f.status='pending'
                ORDER BY f.created_at DESC";
        $getRequest = $conn->prepare($friendRequentList);
        $getRequest->bind_param("i", $sender_id);
        $getRequest->execute();
        $getResultList = $getRequest->get_result();
        $posts = [];
        while ($row["creator"] = $getResultList->fetch_assoc()) {
            // $row["creator"]=[
            //     "id"=>$row["user_id"],
            //     "display_name"=>$row["display_name"],
            //     "profile_image"=>$row["profile_image"]
            // ];
            $posts[] = $row;
        }

        Response::json([
            "status" => true,
            "message" => "Get Friend Request List",
            "data" => array_values($posts)
        ]);

    }
    public static function getReceivedRequests()
    {
        $conn = Database::connect();
        $user = Auth::getUser();
        $receiver_id = $user["user_id"];

        [$page, $limit, $offset] = self::getPageParams();

        /* ---------- COUNT ---------- */
        $countSql = "
        SELECT COUNT(*) AS total
        FROM friends
        WHERE user_2_id = ?
        AND status = 'pending'
    ";

        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param("i", $receiver_id);
        $countStmt->execute();
        $total = (int) $countStmt->get_result()->fetch_assoc()['total'];
        $total_pages = ceil($total / $limit);

        /* ---------- DATA ---------- */
        $sql = "
        SELECT 
            f.user_1_id AS user_id,
            u.display_name,
            u.username,
            u.profile_image,
            u.gender,
            f.created_at
        FROM friends f
        JOIN users u ON u.user_id = f.user_1_id
        WHERE f.user_2_id = ?
        AND f.status = 'pending'
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $receiver_id, $limit, $offset);
        $stmt->execute();

        $result = $stmt->get_result();
        $requests = [];

        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }

        Response::json([
            "status" => true,
            "page" => $page,
            "total_pages" => $total_pages,
            "total" => $total,
            "data" => $requests
        ]);
    }
    public static function peopleYouMayKnow(int $page = 1, int $limit = 20)
    {
        $conn = Database::connect();
        $user = Auth::getUser();

        $me = (int) $user['user_id'];
        $myLocation = $user['location'] ?? null;

        $offset = ($page - 1) * $limit;

        /* ================= TOTAL COUNT ================= */
        $countSql = "
        SELECT COUNT(*) AS total
        FROM users u
        WHERE u.user_id != ?
          AND u.user_id NOT IN (
              SELECT IF(user_1_id = ?, user_2_id, user_1_id)
              FROM friends
              WHERE status IN ('accepted','pending')
                AND ? IN (user_1_id, user_2_id)
          )
          AND u.user_id NOT IN (
              SELECT blocked_user_id FROM blocks WHERE blocker_user_id = ?
              UNION
              SELECT blocker_user_id FROM blocks WHERE blocked_user_id = ?
          )
    ";

        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param("iiiii", $me, $me, $me, $me, $me);
        $countStmt->execute();
        $total = (int) $countStmt->get_result()->fetch_assoc()['total'];
        $totalPages = (int) ceil($total / $limit);

        /* ================= MAIN QUERY ================= */
        $sql = "
    SELECT
        u.user_id,
        u.username,
        u.display_name,
        u.profile_image,
        u.gender,
        u.location,

        (
            COALESCE(m.mutual_count, 0) * 10 +
            COALESCE(r.react_score, 0) +
            COALESCE(c.comment_score, 0) +
            COALESCE(t.tag_score, 0) +
            COALESCE(f.follow_score, 0) +
            CASE WHEN ? IS NOT NULL AND u.location = ? THEN 6 ELSE 0 END
        ) AS score

    FROM users u

    /* -------- MUTUAL FRIENDS -------- */
    LEFT JOIN (
        SELECT candidate_id, COUNT(*) AS mutual_count
        FROM (
            SELECT IF(f2.user_1_id = my.friend_id, f2.user_2_id, f2.user_1_id) AS candidate_id
            FROM (
                SELECT IF(user_1_id = ?, user_2_id, user_1_id) AS friend_id
                FROM friends
                WHERE status = 'accepted'
                  AND ? IN (user_1_id, user_2_id)
            ) my
            JOIN friends f2
              ON my.friend_id IN (f2.user_1_id, f2.user_2_id)
            WHERE f2.status = 'accepted'
        ) x
        GROUP BY candidate_id
    ) m ON m.candidate_id = u.user_id

    /* -------- POST REACTS -------- */
    LEFT JOIN (
        SELECT pr2.user_id, COUNT(*) * 2 AS react_score
        FROM post_reacts pr1
        JOIN post_reacts pr2 ON pr1.post_id = pr2.post_id
        WHERE pr1.user_id = ?
          AND pr2.user_id != ?
        GROUP BY pr2.user_id
    ) r ON r.user_id = u.user_id

    /* -------- COMMENTS -------- */
    LEFT JOIN (
        SELECT pc2.user_id, COUNT(*) * 3 AS comment_score
        FROM post_comments pc1
        JOIN post_comments pc2 ON pc1.post_id = pc2.post_id
        WHERE pc1.user_id = ?
          AND pc2.user_id != ?
        GROUP BY pc2.user_id
    ) c ON c.user_id = u.user_id

    /* -------- TAGS -------- */
    LEFT JOIN (
        SELECT pt2.tagged_user_id, COUNT(*) * 5 AS tag_score
        FROM post_tags pt1
        JOIN post_tags pt2 ON pt1.post_id = pt2.post_id
        WHERE pt1.tagged_user_id = ?
          AND pt2.tagged_user_id != ?
        GROUP BY pt2.tagged_user_id
    ) t ON t.tagged_user_id = u.user_id

    /* -------- FOLLOW -------- */
    LEFT JOIN (
        SELECT following_user_id AS user_id, 4 AS follow_score
        FROM follows
        WHERE follower_user_id = ?
          AND status = 1
    ) f ON f.user_id = u.user_id

    WHERE u.user_id != ?
      AND u.user_id NOT IN (
          SELECT IF(user_1_id = ?, user_2_id, user_1_id)
          FROM friends
          WHERE status IN ('accepted','pending')
            AND ? IN (user_1_id, user_2_id)
      )
      AND u.user_id NOT IN (
          SELECT blocked_user_id FROM blocks WHERE blocker_user_id = ?
          UNION
          SELECT blocker_user_id FROM blocks WHERE blocked_user_id = ?
      )

    ORDER BY score DESC, u.created_at DESC
    LIMIT ? OFFSET ?
    ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssiiiiiiiiiiiiiiii",
            $myLocation,
            $myLocation,
            $me,
            $me,
            $me,
            $me,
            $me,
            $me,
            $me,
            $me,
            $me,
            $me,
            $me,
            $me,
            $me,
            $me,
            $limit,
            $offset
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $people = [];
        while ($row = $result->fetch_assoc()) {
            $people[] = $row;
        }

        Response::json([
            "status" => true,
            "page" => $page,
            "total_pages" => $totalPages,
            "total" => $total,
            "data" => $people
        ]);
    }


    private static function getPageParams()
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        return [$page, $limit, $offset];
    }

    private static function addFollow($conn, $follow, $user_1_id, $user_2_id)
    {
        if (!$follow)
            return;

        // check existing follow
        $checkSql = "
        SELECT follow_id
        FROM follows
        WHERE follower_user_id = ?
          AND following_user_id = ?
        LIMIT 1
    ";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $user_1_id, $user_2_id);
        $checkStmt->execute();
        $res = $checkStmt->get_result();

        if ($res->num_rows > 0) {
            return; // already following
        }

        // insert follow
        $followSql = "
        INSERT INTO follows (follower_user_id, following_user_id, status)
        VALUES (?, ?, 1)
    ";
        $followStmt = $conn->prepare($followSql);
        $followStmt->bind_param("ii", $user_1_id, $user_2_id);
        $followStmt->execute();
    }


}