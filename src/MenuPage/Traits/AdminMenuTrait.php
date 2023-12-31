<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage\Traits;

use function function_exists;
use function is_network_admin;
use function is_user_admin;

trait AdminMenuTrait
{
    /**
     * @var string The slug name to refer to this menu by (should be unique for this menu).
     */
    protected string $slug;

    /**
     * @var string The text to be displayed in the title tags of the page when the menu is selected.
     */
    protected string $menuTitle;

    /**
     * @var string The text to be used for the menu.
     */
    protected string $pageTitle;

    /**
     * @var string The capability required for this menu to be displayed to the user.
     */
    protected string $capability;

    /**
     * @var ?int The position in the menu order this menu should appear.
     */
    protected ?int $position;

    /**
     * @var string The URL to the menu icon.
     */
    protected string $iconUrl;

    /**
     * @var bool Whether to render in network admin
     */
    protected bool $renderInNetworkAdmin = true;

    /**
     * Constructor to use on an admin menu
     *
     * @param string $slug
     * @param string $menuTitle
     * @param string $pageTitle
     * @param string $capability
     * @param string $iconUrl
     * @param ?int $position
     */
    public function __construct(
        string $menuTitle,
        string $pageTitle,
        string $capability,
        string $slug,
        string $iconUrl = '',
        ?int $position = null
    ) {
        $this->slug = $slug;
        $this->menuTitle = $menuTitle;
        $this->pageTitle = $pageTitle;
        $this->capability = $capability;
        $this->iconUrl = $iconUrl;
        $this->position = $position;
    }

    /**
     * @inheritdoc
     */
    public function renderInNetworkAdmin(bool $renderInNetworkAdmin = true): void
    {
        $this->renderInNetworkAdmin = $renderInNetworkAdmin;
    }

    /**
     * @inheritdoc
     */
    public function isRenderInNetworkAdmin(): bool
    {
        return $this->renderInNetworkAdmin;
    }

    /**
     * @inheritdoc
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Set title
     *
     * @param string $menuTitle
     */
    public function setMenuTitle(string $menuTitle): void
    {
        $this->menuTitle = $menuTitle;
    }

    /**
     * @inheritdoc
     */
    public function getMenuTitle(): string
    {
        return $this->menuTitle;
    }

    /**
     * @inheritdoc
     */
    public function setPageTitle(string $pageTitle): void
    {
        $this->pageTitle = $pageTitle;
    }

    /**
     * @inheritdoc
     */
    public function getPageTitle(): string
    {
        return $this->pageTitle;
    }

    /**
     * @inheritdoc
     */
    public function setCapability(string $capability): void
    {
        $this->capability = $capability;
    }

    /**
     * @inheritdoc
     */
    public function getCapability(): string
    {
        return $this->capability;
    }

    /**
     * @inheritdoc
     */
    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    /**
     * @inheritdoc
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * @inheritdoc
     */
    public function setIconUrl(string $iconUrl): void
    {
        $this->iconUrl = $iconUrl;
    }

    /**
     * @inheritdoc
     */
    public function getIconUrl(): string
    {
        return $this->iconUrl;
    }

    /**
     * @inheritdoc
     */
    public function isAllowed(): bool
    {
        return !function_exists('current_user_can') || current_user_can($this->getCapability());
    }

    /**
     * @inheritdoc
     */
    public function isNetworkAdmin() : bool
    {
        return function_exists('is_network_admin') && is_network_admin();
    }

    /**
     * @inheritdoc
     */
    public function getHookAction(): string
    {
        // see menu.php
        return $this->isNetworkAdmin()
            ? 'network_admin_menu'
            : (is_user_admin()
                ? 'user_admin_menu'
                : 'admin_menu'
            );
    }
}
