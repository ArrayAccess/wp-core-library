<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Hook;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\ServiceInterface;
use ArrayAccess\WP\Libraries\Core\Service\Traits\ServiceTrait;
use function __;

/**
 * Service hook that helps to handle the hooks outside of core WordPress hooks.
 *
 * @uses WP_Hook
 * This object does not support 'all' hook name
 */
class Hooks extends Hook implements ServiceInterface
{
    use ServiceTrait;

    /**
     * @var string the service name
     */
    protected string $serviceName = 'hooks';

    /**
     * @inheritdoc
     */
    protected function onConstruct(): void
    {
        $this->description = __(
            'Service hook that helps to handle the hooks outside of core WordPress hooks.',
            'arrayaccess'
        );
    }
}
