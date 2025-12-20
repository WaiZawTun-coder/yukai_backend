<?php
namespace App\Service;

use App\Core\JWT;
// require_once __DIR__  . '/../utilities/jwt.php';
require_once __DIR__ . '/../../bootstrap.php';

class TokenService
{
    public static function generateAccessToken($user_id, $scope = "auth", $expiry = 1800)
    {
        return JWT::encode(
            ["user_id" => $user_id],
            $_ENV["JWT_SECRET"],
            $expiry
        );
    }

    public static function generateRefreshToken()
    {
        $refreshToken = bin2hex(random_bytes(32));
        $refreshHash = hash('sha256', $refreshToken);
        return [$refreshToken, $refreshHash];
    }

    public static function verifyRefreshToken(string $token, string $hash): bool
    {
        return hash_equals($hash, hash("sha256", $token));
    }
}