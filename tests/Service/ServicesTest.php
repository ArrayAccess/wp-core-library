<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Tests\Libraries\Core\Service;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use ArrayAccess\WP\Libraries\Core\Service\Services;
use PHPUnit\Framework\TestCase;
use function strtolower;

class ServicesTest extends TestCase
{
    protected Services $services;
    protected function setUp(): void
    {
        $this->services = new Services();
    }

    public function testGetServiceClassName()
    {
        $this->assertEquals(
            Services\Database::class,
            $this->services->getServiceClassName(Services\Database::class)
        );
        $this->assertNull($this->services->getServiceClassName(''));
        $this->assertNull($this->services->getServiceClassName(Services::class));
    }

    public function testAdd()
    {
        // because Database class is factory
        $this->assertFalse(
            $this->services->add(Services\Database::class)
        );
        $this->assertFalse(
            $this->services->add(Services::class)
        );
        $fakeClass = new class($this->services) extends AbstractService {
            protected string $serviceName = 'fake';
        };
        $this->assertTrue(
            $this->services->add($fakeClass)
        );
        // because fake class already added
        $this->assertFalse(
            $this->services->add($fakeClass)
        );
    }

    public function testSet()
    {
        $fakeClass = new class($this->services) extends AbstractService {
            protected string $serviceName = 'fake';
        };
        $this->assertTrue(
            $this->services->set($fakeClass)
        );
        $this->assertFalse(
            $this->services->set($fakeClass::class)
        );
    }

    public function testGet()
    {
        $this->assertInstanceOf(
            Services\Database::class,
            $this->services->get(Services\Database::class)
        );
        $this->assertInstanceOf(
            Services\StatelessCookie::class,
            $this->services->get(Services\StatelessCookie::class)
        );
        $this->assertInstanceOf(
            Services\Hooks::class,
            $this->services->get(Services\Hooks::class)
        );
        $this->assertInstanceOf(
            Services\Option::class,
            $this->services->get(Services\Option::class)
        );
    }

    public function testContain()
    {
        $this->assertTrue(
            $this->services->contain(Services\Database::class)
        );
        $this->assertTrue(
            $this->services->contain(
                $this->services->get(Services\Database::class)
            )
        );
        $this->assertFalse(
            $this->services->contain(Services::class)
        );
    }


    public function testGetServices()
    {
        $this->assertIsArray(
            $this->services->getServices()
        );
        $this->assertArrayHasKey(
            strtolower(Services\Database::class),
            $this->services->getServices()
        );
        $this->assertArrayHasKey(
            strtolower(Services\StatelessCookie::class),
            $this->services->getServices()
        );
        $this->assertArrayHasKey(
            strtolower(Services\Hooks::class),
            $this->services->getServices()
        );
        $this->assertArrayHasKey(
            strtolower(Services\Option::class),
            $this->services->getServices()
        );
    }

    public function testRemove()
    {
        $this->assertFalse(
            $this->services->remove(Services\Database::class)
        );
        $this->assertFalse(
            $this->services->remove(Services::class)
        );
        $fakeClass = new class($this->services) extends AbstractService {
            protected string $serviceName = 'fake';
        };
        $this->services->add($fakeClass);
        $this->assertTrue(
            $this->services->remove($fakeClass)
        );
    }
}
