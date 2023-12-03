<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

class MultiSelect extends Select
{
    protected bool $multiple = true;

    /**
     * @inheritdoc
     */
    public function isMultiple(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function setMultiple(bool $multiple): static
    {
        $this->multiple = true;
        return $this;
    }
}
