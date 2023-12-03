<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Block\Interfaces;

use ArrayAccess\WP\Libraries\Core\Service\Interfaces\InitServiceInterface;

interface BlockServiceInterface extends InitServiceInterface
{
    /**
     * Initialize the service
     */
    public function init();

    /**
     * Add block to the service
     *
     * @param BlockInterface $block The block instance
     * @param bool $skipExists Whether to skip if the block is already registered
     * @return bool Whether the block is added
     */
    public function register(BlockInterface $block, bool $skipExists = true) : bool;

    /**
     * Check whether the block is registered
     *
     * @param BlockInterface|string $block The block instance or block name
     * @return bool Whether the block is registered
     */
    public function has(BlockInterface|string $block) : bool;

    /**
     * Get block from the service
     *
     * @param BlockInterface|string $block The block instance or block name
     * @return ?BlockInterface
     */
    public function get(BlockInterface|string $block) : ?BlockInterface;

    /**
     * Remove block from the service
     *
     * @param BlockInterface|string $block The block instance or block name
     * @return ?BlockInterface The removed block
     */
    public function remove(
        BlockInterface|string $block
    ) : ?BlockInterface;

    /**
     * Get lists of registered Blocks
     *
     * @return array<string, BlockInterface>
     */
    public function getBlocks() : array;

    /**
     * Enqueue block widget
     *
     * @return array<string, BlockInterface> The enqueued block widgets
     */
    public function getEnqueuedBlocks() : array;

    /**
     * @param BlockInterface|string $block
     * @return bool Whether the block is enqueued
     */
    public function enqueueBlock(BlockInterface|string $block) : bool;
}
