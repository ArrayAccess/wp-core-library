<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Database\Driver;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\PDO\Connection;
use PDO;
use function sprintf;
use function str_starts_with;

/**
 * Driver PDO connection for doctrine
 */
class PDOMySQLDriver extends AbstractMySQLDriver
{
    /**
     * @param PDO $pdo
     */
    public function __construct(private PDO $pdo)
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (!str_starts_with($driver, 'mysql')) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s only support mysql driver',
                    __CLASS__
                )
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function connect(array $params): Connection
    {
        return new Connection($this->pdo);
    }
}
