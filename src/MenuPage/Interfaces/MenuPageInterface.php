<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces;

/**
 * Create admin menu interface with user by slug, capability & position
 * This admin menu to add admin menu page in WordPress dashboard
 * Admin menu positions are:
 *
 *      | Position | Menu Item  |
 *      | -------- | ---------- |
 *      | 2        | Dashboard  |
 *      | 4        | Separator  |
 *      | 5        | Posts      |
 *      | 10       | Media      |
 *      | 15       | Links      |
 *      | 20       | Pages      |
 *      | 25       | Comments   |
 *      | 59       | Separator  |
 *      | 60       | Appearance |
 *      | 65       | Plugins    |
 *      | 70       | Users      |
 *      | 75       | Tools      |
 *      | 80       | Settings   |
 *      | 99       | Separator  |
 *
 * @link https://developer.wordpress.org/reference/functions/add_menu_page/
 * @link https://developer.wordpress.org/reference/functions/add_submenu_page/
 */
interface MenuPageInterface
{
    /**
     * The Default capability is manage_options
     */
    public const DEFAULT_CAPABILITY = 'manage_options';

    /**
     * Roles and Capabilities
     * This constant for reference only
     *
     * @link https://wordpress.org/documentation/article/roles-and-capabilities
     */
    public const ROLE_CAPABILITIES = [
        'activate_plugins',
        'add_users',
        'create_users',
        'delete_others_pages',
        'delete_others_posts',
        'delete_pages',
        'delete_plugins',
        'delete_posts',
        'delete_private_pages',
        'delete_private_posts',
        'delete_published_pages',
        'delete_published_posts',
        'delete_themes',
        'delete_users',
        'edit_dashboard',
        'edit_files',
        'edit_others_pages',
        'edit_others_posts',
        'edit_pages',
        'edit_plugins',
        'edit_posts',
        'edit_private_pages',
        'edit_private_posts',
        'edit_published_pages',
        'edit_published_posts',
        'edit_theme_options',
        'edit_themes',
        'edit_users',
        'export',
        'import',
        'install_plugins',
        'install_themes',
        'list_users',
        'manage_categories',
        'manage_links',
        'manage_options',
        'moderate_comments',
        'promote_users',
        'publish_pages',
        'publish_posts',
        'read',
        'read_private_pages',
        'read_private_posts',
        'remove_users',
        'switch_themes',
        'unfiltered_html',
        'unfiltered_upload',
        'update_core',
        'update_plugins',
        'update_themes',
        'upload_files',
    ];

    /**
     * Roles and Capabilities
     */
    public const ROLE_NAME_LIST = [
        'super_admin',
        'administrator',
        'editor',
        'author',
        'contributor',
        'subscriber',
    ];

    public function __construct(
        string $menuTitle,
        string $pageTitle,
        string $capability,
        string $slug,
        string $iconUrl = '',
        ?int $position = null
    );

    /**
     * Get Menu Slug
     *
     * @return string
     */
    public function getSlug(): string;

    /**
     * Set menu title
     */
    public function setMenuTitle(string $menuTitle);

    /**
     * Get menu title
     *
     * @return string
     */
    public function getMenuTitle(): string;

    /**
     * Set page title
     *
     * @param string $pageTitle
     */
    public function setPageTitle(string $pageTitle);

    /**
     * Get page title
     *
     * @return string
     */
    public function getPageTitle(): string;

    /**
     * Set menu capability
     *
     * @param string $capability
     */
    public function setCapability(string $capability);

    /**
     * Get menu capability
     *
     * @return string
     */
    public function getCapability(): string;

    /**
     * Set menu position
     *
     * @param ?int $position
     */
    public function setPosition(?int $position);

    /**
     * Menu position
     *
     * @return ?int
     */
    public function getPosition(): ?int;

    /**
     * Set menu icon url
     *
     * @param string $iconUrl
     */
    public function setIconUrl(string $iconUrl);

    /**
     * Get menu icon url
     *
     * @return string
     */
    public function getIconUrl(): string;

    /**
     * Set menu icon url
     *
     * @return bool
     */
    public function isAllowed(): bool;
}
