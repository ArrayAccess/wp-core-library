<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

class SubmitButton extends Button
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'submit',
        // default button is p
        'wrapper' => 'p'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->setText(__('Submit', 'arrayaccess'));
    }

    /**
     * Does not support a custom type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): static
    {
        return $this;
    }
}
