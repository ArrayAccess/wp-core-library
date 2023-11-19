<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use function array_key_exists;
use function delete_option;
use function is_array;

/**
 * Option service to create and manage options
 */
class Option extends AbstractService
{
    /**
     * @var string The service name.
     */
    protected string $serviceName = 'option';

    /**
     * @var string The option name.
     */
    private string $optionName = 'array-access_wp_libraries_core_options';

    /**
     * @var ?array<string, mixed> The options.
     */
    private ?array $options = null;

    private ?array $originalOptions;

    private bool $changed = false;

    public function getOptionName(): string
    {
        return $this->optionName;
    }

    public function setOptionName(
        string $optionName,
        bool $useCurrent = false
    ): void {
        if (!$useCurrent && $this->optionName !== $optionName) {
            $this->options = null;
            $this->originalOptions = null;
        }
        $this->changed = false;
        $this->optionName = $optionName;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        if (is_array($this->options)) {
            return $this->options;
        }
        $options = get_option($this->optionName, []);
        if (!is_array($options)) {
            $options = [];
        }
        $this->originalOptions = $options;
        return $this->options = $options;
    }

    public function restoreOptions(): void
    {
        if ($this->options === null) {
            return;
        }
        $this->options = $this->init()->originalOptions;
    }

    /**
     * @return $this
     */
    private function init() : static
    {
        if (!isset($this->options)) {
            $this->getOptions();
        }
        return $this;
    }

    /**
     * Get option
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        $options = $this->getOptions();
        return array_key_exists($name, $options)
            ? $options[$name]
            : $default;
    }

    /**
     * @param array $options
     * @return void
     */
    public function setOptions(array $options): void
    {
        $this->init()->options = $options;
        $this->changed = true;
    }

    /**
     * Set option
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function set(string $name, mixed $value): bool
    {
        $this->changed = true;
        $this->init()->options[$name] = $value;
        return true;
    }

    /**
     * Remove option
     *
     * @param string $name
     * @return bool
     */
    public function remove(string $name): bool
    {
        unset($this->init()->options[$name]);
        $this->changed = true;
        return true;
    }

    /**
     * Delete option
     */
    public function destroy(): bool
    {
        $this->changed = false;
        $this->options = null;
        return delete_option($this->getOptionName());
    }

    /**
     * Save options
     *
     * @return bool
     */
    public function save(): bool
    {
        if (is_array($this->options)) {
            $result = update_option($this->getOptionName(), $this->options);
            if ($result) {
                $this->originalOptions = $this->options;
                $this->changed = false;
            }
            return $result;
        }
        return false;
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if ($this->changed) {
            $this->save();
        }
    }
}
