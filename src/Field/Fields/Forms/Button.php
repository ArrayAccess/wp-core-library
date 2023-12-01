<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use function strtolower;

class Button extends AbstractField implements FormFieldTypeInterface
{
    /**
     * @var string The default tag name.
     */
    protected string $tagName = 'button';

    /**
     * @var array|string[] The default class names.
     */
    protected array $defaultClass = [
        'button'
    ];

    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'button',
    ];

    /**
     * @var array Allowed a button type
     */
    protected array $allowedTypes = [
        'button',
        'submit',
        'reset',
    ];

    /**
     * @param string $text button text, support html
     *
     * @return $this
     */
    public function setText(string $text): static
    {
        $this->attributes['html'] = $text;
        return $this;
    }

    /**
     * Set button type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): static
    {
        $type = strtolower(trim($type));
        if (!in_array($type, $this->allowedTypes, true)) {
            return $this;
        }
        $this->attributes['type'] = $type;
        return $this;
    }

    /**
     * Set disabled attribute for button
     *
     * @param bool $val true to set disabled attribute, false to remove
     * @return $this for chaining
     */
    public function setDisabled(bool $val): static
    {
        if ($val === false) {
            $this->removeAttribute('disabled');
            return $this;
        }
        $this->setAttribute('disabled', true);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValue(): mixed
    {
        return $this->getAttribute('value');
    }
}
