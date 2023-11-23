<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Database;

use ArrayAccess\WP\Libraries\Core\Database\Driver\MySQLiDriver;
use ArrayAccess\WP\Libraries\Core\Database\Driver\PDOMySQLDriver;
use ArrayAccess\WP\Libraries\Core\Database\Driver\PDOSqliteDriver;
use Doctrine\DBAL\Driver;
use mysqli;
use PDO;
use wpdb;
use function defined;
use function is_int;
use function is_numeric;
use function sprintf;
use const DB_HOST;
use const DB_NAME;
use const DB_PASSWORD;
use const DB_USER;

/**
 * WPDB object connection params
 */
final class WPDBConnectionParams
{
    private function __construct(
        protected string $driver,
        protected string $host,
        protected string $username,
        protected string $password,
        protected string $database,
        protected ?int $port,
        protected string $prefix,
        protected string $initCommand,
        protected ?string $socket,
        protected string $charset,
        protected string $collation,
        protected $dbh
    ) {
    }

    public function getPDOOptions(): array
    {
        $options = [
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_STRINGIFY_FETCHES => true,
            PDO::ATTR_TIMEOUT           => 5,
        ];
        $init = $this->getInitCommand();
        if ($init !== '') {
            // PDO::MYSQL_ATTR_INIT_COMMAND
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = $init;
        }
        return $options;
    }

    public function getPDOParams(): array
    {
        return [
            'driver' => $this->driver === 'mysqli' ? 'pdo_mysql' : 'pdo_sqlite',
            'host' => $this->getHost(),
            'path' => $this->getHost(),
            'user' => $this->getUsername(),
            'password' => $this->getPassword(),
            'dbname' => $this->getDatabase(),
            'port' => $this->getPort()?:3306,
            'charset' => $this->getCharset(),
            'collation' => $this->getCollation(),
            'unix_socket' => $this->getSocket(),
            'driverOptions' => $this->getPDOOptions(),
        ];
    }

    public function getMySQLiParams(): array
    {
        $client_flags = defined('MYSQL_CLIENT_FLAGS') ? \MYSQL_CLIENT_FLAGS : 0;
        if (!is_int($client_flags)) {
            $client_flags = 0;
        }
        return [
            'driver' => 'mysqli',
            'host' => $this->driver === 'pdo_sqlite' ? DB_HOST : $this->getHost(),
            'user' => $this->getUsername()?:DB_USER,
            'password' => $this->getPassword()?:DB_PASSWORD,
            'dbname' => $this->getDatabase()?:DB_NAME,
            'port' => $this->getPort()?:3306,
            'unix_socket' => $this->getSocket(),
            'charset' => $this->getCharset(),
            'collation' => $this->getCollation(),
            'driverOptions' => [
                'flags' => $client_flags,
            ],
        ];
    }

    /**
     * @var Driver $driverObject Driver object
     */
    private Driver $driverObject;

    /**
     * @return Driver Driver object
     */
    public function getDriverObject() : Driver
    {
        if (isset($this->driverObject)) {
            return $this->driverObject;
        }
        if ($this->dbh instanceof PDO) {
            return $this->driverObject = $this->driver === 'pdo_sqlite'
                ? new PDOSqliteDriver($this->dbh)
                : new PDOMySQLDriver($this->dbh);
        }
        return $this->driverObject = new MySQLiDriver($this->dbh);
    }

    /**
     * Create from wpdb connection params
     *
     * @param wpdb|null $wpDB
     * @return WPDBConnectionParams
     */
    public static function create(?wpdb $wpDB = null): WPDBConnectionParams
    {
        if ($wpDB === null) {
            global $wpdb;
            $wpDB = $wpdb;
        }

        // ping the connection
        $wpDB->check_connection();
        $dbh = $wpDB->{'dbh'};
        $isMysqli = $dbh instanceof mysqli;
        // wp db support magic method
        $host = $wpDB->{'dbhost'};
        $hostData = $wpDB->parse_db_host($host);
        $port = null;
        $socket = null;
        if ($hostData) {
            $host = $hostData[0]?? $host;
            if (is_numeric($hostData[1])) {
                $port = (int) $hostData[1];
            }
            if ($hostData[3]) {
                $host = "[$host]";
            }
            $socket = $hostData[2]?:null;
        }

        $driver = 'mysqli';
        $init = '';
        if ($isMysqli) {
            /**
             * @see wpdb
             */
            if (!empty($wpDB->charset)) {
                $init .= sprintf("SET NAMES '%s'", $wpDB->charset);
                if ($wpDB->has_cap('collation') && !empty($wpDB->collate)) {
                    $init .= sprintf(" COLLATE '%s'", $wpDB->collate);
                }
            }
            $sqlMode = $wpDB->get_var('SELECT @@SESSION.sql_mode');
            if (!empty($sqlMode)) {
                $init .= sprintf("; SET SESSION sql_mode='%s'", $sqlMode);
            }
        } /** @noinspection PhpUndefinedClassInspection */ elseif ($dbh instanceof \WP_SQLite_Translator) {
            /**
             * Take from SQLite Database Integration Plugin
             * @link https://wordpress.org/plugins/sqlite-database-integration/
             */
            // for wp sqlite
            $driver = 'pdo_sqlite';
            $host = defined('FQDB') ? \FQDB : null;
            if ($host === null) {
                $dbDir = defined('FQDBDIR') ? \FQDBDIR : ABSPATH . 'wp-content/database/';
                $dbDir = rtrim($dbDir, '/') .'/';
                $host = defined('DB_FILE') ? $dbDir . \DB_FILE : $dbDir . '.ht.sqlite';
            }
            $init = 'PRAGMA encoding="UTF-8";PRAGMA foreign_keys = ON;PRAGMA journal_mode=WAL;';
        }

        return new self(
            $driver,
            $host,
            $wpDB->{'dbuser'},
            $wpDB->{'dbpassword'},
            $wpDB->{'dbname'},
            $port,
            $wpDB->prefix,
            $init,
            $socket,
            $wpDB->charset,
            $wpDB->collate,
            $dbh,
        );
    }

    /**
     * Database instance from wpdb
     */
    public function getDbh()
    {
        return $this->dbh;
    }

    /**
     * @return string Database charset
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @return string Database collation
     */
    public function getCollation(): string
    {
        return $this->collation;
    }

    /**
     * @return string Database host or path
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string Database username
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string Database password
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string Database name
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * @return ?int Database port
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @return string table prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return string init command
     */
    public function getInitCommand(): string
    {
        return $this->initCommand;
    }

    /**
     * @return ?string The mysql unix socket
     */
    public function getSocket(): ?string
    {
        return $this->socket;
    }
}
