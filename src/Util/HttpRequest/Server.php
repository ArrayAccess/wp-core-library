<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Util\HttpRequest;

use ArrayAccess\WP\Libraries\Core\Util\HttpRequest\Abstracts\AbstractHttpRequestUtil;
use ArrayAccess\WP\Libraries\Core\Util\Variables;
use function strtoupper;

class Server extends AbstractHttpRequestUtil
{
    /**
     * Get Request Method
     *
     * @return string The request method.
     */
    public static function method() : string
    {
        return strtoupper(self::string('REQUEST_METHOD', 'GET'));
    }

    /**
     * @inheritdoc
     */
    public static function all() : array
    {
        return Variables::servers();
    }
}
