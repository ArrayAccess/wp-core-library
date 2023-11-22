<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\API\Interfaces;

use ArrayAccess\WP\Libraries\Core\Interfaces\CoreExceptionInterface;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;

interface ApiEndpointExceptionInterface extends CoreExceptionInterface
{
    public const ENDPOINT_ALREADY_REGISTERED = E_USER_WARNING;

    public const INVALID_ENDPOINT_NAMESPACE = E_USER_ERROR;

    public const INVALID_ENDPOINT_METHODS = E_USER_NOTICE;

    public const INVALID_ENDPOINT_NESTED = E_USER_ERROR;
}
