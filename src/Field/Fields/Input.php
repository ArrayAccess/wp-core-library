<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;

class Input extends AbstractField
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'text'
    ];

    /**
     * @var array|string[] The disallowing remove attributes.
     */
    protected array $disallowRemoveAttributes = [
        'type'
    ];

    /**
     * Set input type
     *
     * @param string $type
     * @return void
     */
    public function setType(string $type): void
    {
        $this->attributes['type'] = trim($type)?:$this->attributes['type'];
    }
}
