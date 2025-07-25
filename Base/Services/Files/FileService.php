<?php

declare(strict_types=1);

namespace Morphine\Base\Services\Files;

/**
 * FileService: Secure file upload and download utilities for Morphine.
 *
 * Usage: Use FileService::secureUpload and FileService::secureDownload instead of Utils.
 *
 * Methods are static and PSR-12 compliant.
 */
class FileService
{
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
        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes, true)) {
            http_response_code(403);
            exit('EXTENSION_NOT_ALLOWED');
        }
        if (!is_file($realFile) || !is_readable($realFile)) {
            http_response_code(404);
            exit('FILE_NOT_FOUND');
        }
        $downloadName = $downloadName ?: basename($realFile);
        $mime = mime_content_type($realFile) ?: 'application/octet-stream';
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"');
        header('Content-Length: ' . filesize($realFile));
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        readfile($realFile);
        exit;
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