<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Exceptions;

use ArrayAccess\WP\Libraries\Core\Interfaces\CoreExceptionInterface;

class ValueError extends \ValueError implements CoreExceptionInterface
{
}
