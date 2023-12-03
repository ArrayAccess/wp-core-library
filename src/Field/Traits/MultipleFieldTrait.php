<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Traits;

use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\LabelAsTitleInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedValueAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use function array_filter;
use function esc_html;
use function explode;
use function force_balance_tags;
use function is_string;
use function sanitize_html_class;
use function sprintf;
use function wp_kses_post;

trait MultipleFieldTrait
{
    /**
     * @var array<FieldInterface> The fields.
     */
    protected array $fields = [];

    /**
     * Validate all fields that should valid
     *
     * @inheritdoc
     * @noinspection PhpNonStrictObjectEqualityInspection
     */
    public function valueIsValid(): bool|FieldInterface
    {
        foreach ($this->getFields() as $field) {
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators
            if ($field == $this) {
                continue;
            }
            if (!$field->valueIsValid()) {
                return $field;
            }
        }
        return true;
    }

    /**
     * Clear all fields
     */
    public function clearFields(): void
    {
        $this->fields = [];
    }

    /**
     * Get the fields
     *
     * @return array<string, FieldInterface>
     */
    public function getFields() : array
    {
        return $this->fields;
    }

    /**
     * @return bool description is after
     */
    protected function descriptionIsAfter(): bool
    {
        return true;
    }

    /**
     * Create a multi input field with div|wrapper
     *
     * @param bool $inline by default is false (not inline)
     * @param string $wrapper by default is div tag
     * @return string
     * @noinspection PhpNonStrictObjectEqualityInspection
     */
    public function build(?bool $inline = null, string $wrapper = 'div'): string
    {
        $html = '';
        foreach ($this->getFields() as $field) {
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators
            if ($field == $this) {
                continue;
            }
            if ($field instanceof MultipleFieldInterface) {
                $html .= $field->build($inline, $wrapper);
                continue;
            }
            $attr = $field->getAttributes();
            unset($attr['id']);
            $name = $this->getName();
            $type = $this->getAttribute('type');
            if ($name
                && ! $field instanceof UnsupportedNameAttributeInterface
                && ! $field instanceof UnsupportedValueAttributeInterface
            ) {
                $attr['name'] = $name;
                if ($type) {
                    $attr['type'] = $type;
                }
            } else {
                unset($attr['name'], $attr['type'], $attr['value']);
            }
            $label = $field->getLabel();
            // use current tag name
            $field->setLabel(null);
            $htmlTag = $field->build();
            $type = $field->getAttribute('type');
            if ($label
                // only support checkbox and radio
                && (
                    $type === 'checkbox' || $type === 'radio'
                )
            ) {
                $htmlTag = sprintf(
                    '<label class="%s"><span class="aa-multi-label-name">%s</span>%s</label>',
                    'aa-label aa-multi-label',
                    str_contains($label, '<') ? force_balance_tags($label) : esc_html($label),
                    $htmlTag
                );
            }

            $html .= $htmlTag;
        }

        if ($html === '') {
            return '';
        }
        $attributes = $this->getAttributes();
        $attributes['class'] ??= [];
        $attrClass = is_string($attributes['class'])
            ? explode(' ', $attributes['class'])
            : (is_iterable($attributes['class']) ? $attributes['class'] : []);
        $attributes['class'] = [
            'aa-multi-input',
            'aa-multi-input-' . $this->getAttribute('type'),
        ];
        if ($inline) {
            $attributes['class'][] = 'aa-multi-input-inline';
        }
        foreach ($attrClass as $val) {
            if (!is_string($val)) {
                continue;
            }
            $attributes['class'][] = sanitize_html_class($val);
        }
        $attributes['class'] = array_filter($attributes['class']);
        $html = $this->doReformatDescription($html);
        $label = $this->getLabel();
        if ($this instanceof LabelAsTitleInterface) {
            $label = $label?:'';
            $divLabel = sprintf(
                '<div class="%s">%s</div>',
                'aa-multi-label-title',
                str_contains($label, '<') ? force_balance_tags($label) : (
                $label ? esc_html($label) : '&nbsp;'
                )
            );
            $sectionWrap = sprintf(
                '<div class="%s">%s</div>',
                'aa-multi-input-wrap',
                $html
            );
            $html = sprintf(
                '<div class="%s">%s%s</div>',
                'aa-section-wrap aa-multi-input-section-wrap',
                $divLabel,
                $sectionWrap
            );
        }

        $attributes['html'] = $html;
        return HtmlAttributes::createHtmlTag($wrapper, $attributes);
    }

    /**
     * Reformatted description from html
     *
     * @param string $html
     * @return string
     */
    protected function doReformatDescription(string $html) : string
    {
        $description = $this->getDescription();
        if ($description !== null) {
            // check if contain html tag > use force_balance_tag
            if (str_contains($description, '<')) {
                $description = wp_kses_post($description);
            }
            $description = '<span class="aa-field-description">' . $description . '</span>';
            if ($this->descriptionIsAfter()) {
                $html .= $description;
            } else {
                $html = $description . $html;
            }
        }
        return $html;
    }

    /**
     * Enqueue assets
     *
     * @return $this
     */
    protected function doEnqueueAssets(): static
    {
        foreach ($this->getFields() as $field) {
            $field->enqueueAssets();
        }
        return $this;
    }

    /**
     * @return int count of fields
     */
    public function count() : int
    {
        return count($this->fields);
    }
}
