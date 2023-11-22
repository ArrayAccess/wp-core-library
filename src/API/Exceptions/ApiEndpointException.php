<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\API\Exceptions;

use ArrayAccess\WP\Libraries\Core\API\Interfaces\ApiEndpointExceptionInterface;
use ArrayAccess\WP\Libraries\Core\Exceptions\RuntimeException;

class ApiEndpointException extends RuntimeException implements ApiEndpointExceptionInterface
{
}
