<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldInterface;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use function array_filter;
use function explode;
use function is_string;
use function sanitize_html_class;
use function spl_object_hash;
use function sprintf;

/**
 * Create a multi input field
 * Build with the same name and different value.
 * Only support radio and checkbox.
 */
class MultiInput extends AbstractField implements MultipleFieldInterface, FormFieldTypeInterface
{
    /**
     * @var string The tag name.
     */
    protected string $tagName = 'div';

    /**
     * @var array<FieldInterface> The fields.
     */
    protected array $fields = [];

    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [];

    /**
     * @param bool $isRadio by default, is true (radio), false for checkbox
     */
    public function __construct(bool $isRadio = true)
    {
        $this->attributes['type'] = $isRadio ? 'radio' : 'checkbox';
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function addField(FieldInterface $field) : ?FieldInterface
    {
        if ($field instanceof FieldInterface
            || $field->getTagName() !== $this->tagName
        ) {
            return null;
        }

        $field = clone $field;
        $field->setAttribute('type', $this->attributes['type']);
        if ($field->getAttribute('type') !== $this->attributes['type']) {
            return null;
        }
        $this->fields[spl_object_hash($field)] = $field;
        return $field;
    }

    /**
     * Remove a field
     *
     * @param FieldInterface $field
     * @return bool
     */
    public function removeField(FieldInterface $field): bool
    {
        if (isset($this->fields[spl_object_hash($field)])) {
            unset($this->fields[spl_object_hash($field)]);
            return true;
        }
        return false;
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
     * @return array<FieldInterface>
     */
    public function getFields() : array
    {
        return $this->fields;
    }

    /**
     * Set checked field
     *
     * @param FieldInterface $field
     * @return $this
     */
    public function setChecked(FieldInterface $field): static
    {
        $id = spl_object_hash($field);
        foreach ($this->fields as $f) {
            if (spl_object_hash($f) === $id) {
                $f->setAttribute('checked', true);
            } else {
                $f->removeAttribute('checked');
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValue(): mixed
    {
        foreach ($this->fields as $field) {
            if ($field->getAttribute('checked')) {
                return $field->getAttribute('value');
            }
        }

        return null;
    }

    /**
     * Create a multi input field with div|wrapper
     *
     * @param bool $inline by default is false (not inline)
     * @param string $wrapper by default is div tag
     * @return string
     */
    public function build(bool $inline = false, string $wrapper = 'div'): string
    {
        $html = '';
        foreach ($this->fields as $field) {
            $attr = $field->getAttributes();
            unset($attr['id']);
            $attr['name'] = $this->getName();
            $attr['type'] = $this->getAttribute('type');
            // use current tag name
            $tag = HtmlAttributes::createHtmlTag($this->getTagName(), $attr);
            $label = $field->getLabel();
            $htmlTag = $label ? sprintf(
                '<label class="%s"><span class="aa-multi-label-name">%s</span>%s</label>',
                'aa-label aa-multi-label',
                $this->label,
                $tag
            ) : sprintf(
                '<label class="%s">%s</label>',
                'aa-label aa-multi-label',
                $tag
            );
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
        $attributes['html'] = $html;
        return HtmlAttributes::createHtmlTag($wrapper, $attributes);
    }
}
