<?php

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class MessageController
{
    /**
     * Get messages of a chat (with pagination)
     */
    public static function getMessages()
    {
        $conn = Database::connect();
        $user = Auth::getUser();
        $user_id = (int) $user["user_id"];
        $input = Request::json();

        $chat_id = (int) ($input["chat_id"] ?? 0);
        $page = max(1, (int) ($input["page"] ?? 1));
        $limit = min(50, max(1, (int) ($input["limit"] ?? 30)));
        $offset = ($page - 1) * $limit;

        if (!$chat_id) {
            Response::json(["status" => false, "message" => "Invalid chat_id"]);
            return;
        }

        // Security: ensure user is participant
        $check = $conn->prepare("SELECT 1 FROM chat_participants WHERE chat_id=? AND user_id=? LIMIT 1");
        $check->bind_param("ii", $chat_id, $user_id);
        $check->execute();
        if (!$check->get_result()->fetch_row()) {
            Response::json(["status" => false, "message" => "Access denied"]);
            return;
        }

        $device_id = $input["device_id"] ?? null;
        if (!$chat_id || !$device_id) {
            Response::json(["status" => false, "message" => "chat_id and device_id required"]);
            return;
        }

        // Get messages
        $sql = "SELECT m.message_id, m.sender_user_id, m.message_type, m.reply_to_message_id, m.sent_at, m.is_edited, m.is_deleted,
                    u.username, u.display_name, u.profile_image,
                    mp.cipher_text, mp.iv, mp.signed_prekey_id
                FROM messages m
                JOIN users u ON u.user_id = m.sender_user_id
                LEFT JOIN message_payloads mp 
                       ON mp.message_id = m.message_id 
                      AND mp.recipient_user_id = ? 
                      AND mp.recipient_device_id = ?
                WHERE m.chat_id = ?
                ORDER BY m.sent_at DESC
                LIMIT ? OFFSET ?
";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiii", $user_id, $device_id, $chat_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }

        Response::json([
            "status" => true,
            "page" => $page,
            "limit" => $limit,
            "data" => array_reverse($messages)
        ]);
    }


    /**
     * Send a message
     */
    public static function sendMessage()
    {
        $conn = Database::connect();
        $user = Auth::getUser();
        $user_id = (int) $user["user_id"];
        $input = Request::json();

        $chat_id = (int) ($input["chat_id"] ?? 0);
        $payloads = $input["payloads"] ?? []; // array of {recipient_user_id, recipient_device_id, cipher_text, iv, signed_prekey_id}
        $message_type = $input["message_type"] ?? 'text';
        $reply_to = isset($input["reply_to_message_id"]) ? (int) $input["reply_to_message_id"] : null;

        if (!$chat_id || !$payloads || !is_array($payloads)) {
            Response::json(["status" => false, "message" => "chat_id and payloads required"]);
            return;
        }

        // Verify sender is a participant
        $check = $conn->prepare("SELECT 1 FROM chat_participants WHERE chat_id=? AND user_id=? LIMIT 1");
        $check->bind_param("ii", $chat_id, $user_id);
        $check->execute();
        if (!$check->get_result()->fetch_row()) {
            Response::json(["status" => false, "message" => "Access denied"]);
            return;
        }

        // Insert message metadata
        $sql = "INSERT INTO messages (chat_id, sender_user_id, message_type, reply_to_message_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisi", $chat_id, $user_id, $message_type, $reply_to);
        $stmt->execute();
        $message_id = $stmt->insert_id;

        // Insert per-device payloads
        $sqlPayload = "INSERT INTO message_payloads 
        (message_id, recipient_user_id, recipient_device_id, cipher_text, iv, signed_prekey_id) 
        VALUES (?, ?, ?, ?, ?, ?)";
        $stmtPayload = $conn->prepare($sqlPayload);

        foreach ($payloads as $p) {
            $recipient_user_id = (int) $p['recipient_user_id'];
            $recipient_device_id = $p['recipient_device_id'];
            $cipher_text = $p['cipher_text'];
            $iv = $p['iv'];
            $signed_prekey_id = (int) $p['signed_prekey_id'];

            $stmtPayload->bind_param(
                "iisssi",
                $message_id,
                $recipient_user_id,
                $recipient_device_id,
                $cipher_text,
                $iv,
                $signed_prekey_id
            );
            $stmtPayload->execute();
        }

        Response::json([
            "status" => true,
            "message" => "Message sent",
            "message_id" => $message_id
        ]);
    }


    /**
     * Edit a message
     */
    public static function editMessage()
    {
        $conn = Database::connect();
        $user = Auth::getUser();
        $user_id = (int) $user["user_id"];
        $input = Request::json();

        $message_id = (int) ($input["message_id"] ?? 0);
        $cipher_text = $input["cipher_text"] ?? '';

        if (!$message_id || !$cipher_text) {
            Response::json(["status" => false, "message" => "message_id and new cipher_text required"]);
            return;
        }

        $sql = "UPDATE messages SET cipher_text=?, is_edited=1 WHERE message_id=? AND sender_user_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $cipher_text, $message_id, $user_id);
        $stmt->execute();

        Response::json([
            "status" => true,
            "message" => "Message edited"
        ]);
    }

    /**
     * Delete a message (soft delete)
     */
    public static function deleteMessage()
    {
        $conn = Database::connect();
        $user = Auth::getUser();
        $user_id = (int) $user["user_id"];
        $input = Request::json();

        $message_id = (int) ($input["message_id"] ?? 0);

        if (!$message_id) {
            Response::json(["status" => false, "message" => "message_id required"]);
            return;
        }

        $sql = "UPDATE messages SET is_deleted=1, cipher_text='' WHERE message_id=? AND sender_user_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $message_id, $user_id);
        $stmt->execute();

        Response::json([
            "status" => true,
            "message" => "Message deleted"
        ]);
    }

    /**
     * Update message receipt status (seen / delivered)
     */
    public static function updateReceipt()
    {
        $conn = Database::connect();
        $user = Auth::getUser();
        $user_id = (int) $user["user_id"];
        $input = Request::json();

        $chat_id = (int) ($input["chat_id"] ?? 0);
        $status = $input["status"] ?? 'seen';

        if (!$chat_id || !in_array($status, ['delivered', 'seen'])) {
            Response::json(["status" => false, "message" => "Invalid input"]);
            return;
        }

        // Update messages sent by others in this chat
        $sql = "UPDATE messages SET status=? WHERE chat_id=? AND sender_user_id!=? AND status != 'seen'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $status, $chat_id, $user_id);
        $stmt->execute();

        Response::json([
            "status" => true,
            "message" => "Receipt updated"
        ]);
    }
}
