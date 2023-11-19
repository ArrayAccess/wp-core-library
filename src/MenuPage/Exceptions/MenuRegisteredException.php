<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage\Exceptions;

use ArrayAccess\WP\Libraries\Core\Interfaces\CoreException;
use RuntimeException;
use Throwable;

class MenuRegisteredException extends RuntimeException implements CoreException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        if ($message === '') {
            $message = __('Menu page has already been registered.', 'arrayaccess');
        }
        parent::__construct($message, $code, $previous);
    }
}
