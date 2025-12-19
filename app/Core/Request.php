<?php
namespace App\Core;

class Request {
    public static function json(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (!str_contains($contentType, 'application/json')) {
            return [];
        }

        $raw = file_get_contents("php://input");
        return json_decode($raw, true) ?? [];
    }
}
