<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use function array_key_exists;
use function delete_option;
use function is_array;

/**
 * Option service to create and manage options with object oriented itself.
 */
class Option extends AbstractService
{
    /**
     * @var string The service name.
     */
    protected string $serviceName = 'option';

    /**
     * @var ?string The option name.
     */
    protected ?string $optionName = null;

    /**
     * The options.
     * @var ?array<string, mixed> The options.
     */
    private ?array $options = null;

    /**
     * The original options.
     *
     * @var array|null
     */
    private ?array $originalOptions;

    /**
     * @var bool The changed flag.
     */
    private bool $changed = false;

    /**
     * @inheritdoc
     */
    protected function onConstruct(): void
    {
        $this->description = __(
            'Option service to create and manage options with object oriented itself.',
            'arrayaccess'
        );
    }

    /**
     * Get option name
     *
     * @return ?string The option name.
     */
    public function getOptionName(): ?string
    {
        return $this->optionName;
    }

    /**
     * Set option name to get / set of options.
     *
     * @param string $optionName
     * @param bool $useCurrent
     * @return void
     */
    public function setOptionName(
        string $optionName,
        bool $useCurrent = false
    ): void {
        if ($this->optionName === null || !$useCurrent && $this->optionName !== $optionName) {
            $this->options = null;
            $this->originalOptions = null;
        }
        $this->changed = false;
        $this->optionName = $optionName;
    }

    /**
     * Get option
     *
     * @param ?string $optionName
     * @param mixed $default
     * @return mixed
     */
    protected function getOptionFrom(?string $optionName, mixed $default = []) : mixed
    {
        if ($optionName === null) {
            return $default;
        }
        return get_option($optionName, $default);
    }

    /**
     * Delete option
     *
     * @param ?string $optionName
     * @return bool
     */
    protected function deleteOptionFrom(?string $optionName) : bool
    {
        if ($optionName === null) {
            return false;
        }
        return delete_option($optionName);
    }

    /**
     * Save option
     *
     * @param ?string $optionName
     * @param array $value
     * @return bool
     */
    protected function saveOptionFrom(?string $optionName, array $value) : bool
    {
        if ($optionName === null) {
            return false;
        }
        return update_option($optionName, $value);
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions(): array
    {
        if (is_array($this->options)) {
            return $this->options;
        }
        $options = $this->getOptionFrom($this->getOptionName());
        if (!is_array($options)) {
            $options = [];
        }
        $this->originalOptions = $options;
        return $this->options = $options;
    }

    /**
     * Restore the original options.
     *
     * @return void
     */
    public function restoreOptions(): void
    {
        if ($this->options === null) {
            return;
        }
        $this->options = $this->init()->originalOptions;
    }

    /**
     * Initialize the options.
     *
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
     * @return bool true if deleted
     */
    public function destroy(): bool
    {
        $this->changed = false;
        $this->options = null;
        return $this->deleteOptionFrom($this->getOptionName());
    }

    /**
     * Save options
     *
     * @return bool true if saved
     */
    public function save(): bool
    {
        if (is_array($this->options)) {
            $result = $this->saveOptionFrom($this->getOptionName(), $this->options);
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
     * Operation save will be executed if the options changed.
     */
    public function __destruct()
    {
        if ($this->changed) {
            $this->save();
        }
    }
}
