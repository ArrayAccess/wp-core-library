<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

class Hidden extends Input
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'hidden',
    ];

    /**
     * @var ?string The static type.
     */
    protected ?string $staticType = 'hidden';

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
