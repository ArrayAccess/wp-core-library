<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\StandardInputAttributeTrait;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use function is_string;
use function preg_match;
use function preg_replace;
use function str_starts_with;
use function wp_enqueue_script;
use function wp_json_encode;
use function wp_script_is;
use function wp_style_is;

class ColorPicker extends AbstractField implements FormFieldTypeInterface
{
    use StandardInputAttributeTrait;

    /**
     * @var string The tag name.
     */
    protected string $tagName = 'input';

    /**
     * Default class for the field.
     */
    protected array $defaultClass = [
        'wp-color-picker'
    ];

    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'text',
        'data-color-picker' => 'true',
    ];

    /**
     * @var array The color picker options.
     */
    private array $colorPickerOptions = [
        'hide' => true,
    ];

    /**
     * @var array|string[] The allowed types.
     */
    protected array $allowedTypes = [
        'text',
        'hidden',
        'password'
    ];

    /**
     * @var array|string[] The disallowing remove attributes.
     */
    protected array $disallowRemoveAttributes = [
        'data-color-picker',
        'data-color-picker-options',
    ];

    /**
     * @param string $attributeName
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $attributeName, mixed $value): static
    {
        $attributeName = strtolower(trim($attributeName));
        // prevent to change data-color-picker attribute
        if ($attributeName === 'data-color-picker') {
            return $this;
        }

        return parent::setAttribute($attributeName, $value);
    }

    /**
     * Validate the value. Color is valid if it is not empty and is a valid color.
     *
     * @param mixed|null $value
     * @param bool $allowNull
     * @return bool
     */
    public function isValidValue(mixed $value = null, bool $allowNull = true): bool
    {
        if (!parent::isValidValue($value, $allowNull)) {
            return false;
        }
        if ($value === null && $allowNull) {
            return true;
        }
        $required = $this->getAttribute('required');
        if (HtmlAttributes::isBooleanAttributeEnabled('required', $required) && empty($value)) {
            return false;
        }
        if (!is_string($value)) {
            return false;
        }

        // validate color hex rgb or rgba
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value) === 1
            || preg_match('/^rgb\((\d{1,3}),(\d{1,3}),(\d{1,3})\)$/i', $value) === 1
            || preg_match('/^rgba\((\d{1,3}),(\d{1,3}),(\d{1,3}),(\d{1,3})\)$/i', $value) === 1;
    }

    /**
     * Set the color picker options.
     *
     * @param array $options
     * @return $this
     */
    public function setColorPickerOptions(array $options): static
    {
        $this->colorPickerOptions = $options;
        return $this;
    }

    public function getColorPickerOptions(): array
    {
        return $this->colorPickerOptions;
    }

    /**
     * @param string $value
     * @return string
     */
    public function filterValue(mixed $value = null): string
    {
        if (!is_string($value)) {
            return '';
        }
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $value = !str_starts_with($value, '#')
            && preg_match('/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value) === 1
            ? '#' . $value
            : preg_replace('/^#+/', '#', $value);

        if (!$this->isValidValue($value)) {
            return '';
        }
        return $value;
    }
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['data-color-picker-options'] = wp_json_encode((object)$this->getColorPickerOptions())?:'{}';
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function doEnqueueAssets(): static
    {
        if (!wp_script_is('wp-color-picker')) {
            wp_enqueue_script('wp-color-picker');
        }
        if (!wp_style_is('wp-color-picker')) {
            wp_enqueue_style('wp-color-picker');
        }
        return $this;
    }
}
