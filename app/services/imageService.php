<?php
namespace App\Service;

use App\Core\Response;
use CURLFile;

class ImageService
{
    // private static string $cloudName = getenv("CLOUDINARY_CLOUD_NAME");
    // private static string $apiKey = getenv("CLOUDINARY_API_KEY");
    // private static string $apiSecret = getenv("CLOUDINARY_API_SECRET");
    public static function uploadImage($file, $folder = "profiles")
    {
        if (!file_exists($file["tmp_name"])) {
            Response::json([
                "status" => false,
                "message" => "Image not found"
            ]);
        }

        if ($file["error"] !== UPLOAD_ERR_OK) {
            Response::json([
                "status" => false,
                "message" => "Image upload failed"
            ], 400);
        }

        $maxSize = 5 * 1024 * 1024;
        if ($file["size"] > $maxSize) {
            Response::json([
                "status" => false,
                "message" => "Image size too large (5MB)"
            ], 400);
        }

        $allowedMimes = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];

        $finfo = \finfo_open(FILEINFO_MIME_TYPE);
        $mime = \finfo_file($finfo, $file["tmp_name"]);

        if (!isset($allowedMimes[$mime])) {
            Response::json([
                "status" => false,
                "message" => "Invalid image type"
            ], 400);
        }

        $ext = $allowedMimes[$mime];
        $filename = bin2hex(random_bytes(16)) . "." . $ext;

        $timestamp = time();

        $paramToSign = "folder={$folder}&timestamp={$timestamp}";
        $cloudName = getenv("CLOUDINARY_CLOUD_NAME") == "" ? $_ENV["CLOUDINARY_CLOUD_NAME"] : getenv("CLOUDINARY_CLOUD_NAME");
        $cloudSecret = getenv("CLOUDINARY_API_SECRET") == "" ? $_ENV["CLOUDINARY_API_SECRET"] : getenv("CLOUDINARY_API_SECRET");
        $apiKey = getenv("CLOUDINARY_API_KEY") == "" ? $_ENV["CLOUDINARY_API_KEY"] : getenv("CLOUDINARY_API_KEY");

        $signature = sha1($paramToSign . $cloudSecret);

        $url = "https://api.cloudinary.com/v1_1/" . $cloudName . "/image/upload";

        $postFields = [
            "file" => new CURLFile($file["tmp_name"]),
            "api_key" => $apiKey,
            "timestamp" => $timestamp,
            "signature" => $signature,
            "folder" => $folder
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $postFields
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            Response::json([
                "status" => false,
                "message" => "Image upload failed - " . curl_error($ch)
            ], 500);
        }

        echo $response;

        $result = json_decode($response, true);

        // print_r($result);

        if (!isset($result["secure_url"])) {
            Response::json([
                "status" => false,
                "message" => "Image upload failed"
            ], 500);
        }

        return $result;
    }
}