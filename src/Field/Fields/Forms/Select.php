<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use function esc_html;
use function func_num_args;

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
     * @var array<string, string> key is the value and value is the label
     */
    protected array $options = [];

    /**
     * @var ?string The selected value, if null, the first option will be selected
     */
    protected ?string $selected = null;

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
    ];

    /**
     * @param string|null $selected The selected value, if null, the first option will be selected
     */
    public function __construct(?string $selected = null)
    {
        parent::__construct();
        $this->setSelected($selected);
    }

    /**
     * Set options of select field.
     * @param ?string $selected
     * @return $this
     */
    public function setSelected(?string $selected): static
    {
        $this->selected = $selected;
        return $this;
    }

    /**
     * Get options of select field.
     *
     * @return string|null
     */
    public function getSelected(): ?string
    {
        return $this->selected;
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
     * @return $this
     */
    public function addOption(string|int|float $value, string $label): static
    {
        $value = (string) $value;
        $this->options[$value] = $label;
        return $this;
    }

    /**
     * Check if select field has an option.
     *
     * @param string|int|float $value
     * @return bool
     */
    public function hasOption(string|int|float $value): bool
    {
        $value = (string) $value;
        return isset($this->options[$value]);
    }

    /**
     * Remove an option from select field.
     *
     * @param string|int|float $value
     * @return $this
     */
    public function removeOption(string|int|float $value): static
    {
        $value = (string) $value;
        unset($this->options[$value]);
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
     * @return array<string, string> key is the value and value is the label
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
        $html = '';
        $info = $this->getInfo();
        $containSelected = false;
        $selected = $this->getSelected();
        foreach ($this->getOptions() as $value => $label) {
            /** @noinspection PhpCastIsUnnecessaryInspection */
            $label = (string) $label;
            /** @noinspection PhpCastIsUnnecessaryInspection */
            $value = (string) $value;
            $html .= '<option value="' . esc_attr($value) . '"';
            if ($selected === $value) {
                $html .= ' selected';
                $containSelected = true;
            }
            $html .= '>' . esc_html($label) . '</option>';
        }
        if ($info) {
            $htmlInfo = '<option value=""';
            if (!$containSelected) {
                $htmlInfo .= ' selected';
            }
            if (!$this->isInfoSelectable()) {
                $htmlInfo .= ' disabled';
            }
            $htmlInfo .= '>' . esc_html($info) . '</option>';
            $html = $htmlInfo . $html;
        }
        $attr = $this->getAttributes();
        $attr['html'] = $html;
        $html = HtmlAttributes::createHtmlTag($this->getTagName(), $attr);
        if (!$this->label) {
            return $html;
        }
        return $inline ? '<label class="aa-label aa-label-inline" for="'
                . $this->getId() . '">'
                . '<span class="field-label">'
                . $this->label
                . '</span>'
                . $html
                . '</label>' : '<label class="aa-label" for="'
                . $this->getId() . '">' . $this->label . '</label>' . $html;
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
        // if the type is string|float|int and is in option, check has options.
        if (is_string($value) || is_float($value) || is_int($value)) {
            return $this->hasOption($value);
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getValue(): ?string
    {
        return $this->getSelected();
    }
}
