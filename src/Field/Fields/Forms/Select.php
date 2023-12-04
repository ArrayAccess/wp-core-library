<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Service\Services\DefaultAssets;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use function array_filter;
use function array_values;
use function esc_html;
use function force_balance_tags;
use function func_num_args;
use function is_array;
use function is_float;
use function is_int;
use function is_iterable;
use function is_scalar;
use function is_string;
use function preg_match;
use function wp_kses_post;
use function wp_script_is;
use function wp_style_is;
use const ARRAY_FILTER_USE_KEY;

/**
 * Single Select field
 */
class Select extends AbstractField implements FormFieldTypeInterface
{
    /**
     * @var string The tag name.
     */
    protected string $tagName = 'select';

    /**
     * @var array<scalar, array> key is the value and value is the label
     */
    protected array $options = [];

    /**
     * @var string[] The selected value, if null, the first option will be selected
     */
    protected array $selected = [];

    /**
     * The info of select field (the name info of the field)
     * e.g.: <option disabled selected>Choose your option</option>
     * @var string|null if null, will not show
     */
    protected ?string $info = null;

    /**
     * @var bool if true, the info will be selectable
     */
    protected bool $infoSelectable = false;

    /**
     * @var string[] disallowed attributes
     */
    protected array $disallowedAttributes = [
        'type',
        'value',
        'checked',
        'selected',
        'multiple',
        'min',
        'max',
        'step',
        'size',
        'maxlength',
        'minlength',
    ];

    /**
     * @var bool true if multiple
     */
    protected bool $multiple = false;

    /**
     * @param string|null $selected The selected value, if null, the first option will be selected
     */
    public function __construct(?string $name = null, ...$selected)
    {
        parent::__construct($name);
        $this->setSelected(...array_values($selected));
    }

    /**
     * @param bool $multiple
     * @return $this
     */
    public function setMultiple(bool $multiple): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * @return bool $multiple true if multiple
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * @inheritdoc
     */
    public function setAttributes(array $attributes): static
    {
        if ($this->attributes === [] && !isset($attributes['data-selectize'])) {
            $attributes['data-selectize'] = 'true';
        }
        return parent::setAttributes($attributes);
    }

    /**
     * @inheritdoc
     */
    public function setAttribute(string $attributeName, mixed $value): static
    {
        $attributeName = HtmlAttributes::filterAttributeName($attributeName);
        if ($attributeName === 'selected') {
            $value = is_array($value) ? array_values($value) : (
                is_iterable($value)
                    ? iterator_to_array($value)
                    : [$value]
            );
            $this->setSelected(...array_values($value));
            return $this;
        }

        if ($attributeName === 'multiple') {
            $this->setMultiple((bool) $value);
            return $this;
        }

        return parent::setAttribute($attributeName, $value);
    }

    /**
     * @param mixed $selected
     * @return $this
     */
    public function addSelected(mixed $selected): static
    {
        if (is_scalar($selected) && isset($this->options[(string) $selected])) {
            $selected = (string) $selected;
            $this->selected[$selected] = true;
        } elseif (is_array($selected) || is_iterable($selected)) {
            foreach ($selected as $value) {
                $this->addSelected($value);
            }
        }
        return $this;
    }

    /**
     * Set options of select field.
     * @param string ...$selected
     * @return $this
     */
    public function setSelected(mixed ...$selected): static
    {
        $this->selected = [];
        return $this->addSelected(array_values($selected));
    }

    /**
     * Get options of select field.
     *
     * @return array<scalar, array> The selected value.
     */
    public function getSelected(): array
    {
        return array_filter(
            $this->getOptions(),
            fn ($e) => isset($this->selected[$e]),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return $this clear selected
     */
    public function clearSelected(): static
    {
        $this->selected = [];
        return $this;
    }

    /**
     * Set info of select field (the name info of the field).
     * @param string|null $info if null, will not show
     * @return $this
     */
    public function setInfo(?string $info): static
    {
        $this->info = $info;
        return $this;
    }

    /**
     * Get info of select field (the name info of the field).
     *
     * @return string|null
     */
    public function getInfo(): ?string
    {
        return $this->info;
    }

    /**
     * Set if info is selectable.
     *
     * @param bool $infoSelectable true if info is selectable
     * @return $this
     */
    public function setInfoSelectable(bool $infoSelectable): static
    {
        $this->infoSelectable = $infoSelectable;
        return $this;
    }

    /**
     * Get if info is selectable.
     *
     * @return bool
     */
    public function isInfoSelectable(): bool
    {
        return $this->infoSelectable;
    }

    /**
     * Add an option to select field.
     *
     * @param string|int|float $value
     * @param string $label
     * @param array $attributes
     * @return $this
     */
    public function addOption(string|int|float $value, string $label, array $attributes = []): static
    {
        $value = (string) $value;
        $attributes['label'] = $label;
        $this->options[$value] = $attributes;
        $selected = HtmlAttributes::isBooleanEnabled($attributes['selected']??false);
        unset($attributes['selected'], $attributes['value']);
        if ($selected) {
            $this->addSelected($value);
        }
        return $this;
    }

    /**
     * Check if select field has an option.
     *
     * @param string|int|float $value
     * @return bool
     */
    public function hasOption(mixed $value): bool
    {
        if (!is_scalar($value)) {
            return false;
        }
        $value = (string) $value;
        return isset($this->options[$value]);
    }

    /**
     * Remove an option from select field.
     *
     * @param mixed $value
     * @return $this
     */
    public function removeOption(mixed $value): static
    {
        if (!is_scalar($value)) {
            return $this;
        }
        $value = (string) $value;
        unset($this->options[$value], $this->selected[$value]);
        return $this;
    }

    /**
     * Clear all options.
     *
     * @return $this
     */
    public function clearOptions(): static
    {
        $this->options = [];
        return $this;
    }

    /**
     * Get all options.
     *
     * @return array<scalar, array> key is the value and value is the label
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @inheritdoc
     * @param bool $inline
     * @return string The html of the field
     */
    public function build(?bool $inline = null): string
    {
        $inline ??= $this->isInline();
        $isMultiple = $this->isMultiple();
        $html = '';
        $info = $this->getInfo();
        $containSelected = false;
        $selected = $this->getSelected();
        foreach ($this->getOptions() as $value => $attr) {
            $attr['label'] = (string) ($attr['label']??$value);
            $attr['value'] = (string) $value;
            $attr['selected'] = isset($selected[$value]);
            $containSelected = $containSelected || $attr['selected'];
            if (!$attr['selected']) {
                unset($attr['selected']);
            }
            $html .= HtmlAttributes::createHtmlTag('option', $attr);
        }

        if ($info) {
            $attr = [
                'value' => '',
            ];
            if (!$containSelected) {
                $attr['selected'] = true;
            }
            if (!$this->isInfoSelectable()) {
                $attr['disabled'] = true;
            }
            $html = HtmlAttributes::createHtmlTag('option', $attr) . $html;
        }
        $attr = $this->getAttributes();
        $attr['placeholder'] ??= $info??_n(
            'Select an option ...',
            'Select options ...',
            $isMultiple ? 2 : 1,
            'arrayaccess'
        );
        $attr['multiple'] = $isMultiple;
        $name = $this->getName();
        if (!is_string($name)) {
            $name = null;
        }
        if ($name) {
            $name = $isMultiple && !preg_match(
                '~^.*\[.*]~',
                $name
            ) ? $name . '[]' : $name;
        }
        if ($name === null) {
            $this->setName(null);
            unset($attr['name']);
        } else {
            $attr['name'] = $name;
        }
        $attr['html'] = $html;
        $html = HtmlAttributes::createHtmlTag($this->getTagName(), $attr);
        $label = $this->getLabel();
        if ($label) {
            $label = str_contains($label, '<') ? force_balance_tags($label) : esc_html($label);
            $html = $inline ? '<label class="aa-label aa-label-inline" for="'
                . $this->getId() . '">'
                . '<span class="aa-field-label">'
                . $label
                . '</span>'
                . $html
                . '</label>' : '<label class="aa-label" for="'
                . $this->getId() . '">' . $label . '</label>' . $html;
        }
        $description = $this->getDescription();
        if ($description !== null) {
            // check if contain html tag > use force_balance_tag
            if (str_contains($description, '<')) {
                $description = wp_kses_post($description);
            }
            $html .= '<span class="aa-field-description aa-select-description">' . $description . '</span>';
        }
        return $html;
    }

    /**
     * @inheritdoc
     */
    public function isValidValue(mixed $value = null, bool $allowNull = true): bool
    {
        $value = func_num_args() === 0 ? $this->getSelected() : $value;
        if ($allowNull && $value === null) {
            return true;
        }
        if ($this->isMultiple()) {
            // if the type is string|float|int and is in option, check has options.
            if (is_string($value) || is_float($value) || is_int($value)) {
                return $this->hasOption($value);
            }
            if (!is_array($value)) {
                return false;
            }
            foreach ($value as $v) {
                if (!is_scalar($v) || !$this->isValidValue($v, false)) {
                    return false;
                }
            }
            return true;
        }

        // if the type is string|float|int and is in option, check has options.
        if (is_string($value) || is_float($value) || is_int($value)) {
            return $this->hasOption($value);
        }
        return false;
    }

    protected function doEnqueueAssets(): static
    {
        if ($this->getAttribute('data-selectize') !== 'true') {
            return $this;
        }

        if (!wp_script_is('selectize')
            || !wp_style_is('selectize')
        ) {
            $defaultAssets = DefaultAssets::getInstance();
            $defaultAssets->init();
            $defaultAssets->enqueueAsset('selectize');
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValue(): string|array|null
    {
        $selected = $this->getSelected();
        if (!$this->isMultiple()) {
            return count($selected) > 0 ? (string) reset($selected) : null;
        }
        return $selected === [] ? null : $selected;
    }
}
