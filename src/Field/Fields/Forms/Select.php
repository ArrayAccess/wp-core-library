<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractOptionCollection;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Service\Services\DefaultAssets;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use function array_filter;
use function array_map;
use function array_values;
use function esc_html;
use function force_balance_tags;
use function func_num_args;
use function in_array;
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
     * @var array<option|OptGroup> The options of select field.
     */
    protected array $options = [];

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
        if ($attributeName === 'multiple') {
            $this->setMultiple((bool) $value);
            return $this;
        }
        if ($attributeName === 'placeholder'
            && !$this->info
            && is_string($value)
        ) {
            $this->setInfo($value);
        }
        if ($attributeName === 'settings'
            || $attributeName === 'setting'
        ) {
            $attributeName = 'data-selectize-options';
        }
        return parent::setAttribute($attributeName, $value);
    }

    /**
     * @param mixed $selected
     * @return $this
     */
    public function addSelected(mixed $selected): static
    {
        if (!is_array($selected) && is_iterable($selected)) {
            $selected = iterator_to_array($selected);
        }
        $selected = is_array($selected) ? array_values($selected) : [$selected];
        foreach ($this->options as $k => $children) {
            if ($children instanceof AbstractOptionCollection) {
                $children->addSelectedOption(...$selected);
                continue;
            }
            if ($children instanceof Option) {
                $value = $children->getValue();
                if ($value !== null && in_array($value, $selected, true)) {
                    $children->setSelected(true);
                }
                continue;
            }
            // remove if not option or optgroup
            unset($this->options[$k]);
        }
        return $this;
    }

    /**
     * Set options of select field.
     * @param string ...$selected
     * @return $this
     */
    public function setSelected(mixed $selected): static
    {
        if (!is_array($selected) && is_iterable($selected)) {
            $selected = iterator_to_array($selected);
        }
        $selected = is_array($selected) ? array_values($selected) : [$selected];
        foreach ($this->options as $k => $children) {
            if ($children instanceof OptGroup) {
                $children->setSelectedOption(...$selected);
                continue;
            }
            if ($children instanceof Option) {
                $value = $children->getValue();
                if ($value !== null && in_array($value, $selected, true)) {
                    $children->setSelected(true);
                }
                continue;
            }
            // remove if not option or optgroup
            unset($this->options[$k]);
        }
        return $this;
    }

    public function remove(mixed $value): static
    {
        if ($value instanceof Option || $value instanceof OptGroup) {
            $this->options = array_filter(
                $this->options,
                fn ($e) => $e !== $value,
                ARRAY_FILTER_USE_KEY
            );
            return $this;
        }
        if (!is_scalar($value)) {
            return $this;
        }

        foreach ($this->options as $k => $children) {
            if ($children instanceof OptGroup) {
                foreach ($children->getOptions() as $opt) {
                    if ($opt->getValue() === $value) {
                        $children->remove($opt);
                    }
                }
                continue;
            }
            if ($children instanceof Option && $children->getValue() === $value) {
                unset($this->options[$k]);
            }
        }
        return $this;
    }

    /**
     * Get options of select field.
     *
     * @return array<scalar, ?string> The selected value.
     */
    public function getSelected(): array
    {
        $selected = [];
        foreach ($this->getOptions() as $children) {
            if ($children instanceof OptGroup) {
                $selectedOptions = $children->getSelectedOptions();
                foreach ($selectedOptions as $option) {
                    $value = $option->getValue();
                    if (is_scalar($value)) {
                        $value = (string) $value;
                        $selected[$value] = $option->getLabel();
                    }
                }
                continue;
            }
            if ($children instanceof Option && $children->isSelected()) {
                $value = $children->getValue();
                if (is_scalar($value)) {
                    $value = (string) $value;
                    $selected[$value] = $children->getLabel();
                }
            }
        }
        return $selected;
    }

    /**
     * @return $this clear selected
     */
    public function clearSelected(): static
    {
        array_map(fn (Option $e) => $e->setSelected(false), $this->getOptions());
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
     * @return ?Option
     */
    public function addOptionByDeclaration(mixed $value, mixed $label, array $attributes = []): ?Option
    {
        if (!is_scalar($value) || !is_scalar($label)) {
            return null;
        }
        $option = new Option();
        $value = (string) $value;
        $option->setLabel(
            $label
        )->setValue(
            $value
        )->setAttributes(
            $attributes
        );
        $selected = HtmlAttributes::isBooleanAttributeEnabled(
            'selected',
            $attributes['selected']??false
        );
        if ($selected) {
            $option->setSelected(true);
        }
        $this->add($option);
        return $option;
    }

    /**
     * @param scalar $label
     * @param array $options
     * @return ?OptGroup
     */
    public function addOptionGroupByDeclaration(mixed $label, array $options = []): ?OptGroup
    {
        if (!is_scalar($label)) {
            return null;
        }
        $label = (string) $label;
        $optGroup = new OptGroup();
        $optGroup->setLabel($label);
        $this->add($optGroup);
        foreach ($options as $k => $label) {
            if (is_scalar($label)) {
                $optGroup->addOption($k, $label);
                continue;
            }
            if ($label instanceof Option) {
                $optGroup->add($label);
                continue;
            }
            if ($label instanceof OptGroup) {
                foreach ($label->getOptions() as $opt) {
                    $optGroup->add($opt);
                }
                continue;
            }
            if (!is_array($label)) {
                continue;
            }
            $labelName  = $label['label']??null;
            $value = $label['value']??$k;
            if (!is_scalar($value)) {
                continue;
            }

            $labelName ??= $value;
            if (!is_scalar($labelName)) {
                continue;
            }
            $attributes = $label['attributes']??null;
            unset($label['label'], $label['value'], $label['attributes']);
            if (!is_array($attributes)) {
                $attributes = $label;
            }
            $optGroup->addOption($value, $labelName, $attributes);
        }
        return $optGroup;
    }

    /**
     * @param OptGroup|Option $option
     * @return $this
     */
    public function add(OptGroup|Option $option): static
    {
        if (in_array($option, $this->getOptions(), true)) {
            return $this;
        }
        $this->options[] = $option;
        return $this;
    }

    /**
     * Check if select field has an option.
     *
     * @param scalar|OptGroup|Option $value
     * @return bool
     */
    public function hasOption(mixed $value): bool
    {
        if ($value instanceof Option || $value instanceof OptGroup) {
            return in_array($value, $this->getOptions(), true);
        }

        if (!is_scalar($value)) {
            return false;
        }

        foreach ($this->getOptions() as $children) {
            if ($children instanceof AbstractOptionCollection) {
                if ($children->hasOption($value)) {
                    return true;
                }
                continue;
            }
            if ($children instanceof Option && $children->getValue() === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Clear all options.
     *
     * @return $this
     */
    public function clear(): static
    {
        $this->options = [];
        return $this;
    }

    /**
     * Get all options.
     *
     * @return array<scalar, Option|OptGroup> key is the value and value is the label
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
        foreach ($this->getOptions() as $option) {
            if (!$containSelected) {
                if ($option instanceof Option) {
                    $containSelected = $option->isSelected();
                } elseif ($option instanceof OptGroup) {
                    $containSelected = $option->hasSelectedOption();
                }
            }

            $html .= $option->build();
        }

        if ($info && !$this->isMultiple()) {
            $attr = [
                'value' => '',
            ];
            if (!$containSelected) {
                $attr['selected'] = true;
            }
            if (!$this->isInfoSelectable()) {
                $attr['disabled'] = true;
            }
            $opt = (new Option())->setSelected(true)->setAttributes($attr)->setLabel($info);
            $html = $opt->build() . $html;
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

    /**
     * @return $this
     */
    protected function doEnqueueAssets(): static
    {
        if ($this->getAttribute('data-selectize') !== 'true') {
            return $this;
        }
        if (!wp_style_is('arrayaccess-common')
            || !wp_script_is('selectize')
            || !wp_style_is('selectize')
        ) {
            $defaultAssets = DefaultAssets::getInstance();
            $defaultAssets->init();
            $defaultAssets->enqueueAsset('selectize');
            $attr = $this->getAttribute('data-selectize-options');
            if (is_string($attr)) {
                $attr = json_decode($attr, true);
            }
            $plugins = null;
            if (is_array($attr)) {
                $plugins = $attr['plugins']??null;
                $plugins = is_string($plugins) ? [$plugins] : $plugins;
            }

            if (is_array($plugins)) {
                $plugins = array_filter($plugins, fn ($e) => is_string($e));
                $plugins = array_map('strtolower', $plugins);
                $attr['plugins'] = $plugins;
                $this->setAttribute('data-selectize-options', $attr);
                if (in_array('drag_drop', $plugins, true)
                    && !wp_script_is('jquery-ui-sortable')
                ) {
                    $defaultAssets->enqueueAsset('jquery-ui-sortable');
                }
            }
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
