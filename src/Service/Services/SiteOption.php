<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use function delete_site_option;
use function get_site_option;
use function update_site_option;

/**
 * Option service to create and manage options with object oriented itself.
 */
class SiteOption extends Option
{
    /**
     * @var string The service name.
     */
    protected string $serviceName = 'siteOption';

    /**
     * @inheritdoc
     */
    protected function onConstruct(): void
    {
        $this->description = __(
            'Option service to create and manage by site options with object oriented itself.',
            'arrayaccess'
        );
    }

    /**
     * @inheritdoc
     */
    protected function getOptionFrom(?string $optionName, mixed $default = []): mixed
    {
        if ($optionName === null) {
            return $default;
        }
        return get_site_option($optionName, $default);
    }

    /**
     * @inheritdoc
     */
    protected function saveOptionFrom(?string $optionName, array $value): bool
    {
        if ($optionName === null) {
            return false;
        }
        return update_site_option($optionName, $value);
    }

    /**
     * @inheritdoc
     */
    protected function deleteOptionFrom(?string $optionName): bool
    {
        if ($optionName === null) {
            return false;
        }
        return delete_site_option($optionName);
    }
}
