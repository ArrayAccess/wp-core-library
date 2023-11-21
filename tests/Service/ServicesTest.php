<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Tests\Libraries\Core\Service;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use ArrayAccess\WP\Libraries\Core\Service\Services;
use PHPUnit\Framework\TestCase;
use function strtolower;

/**
 * Unit test to test Services class
 */
class ServicesTest extends TestCase
{
    protected Services $services;

    protected function setUp(): void
    {
        $this->services = new Services();
    }

    public function testGetServiceClassName()
    {
        $this->assertSame(
            Services\Database::class,
            $this->services->getServiceClassName(Services\Database::class),
            'getServiceClassName() should return the same class name if the class name is valid'
        );
        $this->assertNull(
            $this->services->getServiceClassName(''),
            'getServiceClassName() should return null if the class name is empty'
        );
        $this->assertNull(
            $this->services->getServiceClassName(Services::class),
            'getServiceClassName() should return null if the class name is not a subclass of AbstractService'
        );
    }

    public function testAdd()
    {
        // because Database class is factory
        $this->assertFalse(
            $this->services->add(Services\Database::class),
            'add() should return false if the class name is factory'
        );
        $this->assertFalse(
            $this->services->add(Services::class),
            'add() should return false if the class name is not a subclass of AbstractService'
        );
        $fakeClass = new class($this->services) extends AbstractService {
            protected string $serviceName = 'fake';
        };
        $this->assertTrue(
            $this->services->add($fakeClass),
            'add() should return true if the class name is a subclass of AbstractService'
        );
        // because fake class already added
        $this->assertFalse(
            $this->services->add($fakeClass),
            'add() should return false if the class name is already added'
        );
    }

    public function testSet()
    {
        $fakeClass = new class($this->services) extends AbstractService {
            protected string $serviceName = 'fake';
        };
        $this->assertTrue(
            $this->services->set($fakeClass),
            'set() should return true if the class name is a subclass of AbstractService'
        );
        $this->assertFalse(
            $this->services->set($fakeClass::class),
            'set() should return false if the class name is already added'
        );
    }

    public function testGet()
    {
        $this->assertInstanceOf(
            Services\Database::class,
            $this->services->get(Services\Database::class),
            'get() should return the same class name if the class name is valid'
        );
        $this->assertInstanceOf(
            Services\StatelessHash::class,
            $this->services->get(Services\StatelessHash::class),
            'get() should return the same class name if the class name is valid'
        );
        $this->assertInstanceOf(
            Services\Hooks::class,
            $this->services->get(Services\Hooks::class),
            'get() should return the same class name if the class name is valid'
        );
        $this->assertInstanceOf(
            Services\Option::class,
            $this->services->get(Services\Option::class),
            'get() should return the same class name if the class name is valid'
        );
    }

    public function testContain()
    {
        $this->assertTrue(
            $this->services->contain(Services\Database::class),
            'contain() should return true if the class name is valid'
        );
        $this->assertTrue(
            $this->services->contain(
                $this->services->get(Services\Database::class),
            ),
            'contain() should return true if the class name is valid'
        );
        $this->assertFalse(
            $this->services->contain(Services::class),
            'contain() should return false if the class name is not a subclass of AbstractService'
        );
    }


    public function testGetServices()
    {
        $this->assertIsArray(
            $this->services->getServices(),
            'getServices() should return an array'
        );
        $this->assertArrayHasKey(
            strtolower(Services\Database::class),
            $this->services->getServices(),
            'getServices() should return an array with key is the class name'
        );
        $this->assertArrayHasKey(
            strtolower(Services\StatelessHash::class),
            $this->services->getServices(),
            'getServices() should return an array with key is the class name'
        );
        $this->assertArrayHasKey(
            strtolower(Services\Hooks::class),
            $this->services->getServices(),
            'getServices() should return an array with key is the class name'
        );
        $this->assertArrayHasKey(
            strtolower(Services\Option::class),
            $this->services->getServices(),
            'getServices() should return an array with key is the class name'
        );
    }

    public function testRemove()
    {
        $this->assertFalse(
            $this->services->remove(Services\Database::class),
            'remove() should return false if the class name is factory'
        );
        $this->assertFalse(
            $this->services->remove(Services::class),
            'remove() should return false if the class name is not a subclass of AbstractService'
        );
        $fakeClass = new class($this->services) extends AbstractService {
            protected string $serviceName = 'fake';
        };
        $this->services->add($fakeClass);
        $this->assertTrue(
            $this->services->remove($fakeClass),
            'remove() should return true if the class name is a subclass of AbstractService'
        );
    }
}
