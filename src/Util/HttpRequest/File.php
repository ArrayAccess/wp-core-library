<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Util\HttpRequest;

use ArrayAccess\WP\Libraries\Core\Util\HttpRequest\Abstracts\AbstractHttpRequestUtil;
use ArrayAccess\WP\Libraries\Core\Util\Variables;

class File extends AbstractHttpRequestUtil
{
    /**
     * @inheritdoc
     */
    public static function all() : array
    {
        return Variables::files();
    }
}
