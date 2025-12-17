<?php

final class PasswordService
{
    private const OPTIONS = [
        'memory_cost' => 1 << 17,
        'time_cost'   => 4,
        'threads'     => 2,
    ];

    public static function hash(string $password): string
    {
        return password_hash(
            $password,
            PASSWORD_ARGON2ID,
            self::OPTIONS
        );
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash(
            $hash,
            PASSWORD_ARGON2ID,
            self::OPTIONS
        );
    }
}
