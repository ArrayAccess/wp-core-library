<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;

class SubmitButton extends AbstractField implements FormFieldTypeInterface
{
    /**
     * @var string The default tag name.
     */
    protected string $tagName = 'button';

    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'submit',
    ];

    /**
     * @param string $text button text, support html
     *
     * @return $this
     */
    public function setText(string $text): static
    {
        $this->attributes['text'] = $text;
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
