<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Abstracts;

use ArrayAccess\WP\Libraries\Core\Service\Interfaces\ServiceInterface;
use ArrayAccess\WP\Libraries\Core\Service\Traits\ServiceTrait;

/**
 * AbstractService class that help to create service of various service objects.
 */
abstract class AbstractService implements ServiceInterface
{
    use ServiceTrait;
}
