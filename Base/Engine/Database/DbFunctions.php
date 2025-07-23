<?php

declare(strict_types=1);

namespace Morphine\Base\Engine\Database;

use mysqli;
use mysqli_stmt;
use Exception;

class DbFunctions
{
    /**
     * Update function for database records.
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public static function updateFunc(array $data, mysqli $jasDB): string
    {
        $columns = $values = $whereCol = $whereVal = [];
        $types = '';
        $table = '';

        foreach ($data as $key => $value) {
            if ($key === 'table') {
                $table = self::sanitizeIdentifier($value);
                continue;
            }
            if ($key === 'setColumn') {
                $columns = is_array($value) ? array_map('Morphine\\Base\\Engine\\Database\\sanitizeIdentifier', $value) : [self::sanitizeIdentifier($value)];
                continue;
            }
            if ($key === 'setValue') {
                $values = is_array($value) ? $value : [$value];
                continue;
            }
            if ($key === 'where') {
                foreach ($value as $colname => $colval) {
                    $whereCol[] = self::sanitizeIdentifier($colname);
                    $whereVal[] = $colval;
                }
            }
        }

        if (count($columns) !== count($values)) {
            throw new \InvalidArgumentException('Mismatch between setColumn and setValue.');
        }

        $setClause = implode(', ', array_map(fn($col) => "$col = ?", $columns));
        $types .= implode('', array_map('Morphine\\Base\\Engine\\Database\\getBindType', $values));
        $params = $values;

        $whereClause = implode(' AND ', array_map(fn($col) => "$col = ?", $whereCol));
        $types .= implode('', array_map('Morphine\\Base\\Engine\\Database\\getBindType', $whereVal));
        $params = array_merge($params, $whereVal);

        $sql = "UPDATE $table SET $setClause WHERE $whereClause";
        $stmt = $jasDB->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException("Statement preparation failed: " . $jasDB->error);
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new \RuntimeException("Statement execution failed: " . $stmt->error);
        }
        $affected = $stmt->affected_rows;
        $stmt->close();
        return "Updated $affected row(s).";
    }

    /**
     * Sanitize SQL identifier (table/column name).
     */
    public static function sanitizeIdentifier(string $value): string
    {
        if (trim($value) === '*') {
            return '*';
        }
        $parts = explode(',', $value);
        $sanitized = [];
        foreach ($parts as $part) {
            $subparts = explode('.', trim($part));
            foreach ($subparts as $sub) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $sub)) {
                    throw new \InvalidArgumentException("Invalid identifier detected: $value");
                }
            }
            $sanitized[] = implode('.', array_map('trim', $subparts));
        }
        return implode(', ', $sanitized);
    }

    /**
     * Get the bind type for mysqli prepared statements.
     */
    public static function getBindType($var): string
    {
        if (is_int($var)) return 'i';
        if (is_float($var)) return 'd';
        return 's';
    }

    /**
     * Sanitize data for insert.
     */
    public static function sanitizeInsertData(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if ($key === 'table') {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
                    throw new \InvalidArgumentException("Invalid table name: $value");
                }
                $clean['table'] = $value;
            } else {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                    throw new \InvalidArgumentException("Invalid column name: $key");
                }
                $clean[$key] = $value;
            }
        }
        return $clean;
    }

    /**
     * Securely build a WHERE clause for SQL queries.
     */
    public static function secureWhere(array $data, bool $isOR = false, bool $returnStructured = false): array|string
    {
        $operator = $isOR ? ' OR ' : ' AND ';
        $sqlParts = [];
        $params = [];
        $types = '';
        foreach ($data as $key => $value) {
            $column = self::sanitizeIdentifier($key);
            if (is_array($value)) {
                $sqlParts[] = "`$column` IN (" . implode(',', array_fill(0, count($value), '?')) . ")";
                foreach ($value as $val) {
                    $params[] = $val;
                    $types .= self::getBindType($val);
                }
            } else {
                $sqlParts[] = "`$column` = ?";
                $params[] = $value;
                $types .= self::getBindType($value);
            }
        }
        $sql = implode($operator, $sqlParts);
        if ($returnStructured) {
            return [
                'sql' => $sql,
                'params' => $params,
                'types' => $types
            ];
        }
        return $sql;
    }

    /**
     * Insert function for database records.
     */
    public static function insertFunc(mysqli $db, array $data): string
    {
        $columns = [];
        $placeholders = [];
        $types = '';
        $params = [];
        $table = '';
        foreach ($data as $key => $value) {
            if ($key === 'table') {
                $table = $value;
                continue;
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                throw new \InvalidArgumentException("Invalid column name '$key'");
            }
            $columns[] = "`$key`";
            $placeholders[] = '?';
            $types .= self::getBindType($value);
            $params[] = $value;
        }
        if (empty($table) || empty($columns) || empty($params)) {
            throw new \InvalidArgumentException('Empty table or data');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException("Invalid table name '$table'");
        }
        $sql = "INSERT INTO `$table` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException("Prepare failed: " . $db->error);
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new \RuntimeException("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        return "Data inserted successfully.";
    }

    /**
     * Batch insert function for database records.
     */
    public static function batchInsertFunc(array $data, mysqli $jasDB): bool
    {
        if (!isset($data['table'], $data['columns'], $data['values'], $data['types'])) {
            throw new \InvalidArgumentException('Missing required keys (table, columns, values, types)');
        }
        $table = '`' . self::sanitizeIdentifier($data['table']) . '`';
        $columns = array_map(function ($col) {
            return '`' . self::sanitizeIdentifier(trim($col)) . '`';
        }, explode('|', $data['columns']));
        $types = $data['types'];
        if (!preg_match('/^[sifb]*$/', $types)) {
            throw new \InvalidArgumentException('Invalid types string (only s, i, f, b allowed)');
        }
        $values = $data['values'];
        if (!is_array($values) || empty($values)) {
            throw new \InvalidArgumentException('Values must be a non-empty array');
        }
        foreach ($values as $index => $valueSet) {
            if (!is_array($valueSet)) {
                throw new \InvalidArgumentException("Each value set must be an array");
            }
            if (array_values($valueSet) !== $valueSet) {
                throw new \InvalidArgumentException("Value set at index $index must be a numerically indexed array (not associative)");
            }
            if (count($valueSet) !== count($columns)) {
                throw new \InvalidArgumentException("Value set at index $index does not match column count");
            }
        }
        $columnsStr = implode(', ', $columns);
        $placeholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $placeholders = implode(', ', array_fill(0, count($values), $placeholder));
        $sql = "INSERT INTO $table ($columnsStr) VALUES $placeholders";
        $typesStr = str_repeat($types, count($values));
        $flatValues = [];
        foreach ($values as $valueSet) {
            foreach ($valueSet as $val) {
                $flatValues[] = $val;
            }
        }
        $stmt = $jasDB->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException('SQL Error: ' . $jasDB->error);
        }
        $stmt->bind_param($typesStr, ...$flatValues);
        if (!$stmt->execute()) {
            throw new \RuntimeException('Execution Error: ' . $stmt->error);
        }
        $stmt->close();
        return true;
    }

    /**
     * Delete function for database records.
     */
    public static function deleteFunc(array $data, mysqli $jasDB): int
    {
        if (!isset($data['table']) || !is_string($data['table'])) {
            throw new \InvalidArgumentException('Table name not defined or invalid.');
        }
        $table = $data['table'];
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException('Invalid table name.');
        }
        if (!isset($data['where']) || !is_array($data['where'])) {
            throw new \InvalidArgumentException("'where' element must be an array.");
        }
        $columns = [];
        $values = [];
        $types = '';
        foreach ($data['where'] as $column => $value) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                throw new \InvalidArgumentException('Invalid column name.');
            }
            $columns[] = "`$column` = ?";
            $values[] = $value;
            $types .= self::getBindType($value);
        }
        $whereClause = implode(' AND ', $columns);
        $sql = "DELETE FROM `$table` WHERE $whereClause";
        $stmt = $jasDB->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException('Prepare failed: ' . $jasDB->error);
        }
        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new \RuntimeException('SQL Error: ' . $err);
        }
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    /**
     * Sort WHERE clause for SQL queries.
     */
    public static function whereSort(array $where, bool $isOR): string
    {
        $wh = '';
        $p = 0;
        $d = count($where);
        foreach ($where as $cl => $vl) {
            $wh .= "$cl='" . addslashes((string)$vl) . "'";
            $p++;
            if ($p > 0 && $p != $d) {
                $wh .= $isOR ? ' OR ' : ' AND ';
            }
        }
        return $wh;
    }

    /**
     * Copy data between tables.
     */
    public static function copyData(array $data, object $parent, bool $updateTarget = false): int
    {
        $conn = $parent::$jasDB;
        if (!isset($data['tables']) || !is_array($data['tables'])) {
            throw new \InvalidArgumentException('"tables" key must be an array');
        }
        foreach ($data['tables'] as $from => $to) {
            $fromTable = $from;
            $toTable = $to;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $fromTable) || !preg_match('/^[a-zA-Z0-9_]+$/', $toTable)) {
            throw new \InvalidArgumentException('Invalid table names');
        }
        if (!isset($data['columns']) || !is_array($data['columns'])) {
            throw new \InvalidArgumentException('"columns" key must be an array');
        }
        $fromCol = [];
        $toCol = [];
        $whereConditions = [];
        $whereParams = [];
        foreach ($data['columns'] as $key => $val) {
            if (strtolower($key) === 'where') {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        if (!preg_match('/^[a-zA-Z0-9_]+$/', $k)) {
                            throw new \InvalidArgumentException('Invalid WHERE column name');
                        }
                        $whereConditions[] = "`$k` = ?";
                        $whereParams[] = $v;
                    }
                } elseif (is_string($val)) {
                    $parts = explode('=', $val);
                    if (count($parts) == 2 && preg_match('/^[a-zA-Z0-9_]+$/', $parts[0])) {
                        $whereConditions[] = "`{$parts[0]}` = ?";
                        $whereParams[] = trim($parts[1]);
                    } else {
                        throw new \InvalidArgumentException('Invalid WHERE condition');
                    }
                }
            } else {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $key) || !preg_match('/^[a-zA-Z0-9_]+$/', $val)) {
                    throw new \InvalidArgumentException('Invalid column name in mapping');
                }
                $fromCol[] = $key;
                $toCol[] = $val;
            }
        }
        if (count($fromCol) !== count($toCol)) {
            throw new \InvalidArgumentException('Source and target column count mismatch');
        }
        $selectFields = implode(", ", array_map(fn($col) => "`$col`", $fromCol));
        $whereClause = $whereConditions ? (" WHERE " . implode(" AND ", $whereConditions)) : "";
        $selectSql = "SELECT $selectFields FROM `$fromTable`$whereClause";
        $stmt = mysqli_prepare($conn, $selectSql);
        if (!$stmt) {
            throw new \RuntimeException('Error preparing SELECT: ' . mysqli_error($conn));
        }
        if (!empty($whereParams)) {
            $types = str_repeat("s", count($whereParams));
            mysqli_stmt_bind_param($stmt, $types, ...$whereParams);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            throw new \RuntimeException('Error executing SELECT: ' . mysqli_error($conn));
        }
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        if (!$rows) {
            throw new \RuntimeException('No matching data found');
        }
        if (!$updateTarget) {
            $insertSql = "INSERT INTO `$toTable` (" . implode(", ", array_map(fn($c) => "`$c`", $toCol)) . ") VALUES ";
            $placeholders = "(" . rtrim(str_repeat("?,", count($toCol)), ",") . ")";
            $insertSql .= implode(", ", array_fill(0, count($rows), $placeholders));
            $flatValues = [];
            foreach ($rows as $row) {
                foreach ($fromCol as $col) {
                    $flatValues[] = $row[$col];
                }
            }
            $stmt = mysqli_prepare($conn, $insertSql);
            if (!$stmt) {
                throw new \RuntimeException('Error preparing INSERT: ' . mysqli_error($conn));
            }
            $types = str_repeat("s", count($flatValues));
            mysqli_stmt_bind_param($stmt, $types, ...$flatValues);
            if (!mysqli_stmt_execute($stmt)) {
                throw new \RuntimeException('Error executing INSERT: ' . mysqli_stmt_error($stmt));
            }
            return 1;
        } else {
            if (!isset($data['target']) || !is_array($data['target'])) {
                throw new \InvalidArgumentException('UPDATE target condition missing');
            }
            $setClause = implode(" = ?, ", array_map(fn($c) => "`$c`", $toCol)) . " = ?";
            $targetClause = implode(" = ? AND ", array_map(fn($k) => "`$k`", array_keys($data['target']))) . " = ?";
            $updateSql = "UPDATE `$toTable` SET $setClause WHERE $targetClause";
            $stmt = mysqli_prepare($conn, $updateSql);
            if (!$stmt) {
                throw new \RuntimeException('Error preparing UPDATE: ' . mysqli_error($conn));
            }
            $updateValues = [];
            foreach ($rows[0] as $v) $updateValues[] = $v;
            foreach ($data['target'] as $v) $updateValues[] = $v;
            $types = str_repeat("s", count($updateValues));
            mysqli_stmt_bind_param($stmt, $types, ...$updateValues);
            if (!mysqli_stmt_execute($stmt)) {
                throw new \RuntimeException('Error executing UPDATE: ' . mysqli_stmt_error($stmt));
            }
            return 1;
        }
    }

    /**
     * Move data between tables.
     */
    public static function moveData(array $data): array
    {
        $mysqli = Database::$jasDB;
        if (!isset($data['from'], $data['to'], $data['columns'], $data['where'])) {
            throw new \InvalidArgumentException('Missing required keys (from, to, columns, where)');
        }
        $sanitizeIdentifier = function ($input) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $input)) {
                throw new Exception("Invalid identifier: $input");
            }
            return $input;
        };
        $fromTable = $sanitizeIdentifier($data['from']);
        $toTable = $sanitizeIdentifier($data['to']);
        $columns = explode('|', $data['columns']);
        $sanitizedCols = [];
        foreach ($columns as $col) {
            $sanitizedCols[] = $sanitizeIdentifier($col);
        }
        $where = $data['where'];
        if (!is_array($where)) {
            throw new \InvalidArgumentException('Invalid "where" clause, must be an array');
        }
        $whereClause = implode(' AND ', array_map(function ($col) use ($sanitizeIdentifier) {
            return "`" . $sanitizeIdentifier($col) . "` = ?";
        }, array_keys($where)));
        $whereValues = array_values($where);
        $whereTypes = str_repeat('s', count($whereValues));
        $selectSQL = "SELECT " . implode(', ', array_map(fn($c) => "`$c`", $sanitizedCols)) . " FROM `$fromTable` WHERE $whereClause";
        $stmt = $mysqli->prepare($selectSQL);
        if (!$stmt) throw new \RuntimeException('Prepare failed (SELECT): ' . $mysqli->error);
        $stmt->bind_param($whereTypes, ...$whereValues);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result || $result->num_rows === 0) throw new \RuntimeException('No matching data found');
        $row = $result->fetch_assoc();
        $stmt->close();
        $placeholders = implode(', ', array_fill(0, count($sanitizedCols), '?'));
        $colList = implode(', ', array_map(fn($c) => "`$c`", $sanitizedCols));
        $insertSQL = "INSERT INTO `$toTable` ($colList) VALUES ($placeholders)";
        $stmt = $mysqli->prepare($insertSQL);
        if (!$stmt) throw new \RuntimeException('Prepare failed (INSERT): ' . $mysqli->error);
        $insertTypes = str_repeat('s', count($row));
        $insertValues = array_values($row);
        $stmt->bind_param($insertTypes, ...$insertValues);
        if (!$stmt->execute()) throw new \RuntimeException('Execution failed (INSERT): ' . $stmt->error);
        $stmt->close();
        $deleteSQL = "DELETE FROM `$fromTable` WHERE $whereClause";
        $stmt = $mysqli->prepare($deleteSQL);
        if (!$stmt) throw new \RuntimeException('Prepare failed (DELETE): ' . $mysqli->error);
        $stmt->bind_param($whereTypes, ...$whereValues);
        if (!$stmt->execute()) throw new \RuntimeException('Execution failed (DELETE): ' . $stmt->error);
        $stmt->close();
        return [true, true];
    }
}
