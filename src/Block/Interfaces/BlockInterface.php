<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Block\Interfaces;

interface BlockInterface
{
    /**
     * Default components dependency
     */
    public const DEFAULT_COMPONENTS = [
        'wp-blocks',
        'wp-element',
        'wp-i18n',
        'wp-editor',
    ];

    /**
     * @return bool Whether the block is dispatched
     */
    public function isDispatched() : bool;

    /**
     * @return string The block script url
     */
    public function getUrl() : string;

    /**
     * The block unique id
     *
     * @return string The block id
     */
    public function getId() : string;

    /**
     * @return string
     */
    public function getBlockType() : string;

    /**
     * The block name / title
     *
     * @return string The block name
     */
    public function getTitle() : string;

    /**
     * The block icon
     *
     * @return string
     */
    public function getIcon() : string;

    /**
     * The block version
     *
     * @return ?string The block version
     */
    public function getVersion() : ?string;

    /**
     * @return array<string, string> required block components / dependency
     */
    public function getComponents() : array;

    /**
     * @param BlockServiceInterface $blockService
     */
    public function dispatch(BlockServiceInterface $blockService);
}
