<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Database\Cache;

use Doctrine\Common\Cache\CacheProvider;
use function wp_cache_delete;
use function wp_cache_flush_group;
use function wp_cache_get;
use function wp_cache_set;

class WPCache extends CacheProvider
{
    public const GROUP = 'neon-core';

    protected function doFetch($id)
    {
        return wp_cache_get($id, self::GROUP);
    }

    protected function doContains($id): bool
    {
        wp_cache_get($id, self::GROUP, false, $found);
        return $found;
    }

    protected function doSave($id, $data, $lifeTime = 0) : bool
    {
        return wp_cache_set($id, $data, self::GROUP, $lifeTime);
    }

    protected function doDelete($id): bool
    {
        return wp_cache_delete($id, self::GROUP);
    }

    protected function doFlush() : bool
    {
        return wp_cache_flush_group(self::GROUP);
    }

    protected function doGetStats()
    {
        return null;
    }
}
