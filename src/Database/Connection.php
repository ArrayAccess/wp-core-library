<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Database;

use ArrayAccess\WP\Libraries\Core\Database\Cache\WPCache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;

/**
 * Doctrine Connection implementation support for WordPress
 */
final class Connection extends \Doctrine\DBAL\Connection
{
    /**
     * @var string $prefix table prefix
     */
    protected string $prefix;

    /**
     * @var DatabaseComparator $comparator database comparator
     */
    protected DatabaseComparator $comparator;

    /**
     * @var array<string, string> $types doctrine types
     */
    public const TYPES = [
        'varchar' => Types\Varchar::class,
    ];

    /**
     * Connection constructor.
     *
     * @param Driver $driver doctrine driver
     * @param string $prefix table prefix
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function __construct(Driver $driver, string $prefix = '')
    {
        foreach (self::TYPES as $type => $class) {
            if (!Type::hasType($type)) {
                /** @noinspection PhpUnhandledExceptionInspection */
                Type::addType($type, $class);
            }
        }

        // let use default
        /** @noinspection PhpUnhandledExceptionInspection */
        parent::__construct([], $driver);
        $this->prefix = $prefix;
        $this->getConfiguration()->setResultCache(CacheAdapter::wrap(new WPCache()));
    }

    /**
     * @return DatabaseComparator
     */
    public function getComparator() : DatabaseComparator
    {
        return $this->comparator ??= new DatabaseComparator($this);
    }

    /**
     * Table prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Create schema from definitions
     *
     * @param array $definitions
     * @param bool $usePrefix
     * @return Schema|SchemaException|Exception
     */
    public function createSchemaWithDefinitions(
        array $definitions,
        bool $usePrefix = true
    ): Schema|SchemaException|Exception {
        return $this->getComparator()->createSchemaFromDefinitions($definitions, $usePrefix);
    }

    /**
     * Compare schema with definitions
     *
     * @param array $definitions
     * @param bool $usePrefix
     * @return SchemaException|SchemaDiff|Exception SchemaDiff if there is difference,
     *  SchemaException if there is error, Exception if there is exception
     */
    public function compareSchemaWithDefinitions(
        array $definitions,
        bool $usePrefix = true
    ): SchemaException|SchemaDiff|Exception {
        return $this->getComparator()->compareSchemaWithDefinitions($definitions, $usePrefix);
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
            $WPDBConnectionParams->getDriverObject(),
            $WPDBConnectionParams->getPrefix()
        );
    }
}
