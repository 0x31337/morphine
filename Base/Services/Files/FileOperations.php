<?php

declare(strict_types=1);

namespace Morphine\Base\Services\Files;

/**
 * FileOperations: Industrial-grade, secure file manipulation utilities for Morphine.
 *
 * All operations are restricted to user-configured allowed directories.
 * All paths are sanitized, resolved, and validated to prevent traversal, symlink, and privilege attacks.
 * All methods are static and PSR-12 compliant.
 */
class FileOperations
{
    /**
     * Get allowed base directories from config.
     * @return array
     */
    private static function getAllowedDirs(): array
    {
        $config = @include(__DIR__ . '/../../../Application/config/files.php');
        return is_array($config['ALLOWED_DIRS'] ?? null) ? $config['ALLOWED_DIRS'] : [];
    }

    /**
     * Check if a path is within allowed directories and safe.
     * @param string $path
     * @return bool
     */
    private static function isAllowedPath(string $path): bool
    {
        $root = realpath(__DIR__ . '/../../..');
        $real = realpath($path) ?: realpath(dirname($path));
        if (!$real) return false;
        $allowed = self::getAllowedDirs();
        foreach ($allowed as $dir) {
            $allowedDir = realpath($root . $dir);
            if ($allowedDir && strpos($real, $allowedDir) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sanitize and validate a file path (no traversal, null bytes, absolute escapes).
     * @param string $path
     * @return bool
     */
    private static function isSafePath(string $path): bool
    {
        if (strpos($path, "..") !== false || strpos($path, "\0") !== false) return false;
        if (preg_match('#(^|/|\\)\.\.(?:/|\\|$)#', $path)) return false;
        if (preg_match('#[<>|?*]#', $path)) return false;
        return true;
    }

    /**
     * Create a new file with given contents. Fails if file exists.
     * @param string $path
     * @param string $contents
     * @return true|string True on success, or error code string
     */
    public static function create(string $path, string $contents = '')
    {
        if (!self::isSafePath($path)) return 'FORBIDDEN_PATH';
        if (!self::isAllowedPath($path)) return 'NOT_ALLOWED';
        if (file_exists($path)) {
            return 'FILE_EXISTS';
        }
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return 'CANNOT_CREATE_DIR';
        }
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            return 'WRITE_FAILED';
        }
        @chmod($path, 0640);
        return true;
    }

    /**
     * Rename a file or directory.
     * @param string $oldPath
     * @param string $newPath
     * @return true|string True on success, or error code string
     */
    public static function rename(string $oldPath, string $newPath)
    {
        if (!self::isSafePath($oldPath) || !self::isSafePath($newPath)) return 'FORBIDDEN_PATH';
        if (!self::isAllowedPath($oldPath) || !self::isAllowedPath($newPath)) return 'NOT_ALLOWED';
        if (!file_exists($oldPath)) {
            return 'SOURCE_NOT_FOUND';
        }
        $dir = dirname($newPath);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return 'CANNOT_CREATE_DIR';
        }
        if (!@rename($oldPath, $newPath)) {
            return 'RENAME_FAILED';
        }
        return true;
    }

    /**
     * Delete a file or directory (recursively if directory).
     * @param string $path
     * @return true|string True on success, or error code string
     */
    public static function delete(string $path)
    {
        if (!self::isSafePath($path)) return 'FORBIDDEN_PATH';
        if (!self::isAllowedPath($path)) return 'NOT_ALLOWED';
        if (!file_exists($path)) {
            return 'NOT_FOUND';
        }
        if (is_link($path)) {
            return 'FORBIDDEN_SYMLINK';
        }
        if (is_file($path)) {
            return @unlink($path) ? true : 'DELETE_FAILED';
        }
        if (is_dir($path)) {
            $items = scandir($path);
            if ($items === false) {
                return 'READ_DIR_FAILED';
            }
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $res = self::delete($path . DIRECTORY_SEPARATOR . $item);
                if ($res !== true) {
                    return $res;
                }
            }
            return @rmdir($path) ? true : 'DELETE_DIR_FAILED';
        }
        return 'UNKNOWN_TYPE';
    }

    /**
     * Copy a file or directory (recursively if directory).
     * @param string $source
     * @param string $dest
     * @return true|string True on success, or error code string
     */
    public static function copy(string $source, string $dest)
    {
        if (!self::isSafePath($source) || !self::isSafePath($dest)) return 'FORBIDDEN_PATH';
        if (!self::isAllowedPath($source) || !self::isAllowedPath($dest)) return 'NOT_ALLOWED';
        if (!file_exists($source)) {
            return 'SOURCE_NOT_FOUND';
        }
        if (is_link($source)) {
            return 'FORBIDDEN_SYMLINK';
        }
        if (is_file($source)) {
            $dir = dirname($dest);
            if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
                return 'CANNOT_CREATE_DIR';
            }
            return @copy($source, $dest) ? true : 'COPY_FAILED';
        }
        if (is_dir($source)) {
            if (!is_dir($dest) && !@mkdir($dest, 0755, true)) {
                return 'CANNOT_CREATE_DIR';
            }
            $items = scandir($source);
            if ($items === false) {
                return 'READ_DIR_FAILED';
            }
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $res = self::copy($source . DIRECTORY_SEPARATOR . $item, $dest . DIRECTORY_SEPARATOR . $item);
                if ($res !== true) {
                    return $res;
                }
            }
            return true;
        }
        return 'UNKNOWN_TYPE';
    }

    /**
     * Move a file or directory (recursively if directory).
     * @param string $source
     * @param string $dest
     * @return true|string True on success, or error code string
     */
    public static function move(string $source, string $dest)
    {
        $res = self::copy($source, $dest);
        if ($res !== true) {
            return $res;
        }
        $res = self::delete($source);
        if ($res !== true) {
            return $res;
        }
        return true;
    }

    /**
     * Upsert: Update file if exists, otherwise create it.
     * @param string $path
     * @param string $contents
     * @return true|string True on success, or error code string
     */
    public static function upsert(string $path, string $contents)
    {
        if (!self::isSafePath($path)) return 'FORBIDDEN_PATH';
        if (!self::isAllowedPath($path)) return 'NOT_ALLOWED';
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return 'CANNOT_CREATE_DIR';
        }
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            return 'WRITE_FAILED';
        }
        @chmod($path, 0640);
        return true;
    }
} 