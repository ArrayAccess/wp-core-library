<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Util\ImageMimeType;
use function explode;
use function is_string;
use function str_contains;
use function trim;

class ImageFile extends File
{
    protected array $attributes = [
        'type' => 'file',
        'accept' => ['image/*'],
    ];

    /**
     * Set accepted images type
     *
     * @param array|string $imageType
     * @return $this
     */
    public function setAcceptedImages(array|string $imageType) : static
    {
        if (is_string($imageType)) {
            $imageType = trim($imageType);
            if ($imageType === '') {
                return $this->setAccept('image/*');
            }
            $imageType = [$imageType];
        }
        $images = [];
        foreach ($imageType as $type) {
            if (!is_string($type)) {
                continue;
            }
            $type = trim(trim($type), '.');
            if ($type === '') {
                continue;
            }
            if (str_contains($type, '/')) {
                $type = explode('/', $type, 2)[1];
            }
            if ($type === '*') {
                return $this->setAccept('image/*');
            }
            $type = strtolower($type);
            $mimetype = ImageMimeType::IMAGE_MIMETYPE_LIST[$type] ?? null;
            if ($mimetype === null) {
                continue;
            }
            $images[] = $type;
        }
        if (empty($images)) {
            return $this->setAccept('image/*');
        }
        return $this->setAccept($images);
    }
}
