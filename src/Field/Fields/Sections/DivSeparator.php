<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Sections;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedValueAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;

class DivSeparator extends AbstractField implements
    UnsupportedValueAttributeInterface,
    UnsupportedNameAttributeInterface
{
    protected string $tagName = 'div';

    /**
     * Default class for the field.
     */
    protected array $defaultClass = [
        'aa-separator'
    ];

    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [];

    /**
     * @var array|string[] The disallowing remove attributes.
     */
    protected array $disallowedAttributes = [
        'type',
        'name',
        'value',
    ];

    /**
     * @param bool $inline
     * @return string
     */
    public function build(?bool $inline = null): string
    {
        $attributes = $this->getAttributes();
        unset($attributes['name'], $attributes['value'], $attributes['type']);
        $html = $attributes['html'] ?? '';
        if ($html === '') {
            $html = '&nbsp;';
        }
        $attributes['html'] = $html;
        return HtmlAttributes::createHtmlTag($this->getTagName(), $attributes);
    }
}
