<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Interfaces;

use Countable;

/**
 * Service hook that helps to handle the hooks outside of core WordPress hooks.
 */
interface HookInterface extends Countable
{
    /**
     * Add a hook
     *
     * @param string $name
     * @param callable $callback
     * @param int $priority
     * @param int $acceptedArgs
     *
     * @return void
     */
    public function add(string $name, callable $callback, int $priority = 10, int $acceptedArgs = 1) : void;

    /**
     * Remove a hook
     *
     * @param string $name
     * @param callable|null $callback
     * @param int|null $priority
     *
     * @return bool
     */
    public function remove(string $name, ?callable $callback = null, ?int $priority = null): bool;

    /**
     * Check if the hook is doing
     *
     * @param string|null $name
     * @return bool
     */
    public function doing(?string $name = null) : bool;

    /**
     * Check the hook already did
     *
     * @param string $name
     * @return int
     */
    public function did(string $name) : int;

    /**
     * Check if the hook exists
     *
     * @param string $name
     * @param callable|null $callback
     * @param int|null $priority
     *
     * @return bool
     */
    public function has(string $name, ?callable $callback = null, ?int $priority = null) : bool;

    /**
     * Check if the hook is empty
     *
     * @return bool
     */
    public function empty() : bool;

    /**
     * Get the current hook name
     *
     * @return ?string
     */
    public function current() : ?string;

    /**
     * Apply the hook
     *
     * @param string $name
     * @param mixed $value
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function apply(string $name, mixed $value = null, mixed ...$args) : mixed;

    /**
     * Do the hook
     *
     * @param string $name
     * @param mixed|null $value
     * @param mixed ...$args
     * @return void
     */
    public function do(string $name, mixed $value = null, mixed ...$args) : void;

    /**
     * Get lists of hook names
     *
     * @return array<string, \WP_Hook>
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function getHookNames() : array;
}
