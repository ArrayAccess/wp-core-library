<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Tests\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Services;
use ArrayAccess\WP\Libraries\Core\Service\Services\Option;
use PHPUnit\Framework\TestCase;
use function get_option;
use function microtime;

class OptionTest extends TestCase
{
    protected Option $option;

    protected string $optionName = 'aa_temporary_phpunit_test';

    protected function setUp(): void
    {
        $this->option = (new Services())->get(Option::class);
        $this->option->setOptionName($this->optionName);
        $this->option->destroy();
    }

    public function testGetOptions()
    {
        $this->assertIsArray(
            $this->option->getOptions()
        );
        $this->assertEmpty(
            $this->option->getOptions()
        );
    }
    public function testSetOptions()
    {
        $data = [
            microtime()
        ];
        $this->option->setOptions($data);
        $this->assertSame(
            $data,
            $this->option->getOptions()
        );
    }

    public function testSave()
    {
        $data = ['a' => microtime()];
        $this->option->setOptions($data);
        $this->option->save();
        $this->assertSame(
            // get option should be identical
            get_option($this->optionName),
            $this->option->getOptions()
        );
    }

    public function testRestoreOptions()
    {
        $data = [
            microtime()
        ];
        $this->option->setOptions($data);
        $this->option->restoreOptions();
        $this->assertSame(
            [],
            $this->option->getOptions()
        );
        $this->option->setOptions($data);
        $this->option->save();
        $this->assertSame(
            $data,
            $this->option->getOptions()
        );
    }

    public function testGet()
    {
        $value = microtime();
        $this->assertSame(
            $value,
            $this->option->get('name', $value)
        );
        $this->assertNotSame(
            $value,
            $this->option->get('name', 'temp')
        );
    }

    public function testSet()
    {
        $value = microtime();
        $this->assertTrue(
            $this->option->set('name', $value)
        );
    }


    public function testRemove()
    {
        $data = [
            'a' => 'a',
            'b' => 'b',
        ];
        $this->option->setOptions($data);
        $this->assertSame(
            $data,
            $this->option->getOptions()
        );
        $this->assertArrayHasKey(
            'a',
            $this->option->getOptions()
        );
        $this->option->remove('a');
        $this->assertArrayNotHasKey(
            'a',
            $this->option->getOptions()
        );
    }

    public function testGetOptionName()
    {
        $this->assertSame(
            $this->optionName,
            $this->option->getOptionName()
        );
    }

    public function testSetOptionName()
    {
        $this->option->set('helo', 'a');
        $this->option->setOptionName('hello', false);
        $this->assertSame(
            'hello',
            $this->option->getOptionName()
        );
        // because different it will empty
        $this->assertEmpty(
            $this->option->getOptions()
        );
        $this->option->set('node', 1);
        // use current setting but no save
        $this->option->setOptionName($this->optionName, true);
        $this->assertSame(
            $this->optionName,
            $this->option->getOptionName()
        );
        $this->assertSame(
            1,
            $this->option->get('node')
        );
    }
}
