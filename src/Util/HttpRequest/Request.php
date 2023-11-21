<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Util\HttpRequest;

use ArrayAccess\WP\Libraries\Core\Util\HttpRequest\Abstracts\AbstractHttpRequestUtil;
use ArrayAccess\WP\Libraries\Core\Util\Variables;

class Request extends AbstractHttpRequestUtil
{
    /**
     * @inheritdoc
     */
    public static function all() : array
    {
        return Variables::requests();
    }
}
