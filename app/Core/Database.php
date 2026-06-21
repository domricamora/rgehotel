<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Thin PDO/SQLite wrapper with a few query conveniences.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct(string $path)
    {
        $dsn = 'sqlite:' . $path;
        $this->pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Pragmas for integrity + concurrency.
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec('PRAGMA journal_mode = WAL');
        $this->pdo->exec('PRAGMA busy_timeout = 5000');
    }

    public static function instance(): Database
    {
        if (self::$instance === null) {
            $cfg = require dirname(__DIR__, 2) . '/config/config.php';
            $path = $cfg['db']['path'];
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            self::$instance = new self($path);
        }
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** Run a query with bound params, return the statement. */
    public function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch all rows. */
    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /** Fetch a single row or null. */
    public function first(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Fetch a single scalar value. */
    public function scalar(string $sql, array $params = [])
    {
        return $this->run($sql, $params)->fetchColumn();
    }

    /** Insert a row into $table from an associative array, return last insert id. */
    public function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $cols),
            implode(', ', $placeholders)
        );
        $this->run($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    /** Update rows in $table from associative $data where $where (assoc, AND). */
    public function update(string $table, array $data, array $where): int
    {
        $set = implode(', ', array_map(fn($c) => "$c = :set_$c", array_keys($data)));
        $cond = implode(' AND ', array_map(fn($c) => "$c = :w_$c", array_keys($where)));
        $params = [];
        foreach ($data as $k => $v) { $params["set_$k"] = $v; }
        foreach ($where as $k => $v) { $params["w_$k"] = $v; }
        $sql = "UPDATE $table SET $set WHERE $cond";
        return $this->run($sql, $params)->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        $cond = implode(' AND ', array_map(fn($c) => "$c = :$c", array_keys($where)));
        return $this->run("DELETE FROM $table WHERE $cond", $where)->rowCount();
    }

    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void { $this->pdo->commit(); }
    public function rollBack(): void { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); }
}
