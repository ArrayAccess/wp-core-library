<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldSetterInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\MultiFieldSetterTrait;
use function spl_object_hash;

/**
 * Create a multi input field
 * Build with the same name and different value.
 * Only support radio and checkbox.
 */
class MultiInput extends AbstractField implements MultipleFieldSetterInterface, FormFieldTypeInterface
{
    use MultiFieldSetterTrait {
        build as protected buildMultiField;
    }

    /**
     * @var string The tag name.
     */
    protected string $tagName = 'div';

    /**
     * @param bool $isRadio by default, is true (radio), false for checkbox
     */
    public function __construct(?string $name = null, bool $isRadio = true)
    {
        $this->attributes['type'] = $isRadio ? 'radio' : 'checkbox';
        $this->staticType = $this->attributes['type'];
        parent::__construct($name);
    }

    /**
     * Set checked field
     *
     * @param FieldInterface $field
     * @param bool $checked
     * @return $this
     */
    public function setChecked(FieldInterface $field, bool $checked = true): static
    {
        $id = spl_object_hash($field);
        foreach ($this->getFields() as $f) {
            if ($checked && spl_object_hash($f) === $id) {
                $f->setAttribute('checked', true);
            } elseif ($this->staticType !== 'radio' || ! $checked) {
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
        $isRadio = $this->staticType === 'radio';
        $result = null;
        foreach ($this->getFields() as $field) {
            if ($field->getAttribute('checked')) {
                if ($isRadio) {
                    return $field->getAttribute('value');
                }
                $result ??= [];
                $result[] = $field->getAttribute('value');
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function build(?bool $inline = null, mixed $wrapper = 'div'): string
    {
        return $this->buildMultiField($inline, $wrapper, null);
    }
}
