<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Database\Driver;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Mysqli\Connection;
use mysqli;
use wpdb;

class MySQLiDriver extends AbstractMySQLDriver
{
    protected mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * @inheritdoc
     */
    public function connect(array $params): Connection
    {
        return new Connection($this->mysqli);
    }
}
