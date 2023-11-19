<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Database\Cache;

use Doctrine\Common\Cache\CacheProvider;
use function wp_cache_delete;
use function wp_cache_flush_group;
use function wp_cache_get;
use function wp_cache_set;

/**
 * Cache provider for Doctrine DBAL with WordPress cache
 */
class WPCache extends CacheProvider
{
    public const GROUP = 'array-access-core';

    /**
     * @inheritdoc
     */
    protected function doFetch($id)
    {
        return wp_cache_get($id, self::GROUP);
    }

    /**
     * @inheritdoc
     */
    protected function doContains($id): bool
    {
        wp_cache_get($id, self::GROUP, false, $found);
        return $found;
    }

    /**
     * @inheritdoc
     */
    protected function doSave($id, $data, $lifeTime = 0) : bool
    {
        return wp_cache_set($id, $data, self::GROUP, $lifeTime);
    }

    /**
     * @inheritdoc
     */
    protected function doDelete($id): bool
    {
        return wp_cache_delete($id, self::GROUP);
    }

    /**
     * @inheritdoc
     */
    protected function doFlush() : bool
    {
        return wp_cache_flush_group(self::GROUP);
    }

    /**
     * @inheritdoc
     */
    protected function doGetStats(): ?array
    {
        return null;
    }
}
