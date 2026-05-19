<?php

namespace App\Core;

use PDO;
use PDOStatement;

class LoggedPDO extends PDO {
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null) {
        parent::__construct($dsn, $username, $password, $options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [LoggedPDOStatement::class]);
    }

    public function query(string $query, ?int $fetchMode = null, ...$fetchModeArgs): PDOStatement|false {
        $start = microtime(true);
        $result = parent::query($query, $fetchMode, ...$fetchModeArgs);
        DebugCollector::getInstance()->logQuery($query, [], microtime(true) - $start);
        return $result;
    }

    public function exec(string $statement): int|false {
        $start = microtime(true);
        $result = parent::exec($statement);
        DebugCollector::getInstance()->logQuery($statement, [], microtime(true) - $start);
        return $result;
    }
}

class LoggedPDOStatement extends PDOStatement {
    protected function __construct() {
        // Required for ATTR_STATEMENT_CLASS
    }

    public function execute(?array $params = null): bool {
        $start = microtime(true);
        $result = parent::execute($params);
        DebugCollector::getInstance()->logQuery($this->queryString, $params ?? [], microtime(true) - $start);
        return $result;
    }
}
