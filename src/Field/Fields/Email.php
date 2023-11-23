<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\StandardInputAttributeTrait;
use ArrayAccess\WP\Libraries\Core\Util\Filter;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;

class Email extends AbstractField implements FormFieldTypeInterface
{
    use StandardInputAttributeTrait;

    /**
     * @var string The tag name.
     */
    protected string $tagName = 'input';

    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'email',
    ];

    public function setEmail(string $email): static
    {
        return $this->setValue($email);
    }

    /**
     * Validate the value. Email is valid if it is not empty and is a valid email address.
     *
     * @param mixed|null $value
     * @param bool $allowNull
     * @return bool
     */
    public function isValidValue(mixed $value = null, bool $allowNull = true): bool
    {
        if (is_null($value) && $allowNull) {
            return !HtmlAttributes::isBooleanAttributeEnabled('required', $this->getAttribute('required'));
        }
        // is required
        $required = $this->getAttribute('required');
        if (HtmlAttributes::isBooleanAttributeEnabled('required', $required) && empty($value)) {
            return false;
        }
        return Filter::email($value) !== null;
    }
}
