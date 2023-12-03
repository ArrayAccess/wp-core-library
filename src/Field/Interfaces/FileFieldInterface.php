<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Interfaces;

interface FileFieldInterface extends FieldInterface
{
    /**
     * Set the accepted media type
     *
     * @param string|array $accept accepted media-type
     * @return $this
     */
    public function setAccept(string|array $accept): static;

    /**
     * Get the accepted media type
     *
     * @return array<string> accepted media-type
     */
    public function getAccept(): array;
}
