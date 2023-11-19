<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Database;

use ArrayAccess\WP\Libraries\Core\Database\Cache\WPCache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\DriverManager;

final class Connection extends \Doctrine\DBAL\Connection
{
    protected string $prefix;

    /**
     * @param Driver $driver
     * @param string $prefix
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function __construct(Driver $driver, string $prefix = '')
    {
        // let use default
        /** @noinspection PhpUnhandledExceptionInspection */
        parent::__construct([], $driver);
        $this->prefix = $prefix;
        $this->getConfiguration()->setResultCache(CacheAdapter::wrap(new WPCache()));
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Create new Object connection from wpdb connection params
     *
     * @param WPDBConnectionParams $WPDBConnectionParams
     * @return Connection
     * @noinspection PhpDocMissingThrowsInspection
     */
    public static function createFromWPDBConnectionParams(
        WPDBConnectionParams $WPDBConnectionParams
    ) : Connection {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new self(
            DriverManager::getConnection($WPDBConnectionParams->getMySQLiParams()),
            $WPDBConnectionParams->getPrefix()
        );
    }
}
