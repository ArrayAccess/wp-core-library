<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Tests\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Services;
use ArrayAccess\WP\Libraries\Core\Service\Services\Database;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Unit test to test Database class
 */
class DatabaseTest extends TestCase
{
    protected Services $services;

    protected function setUp(): void
    {
        $this->services = new Services();
    }

    public function testGetConnection()
    {
        $database = $this->services->get(Database::class);
        $this->assertInstanceOf(
            Connection::class,
            $database->getConnection(),
            'getConnection() should return Doctrine\DBAL\Connection instance'
        );
    }

    /**
     * @throws Exception
     */
    public function testQuery()
    {
        $database = $this->services->get(Database::class);
        $this->assertSame(
            1,
            $database->query('SELECT 1')->rowCount(),
            'query() should return Doctrine\DBAL\Driver\ResultStatement instance'
        );
        $this->expectException(Exception::class);
        $database->query('SELECT NOT EXISTS');
    }

    /**
     * @throws Exception
     */
    public function testPrepare()
    {
        $database = $this->services->get(Database::class);
        $this->assertSame(
            1,
            $database->prepare('SELECT 1')->executeQuery()->rowCount(),
            'prepare() should return Doctrine\DBAL\Driver\Statement instance'
        );
        $this->expectException(Exception::class);
        $database->prepare('SELECT NOT EXISTS');
    }
}
