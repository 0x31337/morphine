<?php

declare(strict_types=1);

namespace Morphine\Base\Engine\Database;

use Morphine\Base\Engine\Database\DbFunctions;
use Morphine\Base\Engine\Config;

// use statements for any external dependencies (if needed)

class Database
{
    private static string $morphDbHost = '127.0.0.1';
    private static string $morphDbName = 'morphine_app';
    private static string $morphDbUser = 'root';
    private static string $morphDbPassword = '';
    private static $jasDB;
    private static $select;

    public function __construct()
    {
        self::set();
    }

    public static function set(): void
    {
        $dbConfig = Config::get('database');
        self::$jasDB = mysqli_connect(
            $dbConfig['host'],
            $dbConfig['user'],
            $dbConfig['password']
        );
        if (!self::$jasDB) {
            throw new \RuntimeException(
                "Unable to connect to the database. Did you <i>install</i> Morphine? " .
                "Please visit <a href='https://github.com/0x31337/morphine/wiki/Getting-Started#-installation'>The User-friendly quickstart</a> to find out what's missing."
            );
        }
        mysqli_select_db(self::$jasDB, $dbConfig['name']);
        mysqli_query(self::$jasDB, "SET NAMES 'utf8'");
    }

    public static function close(): void
    {
        mysqli_close(self::$jasDB);
    }

    public function select(
        $column,
        $table,
        $where = false,
        $isOR = false,
        $offset = false,
        $limit = false,
        $order = false,
        $search = false
    ): string {
        try {
            $column = self::sanitizeIdentifiers($column);
            $table = self::sanitizeIdentifiers($table);

            if ($order === 'ASC') {
                $orderby = 'ORDER BY id ASC';
            } elseif (is_array($order) && isset($order['column'], $order['type'])) {
                $orderColumn = self::sanitizeIdentifiers($order['column']);
                $orderType = strtoupper($order['type']) === 'ASC' ? 'ASC' : 'DESC';
                $orderby = "ORDER BY $orderColumn $orderType";
            } else {
                $orderby = 'ORDER BY id DESC';
            }

            $query = "SELECT $column FROM $table";
            $params = [];
            $types = '';

            if (!$where) {
                if (is_array($search)) {
                    $i = 0;
                    foreach ($search as $key => $arr) {
                        $columnName = self::sanitizeIdentifiers($arr['column']);
                        $keyword = $arr['keyword'];
                        $type = (isset($arr['type']) && strtoupper($arr['type']) === 'OR') ? 'OR' : 'AND';

                        if ($i === 0) {
                            $query .= " WHERE `$columnName` LIKE ?";
                        } else {
                            $query .= " $type `$columnName` LIKE ?";
                        }

                        $params[] = '%' . $keyword . '%';
                        $types .= 's';
                        $i++;
                    }
                }
            } else {
                $secure = DbFunctions::secureWhere($where, $isOR, true);
                if (!is_array($secure) || !isset($secure['sql'], $secure['params'], $secure['types'])) {
                    throw new \InvalidArgumentException('Invalid format returned from secureWhere.');
                }
                $query .= ' WHERE ' . $secure['sql'];
                $params = array_merge($params, $secure['params']);
                $types .= $secure['types'];
            }

            $query .= " $orderby";

            if ($limit !== false) {
                $query .= ' LIMIT ?';
                $params[] = (int)$limit;
                $types .= 'i';

                if ($offset !== false) {
                    $query .= ' OFFSET ?';
                    $params[] = (int)$offset;
                    $types .= 'i';
                }
            }

            $stmt = mysqli_prepare(self::$jasDB, $query);
            if (!$stmt) {
                $error = mysqli_error(self::$jasDB);
                if (strpos($error, 'No database selected') !== false) {
                    echo "<div style='color: red; font-weight: bold;'><b>Morphine Framework:</b> No database selected.<br>Please <i>install</i> your application, or check the <a href='https://github.com/0x31337/morphine/wiki/01.-Getting-Started#-installation'>documentation</a> for setup instructions.</div>";
                    exit;
                } else {
                    echo "<div style='color: red; font-weight: bold;'>SQL Preparation failed: " . htmlspecialchars($error) . "</div>";
                    exit;
                }
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                $error = $stmt->error;
                echo "<div style='color: red; font-weight: bold;'>SQL Execution failed: " . htmlspecialchars($error) . "</div>";
                exit;
            }

            $result = $stmt->get_result();
            self::$select = $result;
            return $query;
        } catch (\Throwable $e) {
            echo "<div style='color: red; font-weight: bold;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            exit;
        }
    }

    public function getTotalRows(): int
    {
        return mysqli_num_rows(self::$select);
    }

    public function exists(): ?array
    {
        return mysqli_fetch_assoc(self::$select) ?: null;
    }

    public function insert($data)
    {
        return DbFunctions::insertFunc(self::$jasDB, DbFunctions::sanitizeInsertData($data));
    }

    public function batchInsert($data)
    {
        return DbFunctions::batchInsertFunc(self::$jasDB, $data);
    }

    public function update($data)
    {
        return DbFunctions::updateFunc($data, self::$jasDB);
    }

    public function __clone()
    {
        return new self();
    }

    public function delete($data)
    {
        return DbFunctions::deleteFunc($data, self::$jasDB);
    }

    public function copy($data, $updateTarget = false)
    {
        return DbFunctions::copyData($data, self::$jasDB, $updateTarget);
    }

    public function move($data)
    {
        return DbFunctions::moveData($data);
    }

    public function unsafeQuery(string $sql)
    {
        $mysqli = self::$jasDB;

        if (!$mysqli || !$mysqli instanceof \mysqli) {
            throw new \RuntimeException('Invalid database connection.');
        }

        $trimmedSql = trim($sql);
        if (empty($trimmedSql)) {
            throw new \InvalidArgumentException('SQL query is empty.');
        }

        if (preg_match('/;.*\S/', $trimmedSql)) {
            throw new \InvalidArgumentException('Multiple SQL statements are not allowed.');
        }

        $dangerous = ['--', '/*', '*/', 'xp_', 'exec(', 'exec ', 'sp_', 'declare '];
        foreach ($dangerous as $d) {
            if (stripos($trimmedSql, $d) !== false) {
                throw new \InvalidArgumentException('Potentially dangerous SQL content detected.');
            }
        }

        $result = $mysqli->query($trimmedSql);

        if ($result === false) {
            throw new \RuntimeException('SQL error: ' . $mysqli->error);
        }

        if ($result instanceof \mysqli_result) {
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $result->free();
            return $data;
        }

        return true;
    }

    private static function sanitizeIdentifiers($input): string
    {
        if (is_array($input)) {
            return implode(', ', array_map([__CLASS__, 'sanitizeIdentifier'], $input));
        }
        return DbFunctions::sanitizeIdentifier($input);
    }

    public function lastError(): string
    {
        return mysqli_error(self::$jasDB);
    }
}