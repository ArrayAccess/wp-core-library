<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Interfaces;

use ArrayAccess\WP\Libraries\Core\Database\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;

interface DatabaseInterface extends ServiceInterface
{
    /**
     * Get Connection
     *
     * @return Connection
     */
    public function getConnection() : Connection;

    /**
     * @param string $sql
     * @return Result
     */
    public function query(string $sql) : Result;

    /**
     * @param string $sql
     * @return Statement
     */
    public function prepare(string $sql) : Statement;
}
