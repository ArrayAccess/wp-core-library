<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Abstracts;

use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Option;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldInterface;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use function array_map;
use function array_values;
use function is_array;
use function is_scalar;
use function iterator_to_array;

abstract class AbstractOptionCollection extends AbstractField implements MultipleFieldInterface
{
    /**
     * @var string The tag name.
     */
    protected string $tagName = '';

    /**
     * @var string|null The static type.
     */
    protected ?string $staticType = null;

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
     * @var array<Option>
     */
    protected array $options = [];

    /**
     * @param Option $option
     * @return $this
     */
    public function add(Option $option): static
    {
        $this->options[] = $option;
        return $this;
    }

    /**
     * Get the options by value.
     *
     * @param mixed ...$values
     * @return array<Option>
     */
    public function getOptionsByValue(mixed ...$values): array
    {
        $options = [];
        foreach ($this->getOptions() as $value) {
            if ($value->getValue() === $values) {
                $options[] = $value;
            }
        }
        return $options;
    }

    public function setAttribute(string $attributeName, mixed $value): static
    {
        $attributeName = HtmlAttributes::filterAttributeName($attributeName);
        if ($attributeName === 'selected' || $attributeName === 'value') {
            $value = is_array($value) ? array_values($value) : (
            is_iterable($value)
                ? iterator_to_array($value)
                : [$value]
            );
            $this->setSelectedOption(...array_values($value));
            return $this;
        }
        return parent::setAttribute($attributeName, $value);
    }

    /**
     * @param scalar|Option ...$options
     * @return $this
     */
    public function setSelectedOption(mixed ...$options): static
    {
        array_map(fn (Option $opt) => $opt->setSelected(false), $this->getOptions());
        foreach ($options as $option) {
            if ($option instanceof Option) {
                $option->setSelected(true);
            }
            if (!is_scalar($option)) {
                continue;
            }
            foreach ($this->getOptionsByValue($option) as $opt) {
                $opt->setSelected(true);
            }
        }
        return $this;
    }

    public function addSelectedOption(mixed ...$options): static
    {
        foreach ($options as $option) {
            if ($option instanceof Option) {
                $option->setSelected(true);
            }
            if (!is_scalar($option)) {
                continue;
            }
            foreach ($this->getOptionsByValue($option) as $opt) {
                $opt->setSelected(true);
            }
        }
        return $this;
    }

    /**
     * Remove options.
     *
     * @param Option $option
     * @return $this
     */
    public function remove(Option $option): static
    {
        $this->options = array_values(array_filter($this->options, fn (Option $opt) => $opt !== $option));
        return $this;
    }

    /**
     * @return array<Option>
     */
    public function getSelectedOptions() : array
    {
        $options = [];
        foreach ($this->getOptions() as $option) {
            if ($option->isSelected()) {
                $options[] = $option;
            }
        }
        return $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasOption(mixed $option): bool
    {
        if ($option instanceof Option) {
            return in_array($option, $this->getOptions(), true);
        }
        foreach ($this->getOptions() as $opt) {
            if ($opt->getValue() === $option) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param bool|null $inline
     * @return string
     */
    public function build(?bool $inline = null): string
    {
        $html = '';
        $rendered = [];
        foreach ($this->getOptions() as $option) {
            $value = $option->getValue();
            if ($value === null) {
                continue;
            }
            if (in_array($value, $rendered, true)) {
                continue;
            }
            // prevent duplicate rendering
            $rendered[] = $value;
            $html .= $option->build($inline);
        }

        if ($this->tagName !== '') {
            $attributes = $this->getAttributes();
            $attributes['html'] = $html;
            $attributes = $this->doAppendAttributes($attributes);
            $html = HtmlAttributes::createHtmlTag(
                $this->getTagName(),
                $attributes
            );
        }
        return $this->doFinalBuild($html);
    }

    /**
     * Check if has selected option.
     *
     * @return bool
     */
    public function hasSelectedOption(): bool
    {
        foreach ($this->getOptions() as $option) {
            if ($option->isSelected()) {
                return true;
            }
        }
        return false;
    }
    /**
     * @param string $html
     * @param bool|null $inline
     * @return string
     */
    protected function doFinalBuild(string $html, ?bool $inline = null): string
    {
        return $html;
    }

    /**
     * Append attributes on build.
     *
     * @param array $attributes
     * @return array The appended attributes.
     */
    protected function doAppendAttributes(array $attributes) : array
    {
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->getOptions());
    }

    /**
     * @inheritdoc
     */
    public function clearFields(): void
    {
        $this->options = [];
    }

    /**
     * @inheritdoc
     */
    public function getFields(): array
    {
        return $this->getOptions();
    }
}
