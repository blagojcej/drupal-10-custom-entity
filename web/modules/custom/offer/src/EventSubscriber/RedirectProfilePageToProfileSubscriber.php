<?php

namespace Drupal\offer\EventSubscriber;

use Drupal\Core\Url;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class ResponseSubscriber.
 * Redirects the user view to the user edit form
 *
 * @package Drupal\offer
 */
class RedirectProfilePageToProfileSubscriber implements EventSubscriberInterface
{


    /**
     * Drupal\Core\Routing\CurrentRouteMatch definition.
     *
     * @var Drupal\Core\Routing\CurrentRouteMatch
     */
    protected $routeMatch;

    /**
     * Constructor.
     */
    public function __construct(CurrentRouteMatch $current_route_match)
    {
        $this->routeMatch = $current_route_match;
    }

    /**
     * {@inheritdoc}
     */
    static function getSubscribedEvents()
    {
        $events['kernel.response'] = ['handle'];

        return $events;
    }

    /**
     * This method is called whenever the kernel.response event is
     * dispatched.
     *
     * @param ResponseEvent $event
     */
    public function handle(ResponseEvent $event)
    {
        $route_name = $this->routeMatch->getRouteName();
        switch ($route_name) {
            case 'entity.user.canonical':
                $user = $this->routeMatch->getParameter('user');
                $routeName = 'entity.user.edit_form';
                $routeParameters = ['user' => $user->id()];
                $url = Url::fromRoute($routeName, $routeParameters)->toString();
                $response =  new RedirectResponse($url);
                $event->setResponse($response);
        }
    }
}
