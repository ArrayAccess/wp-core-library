<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Database\Driver;

use Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Doctrine\DBAL\Driver\PDO\Connection;
use PDO;
use function sprintf;
use function str_starts_with;

class PDOSqliteDriver extends AbstractSQLiteDriver
{
    public function __construct(private PDO $pdo)
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (!str_starts_with($driver, 'sqlite')) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s only support Sqlite driver',
                    __CLASS__
                )
            );
        }
    }

    public function connect(array $params): Connection
    {
        return new Connection($this->pdo);
    }
}
