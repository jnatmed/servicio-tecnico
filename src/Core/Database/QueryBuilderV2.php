<?php 

namespace Paw\Core\Database;

use PDO;
use Monolog\Logger;
use Exception;
use PDOException;

class QueryBuilderV2
{
    protected PDO $pdo;
    protected Logger $logger;

    protected $select = '*';
    protected $from;
    protected $joins = [];
    protected $where = [];
    protected $bindings = [];

    public function __construct(PDO $pdo, ?Logger $logger = null)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function select(string $columns)
    {
        $this->select = $columns;
        return $this;
    }

    public function from(string $table, string $alias = '')
    {
        $this->from = trim($table . ' ' . $alias);
        return $this;
    }

    public function join(string $table, string $on)
    {
        $this->joins[] = "JOIN $table ON $on";
        return $this;
    }

    public function where(string $condition)
    {
        $this->where[] = $condition;
        return $this;
    }

    public function bind(string $key, $value)
    {
        $this->bindings[$key] = $value;
        return $this;
    }

    public function get(): array
    {
        $sql = "SELECT {$this->select} FROM {$this->from}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($this->bindings as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();

        $this->logger?->info("Ejecutando SQL V2", ['sql' => $sql, 'bindings' => $this->bindings]);

        return $stmt->fetchAll();
    }
}
