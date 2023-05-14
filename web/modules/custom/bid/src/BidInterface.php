<?php

namespace Drupal\bid;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a bid entity type.
 */
interface BidInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
