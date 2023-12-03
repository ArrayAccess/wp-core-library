<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Ajax\AdminAjax;
use ArrayAccess\WP\Libraries\Core\Ajax\CallbackHandler;
use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\HandlerInterface;
use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;

/**
 * Service to handle admin AJAX.
 */
class Ajax extends AbstractService
{
    /**
     * @var string $serviceName Service name.
     */
    protected string $serviceName = 'ajax';

    /**
     * @var AdminAjax $adminAjax Admin AJAX object.
     */
    private AdminAjax $adminAjax;

    /**
     * @inheritdoc
     */
    public function onConstruct(): void
    {
        $this->description = __('Service to handle admin AJAX.', 'arrayaccess');
    }

    /**
     * Get the admin AJAX object.
     *
     * @return AdminAjax Admin AJAX object.
     */
    public function getAdminAjax(): AdminAjax
    {
        return $this->adminAjax ??= new AdminAjax(
            $this->services->get(Hooks::class)
        );
    }

    /**
     * Add a handler.
     *
     * @param HandlerInterface $handler
     * @return void
     */
    public function add(HandlerInterface $handler): void
    {
        $this->getAdminAjax()->add($handler);
    }

    /**
     * Check if the action is registered.
     *
     * @param string $action
     * @return bool
     */
    public function hasAction(string $action): bool
    {
        return $this->getAdminAjax()->hasAction($action);
    }

    /**
     * Remove action.
     *
     * @param string $action
     * @return void
     */
    public function removeAction(string $action): void
    {
        $this->getAdminAjax()->removeAction($action);
    }

    /**
     * Remove handler.
     *
     * @param HandlerInterface $handler
     * @return void
     */
    public function remove(HandlerInterface $handler): void
    {
        $this->getAdminAjax()->remove($handler);
    }

    /**
     * Check if the handler is registered.
     *
     * @param HandlerInterface $handler
     * @return bool
     */
    public function has(HandlerInterface $handler): bool
    {
        return $this->getAdminAjax()->has($handler);
    }

    /**
     * Add handler with callback
     *
     * @param string $action
     * @param callable $callback
     * @param array|string|null $acceptedMethods
     * @param bool $requireLoggedIn
     * @param int $priority
     * @return CallbackHandler
     */
    public function addCallable(
        string $action,
        callable $callback,
        array|string|null $acceptedMethods = null,
        bool $requireLoggedIn = false,
        int $priority = 10
    ): CallbackHandler {
        $callback = CallbackHandler::create(
            $action,
            $callback,
            $acceptedMethods,
            $requireLoggedIn,
            $priority
        );
        $this->add($callback);
        return $callback;
    }
}
