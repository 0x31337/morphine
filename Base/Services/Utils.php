<?php

declare(strict_types=1);

namespace Morphine\Base\Services;

use Morphine\Base\Renders\Render;
use Morphine\Base\Events\Pages;

/**
 * Utility class for various backend operations.
 */
class Utils
{
    /**
     * Render an AJAX conditional view response.
     *
     * @param array $req_data
     * @return void
     */
    public static function ajaxConditionalView(array $req_data): void
    {
        new Render('ajax', new Pages(), $req_data);
    }

    /**
     * Render a dynamic view and return it in the response.
     *
     * @param array $req_data
     * @return void
     */
    public static function dynamicView(array $req_data): void
    {
        new Render($req_data['view_name'], new Pages(), $req_data);
    }

    /**
     * Execute an Ajax Pure operation (no response expected).
     *
     * @param array $req_data
     * @return void
     */
    public static function ajpS1Action(array $req_data): void
    {
        switch ($req_data['action'] ?? null) {
            case 'exampleAction':
                // Call an Operation
                break;
        }
    }

    /**
     * Return a JSON HTTP response from a model.
     *
     * @param array $req_data
     * @return string JSON-encoded response
     */
    public static function jsonResponse(array $req_data): string
    {
        $response = [];
        switch ($req_data['request'] ?? null) {
            case 'exampleRequestParam':
                $response = [];
                break;
        }
        return json_encode($response, true);
    }

    /**
     * Return a raw HTTP response from a model.
     *
     * @param array $req_data
     * @return string Raw response
     */
    public static function rawResponse(array $req_data): string
    {
        switch (true) {
            case (isset($req_data['request']) && strpos($req_data['request'], 'exampleRequest') !== false):
                return '1';
        }
        return '';
    }

    /**
     * Compress and resize an image, saving to a new path.
     * Supports JPEG, PNG, GIF, and WebP.
     *
     * @param string $sourcePath Path to the source image
     * @param string $destPath Path to save the compressed image
     * @param int $maxWidth Maximum width of the output image
     * @param int $maxHeight Maximum height of the output image
     * @param int $quality Compression quality (0-100)
     * @return true|string True on success, or error code string
     */
    public static function compressImage(
        string $sourcePath,
        string $destPath,
        int $maxWidth = 300,
        int $maxHeight = 300,
        int $quality = 85
    ) {
        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            return 'SOURCE_NOT_FOUND';
        }
        $info = getimagesize($sourcePath);
        if ($info === false) {
            return 'NOT_AN_IMAGE';
        }
        [$width, $height, $type] = $info;
        $mime = $info['mime'];
        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        // Create image resource from source
        switch ($mime) {
            case 'image/jpeg':
                $srcImg = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $srcImg = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $srcImg = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $srcImg = imagecreatefromwebp($sourcePath);
                } else {
                    return 'WEBP_NOT_SUPPORTED';
                }
                break;
            default:
                return 'UNSUPPORTED_IMAGE_TYPE';
        }
        if (!$srcImg) {
            return 'IMAGE_CREATE_FAILED';
        }
        $dstImg = imagecreatetruecolor($newWidth, $newHeight);
        // Preserve transparency for PNG and GIF
        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagecolortransparent($dstImg, imagecolorallocatealpha($dstImg, 0, 0, 0, 127));
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
        }
        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        // Save the image
        $result = false;
        switch ($mime) {
            case 'image/jpeg':
                $result = imagejpeg($dstImg, $destPath, $quality);
                break;
            case 'image/png':
                // Convert quality to PNG scale (0-9, 0 = no compression)
                $pngQuality = (int)round((100 - $quality) / 10);
                $result = imagepng($dstImg, $destPath, $pngQuality);
                break;
            case 'image/gif':
                $result = imagegif($dstImg, $destPath);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    $result = imagewebp($dstImg, $destPath, $quality);
                } else {
                    $result = false;
                }
                break;
        }
        imagedestroy($srcImg);
        imagedestroy($dstImg);
        if (!$result) {
            return 'SAVE_FAILED';
        }
        return true;
    }
} 