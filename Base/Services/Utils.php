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
     * Securely upload a file with strict validation and sanitization.
     *
     * @param string $directory Directory to upload to (from root, e.g. '/uploads')
     * @param string $mode 'explicit' or 'dat-based' (date-based subfolders)
     * @param array|string $types Allowed extensions (array or '*' for all)
     * @param array $file_input The $_FILES[input] array
     * @param string $max_size Max file size (e.g. '20MB', '1024KB', '2GB', '500B')
     * @return array|string ['SUCCESS', $real_path] or error code string in CAPS
     */
    public static function secureUpload(
        string $directory,
        string $mode,
        $types,
        array $file_input,
        string $max_size
    ) {
        $root = realpath(__DIR__ . '/../../..');
        if (!$root) {
            return 'ROOT_NOT_FOUND';
        }
        $directory = '/' . ltrim($directory, '/\\');
        $target_dir = realpath($root . $directory);
        if ($target_dir === false) {
            $target_dir = $root . $directory;
            if (!@mkdir($target_dir, 0755, true)) {
                return 'UPLOAD_DIR_NOT_FOUND';
            }
        }
        if (strpos($target_dir, $root) !== 0) {
            return 'INVALID_UPLOAD_PATH';
        }

        if (!isset($file_input['error']) || is_array($file_input['error'])) {
            return 'INVALID_FILE_INPUT';
        }
        if ($file_input['error'] !== UPLOAD_ERR_OK) {
            $err_map = [
                UPLOAD_ERR_INI_SIZE => 'FILE_TOO_LARGE',
                UPLOAD_ERR_FORM_SIZE => 'FILE_TOO_LARGE',
                UPLOAD_ERR_PARTIAL => 'UPLOAD_INCOMPLETE',
                UPLOAD_ERR_NO_FILE => 'NO_FILE_UPLOADED',
                UPLOAD_ERR_NO_TMP_DIR => 'NO_TMP_DIR',
                UPLOAD_ERR_CANT_WRITE => 'CANT_WRITE',
                UPLOAD_ERR_EXTENSION => 'UPLOAD_EXTENSION_BLOCKED',
            ];
            return $err_map[$file_input['error']] ?? 'UPLOAD_ERROR';
        }
        if (!isset($file_input['tmp_name']) || !is_uploaded_file($file_input['tmp_name'])) {
            return 'INVALID_TMP_FILE';
        }

        $size_bytes = self::parseSize($max_size);
        if ($size_bytes === false) {
            return 'INVALID_MAX_SIZE';
        }
        $actual_size = filesize($file_input['tmp_name']);
        if ($actual_size === false || $actual_size > $size_bytes) {
            return 'FILE_TOO_LARGE';
        }

        $original_name = $file_input['name'];
        $basename = basename($original_name);
        $basename = str_replace("\0", '', $basename);
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $basename)) {
            return 'INVALID_FILENAME';
        }
        $parts = explode('.', $basename);
        if (count($parts) < 2) {
            return 'NO_EXTENSION';
        }
        $allowed_multi = ['tar.gz', 'tar.bz2', 'tar.xz'];
        $exts = array_map('strtolower', $parts);
        $final_ext = array_pop($exts);
        $multi_ext = $exts ? implode('.', $exts) . '.' . $final_ext : $final_ext;
        $ext = $final_ext;
        if (in_array($multi_ext, $allowed_multi)) {
            $ext = $multi_ext;
        } else {
            foreach ($exts as $intermediate) {
                if (preg_match('/^(php[0-9]?|phtml|phar|pl|py|sh|exe|bat|cmd|js|jsp|asp|aspx|cgi|dll|so|bin|htaccess)$/i', $intermediate)) {
                    return 'DANGEROUS_DOUBLE_EXTENSION';
                }
            }
        }
        if ($types !== '*' && !(is_array($types) && in_array($ext, $types, true))) {
            return 'EXTENSION_NOT_ALLOWED';
        }
        if ($mode === 'dat-based') {
            $date_path = date('Y/m/d');
            $target_dir = rtrim($target_dir, '/\\') . DIRECTORY_SEPARATOR . $date_path;
            if (!is_dir($target_dir) && !@mkdir($target_dir, 0755, true)) {
                return 'CANNOT_CREATE_DATE_DIR';
            }
        }
        $unique = bin2hex(random_bytes(8));
        $base_no_ext = implode('.', $parts);
        $safe_name = $unique . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $base_no_ext) . '.' . $ext;
        $dest_path = $target_dir . DIRECTORY_SEPARATOR . $safe_name;
        $real_dest = realpath(dirname($dest_path));
        if ($real_dest === false || strpos($real_dest, $root) !== 0) {
            return 'INVALID_FINAL_PATH';
        }
        if (!move_uploaded_file($file_input['tmp_name'], $dest_path)) {
            return 'MOVE_FAILED';
        }
        @chmod($dest_path, 0640);
        $public_path = str_replace($root, '', $dest_path);
        return ['SUCCESS', $public_path];
    }

    /**
     * Securely download a file from an allowed directory with strict validation.
     *
     * @param string $directory Allowed base directory (e.g., '/uploads')
     * @param string $filename The file to download (relative to $directory)
     * @param array $allowedTypes Whitelisted extensions (e.g., ['pdf','jpg','zip'])
     * @param string|null $downloadName Optional: name to present to the user
     * @return void (exits or throws on error)
     */
    public static function secureDownload(
        string $directory,
        string $filename,
        array $allowedTypes,
        string $downloadName = null
    ): void {
        // 1. Get allowed directories from .env
        $allowedDirs = getenv('DOWNLOAD_ALLOWED_DIRS');
        if (!$allowedDirs) {
            http_response_code(403);
            exit('DOWNLOAD_NOT_ALLOWED');
        }
        $allowedDirs = array_map('trim', explode(',', $allowedDirs));
        $root = realpath(__DIR__ . '/../../..');
        $directory = '/' . ltrim($directory, '/\\');
        $baseDir = realpath($root . $directory);
        if ($baseDir === false || !in_array($directory, $allowedDirs, true)) {
            http_response_code(403);
            exit('DOWNLOAD_DIR_NOT_ALLOWED');
        }
        // 2. Sanitize filename
        $filename = str_replace(["..", "\0", "/", "\\"], '', $filename);
        if ($filename === '' || strpos($filename, '..') !== false) {
            http_response_code(400);
            exit('INVALID_FILENAME');
        }
        $filePath = $baseDir . DIRECTORY_SEPARATOR . $filename;
        $realFile = realpath($filePath);
        if ($realFile === false || strpos($realFile, $baseDir) !== 0) {
            http_response_code(404);
            exit('FILE_NOT_FOUND');
        }
        // 3. Prohibit downloads from project root and /Base
        $forbiddenDirs = [
            $root,
            $root . DIRECTORY_SEPARATOR . 'Base',
        ];
        foreach ($forbiddenDirs as $forbiddenDir) {
            $forbiddenDirReal = realpath($forbiddenDir);
            if ($forbiddenDirReal && strpos($realFile, $forbiddenDirReal) === 0) {
                http_response_code(403);
                exit('FORBIDDEN_DIRECTORY');
            }
        }
        // 4. Extension whitelist only
        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes, true)) {
            http_response_code(403);
            exit('EXTENSION_NOT_ALLOWED');
        }
        if (!is_file($realFile) || !is_readable($realFile)) {
            http_response_code(404);
            exit('FILE_NOT_FOUND');
        }
        // 5. Set headers
        $downloadName = $downloadName ?: basename($realFile);
        $mime = mime_content_type($realFile) ?: 'application/octet-stream';
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"');
        header('Content-Length: ' . filesize($realFile));
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        // 6. Log download attempt here if needed
        // file_put_contents('/path/to/download.log', ...);
        // 7. Output file
        readfile($realFile);
        exit;
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

    /**
     * Parse human-readable size (e.g. 20MB, 1024KB, 2GB, 500B) to bytes.
     *
     * @param string $size
     * @return int|false
     */
    private static function parseSize($size)
    {
        if (is_numeric($size)) {
            return (int) $size;
        }
        if (!is_string($size)) {
            return false;
        }
        $size = trim($size);
        if (!preg_match('/^(\d+)(B|KB|MB|GB)$/i', $size, $m)) {
            return false;
        }
        $num = (int) $m[1];
        switch (strtoupper($m[2])) {
            case 'B':
                return $num;
            case 'KB':
                return $num * 1024;
            case 'MB':
                return $num * 1024 * 1024;
            case 'GB':
                return $num * 1024 * 1024 * 1024;
        }
        return false;
    }
} 