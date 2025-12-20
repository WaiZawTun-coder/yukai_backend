<?php
namespace App\Core;

use Exception;

class JWT{
    private static function base64UrlEncode($data){
        return rtrim(strtr(base64_encode($data), "+/", "-_"), "=");
    }

    private static function base64UrlDecode($data){
        return base64_decode(strtr($data, "-_", "+/"));
    }

    public static function encode($payload, $secret, $expireSeconds = 86400){
        $header = ["alg" => "HS256", "typ" => "JWT"];

        $payload["iat"] = time();
        $payload["exp"] = time() + $expireSeconds;

        $base64Header = self::base64UrlEncode(json_encode($header));
        $base64Payload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac("sha256", $base64Header . "." . $base64Payload, $secret, true);

        return $base64Header . "." . $base64Payload . "." . self::base64UrlEncode($signature);
    }

    public static function decode($token, $secret){
        $parts = explode(".", $token);
        if(count($parts) !== 3){
            throw new Exception("Invalid token format");
        }

        [$header, $payload, $signature] = $parts;

        $validSignature = self::base64UrlEncode(hash_hmac("sha256", "$header.$payload", $secret, true));

        if(!hash_equals($validSignature, $signature)){
            throw new Exception("Invalid token signature");
        }

        $payloadData = json_decode(self::base64UrlDecode($payload), true);

        if($payloadData["exp"] < time()){
            throw new Exception("Token has expired");
        }

        return $payloadData;
    }
}