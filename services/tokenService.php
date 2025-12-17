<?php
require_once __DIR__  . '/../utilities/jwt.php';
require_once __DIR__  . '/../bootstrap.php';

class TokenService {
    public static function generateAccessToken($user_id, $expiry = 86400) {
        return JWT::encode(
            ["user_id" => $user_id],
            $_ENV["JWT_SECRET"],
            $expiry
        );
    }

    public static function generateRefreshToken() {
        $refreshToken = bin2hex(random_bytes(32));
        $refreshHash  = hash('sha256', $refreshToken);
        return [$refreshToken, $refreshHash];
    }
}