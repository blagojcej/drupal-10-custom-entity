<?php

namespace Drupal\offer\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 * displays number of offers.
 */
class MyOffers extends MenuLinkDefault
{
    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        $count = 0;
        if (\Drupal::currentUser()->isAuthenticated()) {
            $offers = \Drupal::entityTypeManager()
                ->getStorage('offer')
                ->loadByProperties(['uid' => \Drupal::currentUser()->id()]);
            $count = count($offers);
            return $this->t('My offers <span class="count-badge">(@count)</span>', ['@count' => $count]);
        } else {
            return null;
        }
    }

    public function getCacheContexts()
    {
        return ['user'];
    }
    public function getCacheTags()
    {
        return ['my_offers_user_' . \Drupal::currentUser()->id()];
    }
}
