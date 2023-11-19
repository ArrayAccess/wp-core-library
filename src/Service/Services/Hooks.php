<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\HookInterface;
use WP_Hook;
use function _wp_filter_build_unique_id;
use function array_keys;
use function array_pop;
use function array_unshift;
use function end;
use function in_array;

/**
 * @uses WP_Hook
 * This object does not support 'all' hook name
 */
class Hooks extends AbstractService implements HookInterface
{
    protected string $serviceName = 'hooks';

    /**
     * Hooks collection
     *
     * @var array<string, WP_Hook>
     */
    private array $hooks = [];

    /**
     * The actions
     *
     * @var array<string, int>
     */
    private array $actions = [];

    /**
     * @var array<string>
     */
    private array $currents = [];

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
    public function add(
        string $name,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): void {
        $this->hooks[$name] ??= new WP_Hook();
        $this->hooks[$name]->add_filter($name, $callback, $priority, $acceptedArgs);
    }

    /**
     * Check if the hook is empty
     *
     * @return bool
     */
    public function empty(): bool
    {
        foreach ($this->hooks as $hook) {
            return $hook !== null;
        }
        return true;
    }

    /**
     * Check if the hook exists
     *
     * @param string $name
     * @param callable|null $callback
     * @param int|null $priority
     *
     * @return bool
     */
    public function has(string $name, ?callable $callback = null, ?int $priority = null) : bool
    {
        if (!isset($this->hooks[$name])
            || empty($this->hooks[$name]->callbacks)
        ) {
            return false;
        }
        if ($priority === null) {
            return $this->hooks[$name]->has_filter($name, $callback ?? false) !== false;
        }
        if (!isset($this->hooks[$name]->callbacks[$priority])) {
            return false;
        }
        if ($callback === null) {
            return true;
        }
        $functionKey = _wp_filter_build_unique_id($name, $callback, $priority);
        return isset($this->hooks[$name][$priority][$functionKey]);
    }

    /**
     * Remove a hook
     *
     * @param string $name
     * @param callable|null $callback
     * @param int|null $priority
     *
     * @return bool
     */
    public function remove(string $name, ?callable $callback = null, ?int $priority = null): bool
    {
        if (!isset($this->hooks[$name])) {
            return false;
        }
        if ($callback !== null) {
            $result = !empty($this->hooks[$name]->callbacks);
            $this->hooks[$name]->remove_all_filters($priority??false);
        } else {
            // if priority null remove all callbacks
            if ($priority === null) {
                $result = false;
                foreach (array_keys($this->hooks[$name]->callbacks) as $priority) {
                    $result = $this->hooks[$name]->remove_filter($name, $callback, $priority)?:$result;
                }
            } else {
                $result = $this->hooks[$name]->remove_filter($name, $callback, $priority);
            }
        }
        if ([] === $this->hooks[$name]->callbacks) {
            unset($this->hooks[$name]);
        }
        return $result;
    }

    /**
     * Check the hook already did
     *
     * @param string $name
     * @return int
     */
    public function did(string $name): int
    {
        return $this->actions[$name]??0;
    }

    /**
     * Check if the hook is doing
     *
     * @param string|null $name
     * @return bool
     */
    public function doing(?string $name = null): bool
    {
        if ($name === null) {
            return !$this->empty();
        }

        return in_array($name, $this->currents, true);
    }

    /**
     * Get the current hook name
     *
     * @return ?string
     */
    public function current() : ?string
    {
        $current = end($this->currents);
        return $current === false ? null : $current;
    }

    /**
     * Apply the hook
     *
     * @param string $name
     * @param mixed $value
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function apply(string $name, mixed $value, mixed ...$args): mixed
    {
        $this->actions[$name] ??= 0;
        $this->actions[$name]++;
        if (!isset($this->hooks[$name])) {
            return $value;
        }

        $this->currents[] = $name;
        array_unshift($args, $value);
        $filtered = $this->hooks[$name]->apply_filters($value, $args);
        array_pop($this->currents);
        return $filtered;
    }

    /**
     * Get lists of hook names
     *
     * @return array<string, WP_Hook>
     */
    public function getHookNames() : array
    {
        return array_keys($this->hooks);
    }

    /**
     * Count the hooks by name
     *
     * @return int
     */
    public function count() : int
    {
        return $this->empty() ? 0 : count($this->hooks);
    }
}