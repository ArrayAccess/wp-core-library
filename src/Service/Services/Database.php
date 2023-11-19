<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Database\Connection;
use ArrayAccess\WP\Libraries\Core\Database\WPDBConnectionParams;
use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\DatabaseInterface;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;

/**
 * The service that handle database connection with Doctrine Database Abstraction Layer.
 *
 * @mixin Connection
 */
final class Database extends AbstractService implements DatabaseInterface
{
    protected string $serviceName = 'database';

    /**
     * The connection.
     *
     * @var Connection
     */
    private Connection $connection;

    /**
     * @inheritdoc
     */
    protected function onConstruct(): void
    {
        $this->description = __(
            'The service that handle database connection with Doctrine Database Abstraction Layer.',
            'arrayaccess'
        );
    }

    /**
     * @return Connection
     */
    public function getConnection() : Connection
    {
        if (isset($this->connection)) {
            return $this->connection;
        }
        return $this->connection = Connection::createFromWPDBConnectionParams(
            $GLOBALS['wpdb']
        );
    }

    /**
     * @throws Exception
     */
    public function query(
        string $sql,
        array $params = [],
        $types = []
    ): Result {
        return $this->getConnection()->executeQuery(
            $sql,
            $params,
            $types
        );
    }

    /**
     * @throws Exception
     */
    public function prepare(string $sql): Statement
    {
        return $this->getConnection()->prepare(
            $sql
        );
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     *
     * @uses ConnectionInterface
     */
    public function __call(string $name, array $arguments)
    {
        return $this->getConnection()->$name(...$arguments);
    }
}
