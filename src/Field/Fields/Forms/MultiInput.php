<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\MultiFieldTrait;
use function spl_object_hash;

/**
 * Create a multi input field
 * Build with the same name and different value.
 * Only support radio and checkbox.
 */
class MultiInput extends AbstractField implements MultipleFieldInterface, FormFieldTypeInterface
{
    use MultiFieldTrait;

    /**
     * @var string The tag name.
     */
    protected string $tagName = 'div';

    /**
     * @param bool $isRadio by default, is true (radio), false for checkbox
     */
    public function __construct(bool $isRadio = true)
    {
        $this->attributes['type'] = $isRadio ? 'radio' : 'checkbox';
        parent::__construct();
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
}
