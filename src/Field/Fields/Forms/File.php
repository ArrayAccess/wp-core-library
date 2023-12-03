<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FileFieldInterface;
use ArrayAccess\WP\Libraries\Core\Util\Filter;

class File extends Input implements FileFieldInterface
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'file',
    ];

    /**
     * @var ?string The static type.
     */
    protected ?string $staticType = 'file';

    /**
     * @var array|string[] The disallowed attributes.
     */
    protected array $disallowedAttributes = [
        'maxlength',
        'minlength',
        'pattern',
        'placeholder',
        'readonly',
        'required',
        'size',
        'autocomplete',
        'autofocus',
        'list',
        'multiple',
        'step',
        'max',
        'min',
        'cols',
        'rows',
    ];

    /**
     * Set Accepted media type
     *
     * @param string|array $accept
     * @return $this
     */
    public function setAccept(string|array $accept): static
    {
        $accept = Filter::filterAccept($accept);
        if (empty($accept)) {
            $this->removeAttribute('accept');
            return $this;
        }
        $this->setAttribute('accept', $accept);
        return $this;
    }

    /**
     * Get accepted media type
     *
     * @return array accepted media-type
     */
    public function getAccept(): array
    {
        $accept = $this->getAttribute('accept');
        return $accept === null ? [] : Filter::filterAccept($accept);
    }

    /**
     * Set input type
     */
    public function setType(string $type): static
    {
        return $this;
    }

    /**
     * No label for hidden
     *
     * @inheritdoc
     */
    public function getLabel(): ?string
    {
        return null;
    }

    /**
     * disable label
     * @inheritdoc
     */
    public function setLabel(?string $label): static
    {
        $this->label = null;
        return $this;
    }
}
