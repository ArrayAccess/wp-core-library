<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Interfaces;

use Countable;
use WP_Hook;

interface HookInterface extends ServiceInterface, Countable
{
    public function add(string $name, callable $callback, int $priority = 10, int $acceptedArgs = 1) : void;

    public function remove(string $name, ?callable $callback = null, ?int $priority = null): bool;

    public function doing(?string $name = null) : bool;

    public function did(string $name) : int;

    public function has(string $name, ?callable $callback = null, ?int $priority = null) : bool;

    public function empty() : bool;

    public function current() : ?string;

    public function apply(string $name, mixed $value, mixed ...$args) : mixed;

    /**
     * Get lists of hook names
     *
     * @return array<string, WP_Hook>
     */
    public function getHookNames() : array;
}
