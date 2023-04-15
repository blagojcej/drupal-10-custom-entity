<?php

namespace Drupal\offer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an offer entity type.
 */
interface OfferInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
