<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service;

use ArrayAccess\WP\Libraries\Core\Service\Interfaces\HookInterface;
use WP_Hook;
use function _wp_filter_build_unique_id;
use function array_keys;
use function array_pop;
use function array_unshift;
use function end;
use function in_array;

/**
 * Service hook that helps to handle the hooks outside of core WordPress hooks.
 *
 * @uses WP_Hook
 * This object does not support 'all' hook name
 */
class Hook implements HookInterface
{
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
     * @inheritdoc
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
     * @inheritdoc
     */
    public function empty(): bool
    {
        foreach ($this->hooks as $hook) {
            return $hook !== null;
        }
        return true;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
     */
    public function did(string $name): int
    {
        return $this->actions[$name]??0;
    }

    /**
     * @inheritdoc
     */
    public function doing(?string $name = null): bool
    {
        if ($name === null) {
            return !$this->empty();
        }

        return in_array($name, $this->currents, true);
    }

    /**
     * @inheritdoc
     */
    public function current() : ?string
    {
        $current = end($this->currents);
        return $current === false ? null : $current;
    }

    /**
     * @inheritdoc
     */
    public function apply(string $name, mixed $value = null, mixed ...$args): mixed
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
     * @param string $name
     * @param mixed|null $value
     * @param ...$args
     * @return void
     */
    public function do(string $name, mixed $value = null, ...$args): void
    {
        $this->apply($name, $value, ...$args);
    }

    /**
     * @inheritdoc
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
