<?php

namespace Drupal\offer\Entity;

use Drupal\Core\Link;
use Drupal\bid\Entity\Bid;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\Markup;
use Drupal\offer\OfferInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\notification\Entity\Notification;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;

/**
 * Defines the offer entity class.
 *
 * @ContentEntityType(
 *   id = "offer",
 *   label = @Translation("Offer"),
 *   label_collection = @Translation("Offers"),
 *   label_singular = @Translation("offer"),
 *   label_plural = @Translation("offers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count offers",
 *     plural = "@count offers",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\offer\OfferAccessControlHandler",
 *     "list_builder" = "Drupal\offer\OfferListBuilder",
 *     "views_data" = "Drupal\offer\OfferViewsData",
 *     "form" = {
 *       "add" = "Drupal\offer\Form\OfferForm",
 *       "step_1" = "Drupal\offer\Form\OfferAddFormStep1",
 *       "step_2" = "Drupal\offer\Form\OfferAddFormStep2",
 *       "step_3" = "Drupal\offer\Form\OfferAddFormStep3",
 *       "edit" = "Drupal\offer\Form\OfferForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "offer",
 *   revision_table = "offer_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer offer",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/offer",
 *     "add-form" = "/offer/add",
 *     "canonical" = "/offer/{offer}",
 *     "edit-form" = "/offer/{offer}/edit",
 *     "delete-form" = "/offer/{offer}/delete",
 *   },
 *   field_ui_base_route = "entity.offer.settings",
 * )
 */
class Offer extends RevisionableContentEntityBase implements OfferInterface
{

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   *
   * Makes the current user the owner of the entity
   */
  public static function preCreate(EntityStorageInterface
  $storage_controller, array &$values)
  {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'uid' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage)
  {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
  {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the offer'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 150)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // This will be configured using UI
    // $fields['message'] = BaseFieldDefinition::create('string_long')
    //   ->setRevisionable(TRUE)
    //   ->setLabel(t('Message'))
    //   ->setRequired(TRUE)
    //   ->setDisplayOptions('form', [
    //     'type' => 'string_textarea',
    //     'weight' => 4,
    //     'settings' => [
    //       'rows' => 12,
    //     ],
    //   ])
    //   ->setDisplayConfigurable('form', TRUE)
    //   ->setDisplayOptions('view', [
    //     'type' => 'string',
    //     'weight' => 0,
    //     'label' => 'above',
    //   ])
    //   ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Offer entity is published.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setLabel(t('User'))
      ->setDescription(t('The user that created the offer.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the offer was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the offer was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner()
  {
    return $this->get('uid')->entity;
  }
  /**
   * {@inheritdoc}
   */
  public function getOwnerId()
  {
    return $this->get('uid')->target_id;
  }

  /**
   * Returns a promotext (fixed, for now!)
   * @return string
   */
  public function getPromoText()
  {
    return 'Be the first!';
  }

  /**
   * Return a price string based on field_price
   * @return string
   */
  public function getPriceAmount()
  {
    $price = '';
    $OfferHasBid = $this->getOfferHighestBid();
    switch ($this->get('field_offer_type')->getString()) {
      case 'with_minimum':
        $price = $this->get('field_price')->getString() . '$';
        break;
      case 'no_minimum':
        $price = 'Start bidding at 0$';
        break;
    }
    if ($OfferHasBid) {
      $price = 'Highest bid currently ' . $OfferHasBid . '$';
    } else {
      $price = 'No bids yet. Grab your chance!';
    }
    return $price;
  }

  /**
   * Returns the highest bid on an offer
   * @return integer $price
   * The price
   */
  public function getOfferHighestBid()
  {
    $bids = [];
    $id = $this->id();
    $query = \Drupal::entityQuery('bid')
      ->accessCheck(TRUE)
      ->condition('offer_id', $id)
      ->sort('bid', 'ASC')
      ->range(NULL, 1);
    $bidIds = $query->execute();
    $price = null;
    foreach ($bidIds as $id) {
      $bid = Bid::load($id);
      $price = $bid->get('bid')->getString();
    }
    return $price;
  }

  /**
   * Returns all bids of an offer
   * @return array $bids
   * Array of bid entities
   */
  public function getOfferBids()
  {
    $bids = [];
    $id = $this->id();
    $query = \Drupal::entityQuery('bid')
      ->accessCheck(TRUE)
      ->condition('offer_id', $id)
      ->sort('bid', 'DESC');
    $bidIds = $query->execute();
    foreach ($bidIds as $id) {
      $bid = Bid::load($id);
      $bids[] = $bid;
    }
    return $bids;
  }

  /**
   * Returns the bid amount
   * @return int
   * a count of the amount of bids
   */
  public function getBidAmount()
  {
    $bids = $this->getOfferBids();
    return count($bids);
  }

  /**
   * Returns a rendered table below an offer
   * @return a drupal table render array
   */
  public function getOfferBiddingTable()
  {
    $bids = $this->getOfferBids();
    $rows = [];
    foreach ($bids as $bid) {
      $price = $bid->get('bid')->getString();
      $owner = $bid->getOwner();
      $ownerName = $owner->getDisplayName();
      $time =
        \Drupal::service('date.formatter')->formatTimeDiffSince($bid->created->value);
      $updates = '';
      if ($bid->hasRevisions()) {
        $revisions = $bid->getRevisionsList();
        // We now have the list of revisions.
        // Let's compare the latest bid with the last revision
        $current_revision_id = $bid->getLoadedRevisionId();
        // We now know the current, we want the one before the current
        // We remove the current from the revisions list
        unset($revisions[$current_revision_id]);
        $last_revision_id = max(array_keys($revisions));
        $revisionBid = \Drupal::entityTypeManager()
          ->getStorage('bid')
          ->loadRevision($last_revision_id);
        $revisionAmount = $revisionBid->get('bid')->getString();
        $priceDifference = $price - $revisionAmount;
        $priceDifference = $priceDifference . '$';
        $updates = '
            <svg width="24px" height="18px" viewBox="0 0 24 24"
            fill="#61f70a" xmlns="http://www.w3.org/2000/svg">
            <path d="M6.1018 16.9814C5.02785 16.9814 4.45387 15.7165
            5.16108 14.9083L10.6829 8.59762C11.3801 7.80079 12.6197 7.80079
            13.3169 8.59762L18.8388 14.9083C19.5459 15.7165 18.972 16.9814
            17.898 16.9814H6.1018Z" fill="#61f70a"/>
            </svg><small style="color:#0444C4">Last raise was ' .
          $priceDifference . '</small>';
      }

      $link = '';
      if ($bid->access('delete')) {
        $url = $bid->toUrl('delete-form');
        $deleteLink = [
          '#type' => 'link',
          '#title' => 'Remove bid',
          '#url' => $url,
          '#attributes' => [
            'class' => [
              'use-ajax', 'button', 'button--small',
              'button--danger'
            ],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => \Drupal\Component\Serialization\Json::encode(['title' => t('Remove bid?'), 'width' => 800,]),
          ],
        ];
        $link = \Drupal::service('renderer')->render($deleteLink);
      }

      $row = [
        Markup::create($ownerName),
        Markup::create($time . ' ago'),
        Markup::create($price . '$' . $updates),
        Markup::create($link)
      ];
      $rows[] = $row;
    }
    $build['table'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#empty' => t('This offer has no bids yet. Grab your chance!')
    ];
    return [
      '#type' => '#markup',
      '#markup' => \Drupal::service('renderer')->render($build)
    ];
  }

  /**
   * Checks if the current user has bids on the current offer
   * @return bool
   * True if it has, false if it doesn't
   */
  public function CurrentUserHasBids()
  {
    $user_id = \Drupal::currentUser()->id();
    $id = $this->id();
    $query = \Drupal::entityQuery('bid')
      ->accessCheck(TRUE)
      ->condition('offer_id', $id)
      ->condition('uid', $user_id);
    $count = $query->count()->execute();
    if ($count > 0) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Returns the current users bid on the offer
   * @return Drupal\bid\Entity\Bid Bid
   * The offer entity
   */
  function currentUserBid()
  {
    $user_id = \Drupal::currentUser()->id();
    $id = $this->id();
    $query = \Drupal::entityQuery('bid')
      ->accessCheck(TRUE)
      ->condition('offer_id', $id)
      ->condition('uid', $user_id);
    $result = $query->execute();
    $bidId = reset($result);
    $bid = Bid::load($bidId);
    return $bid;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities)
  {
    parent::preDelete($storage, $entities);
    // Delete all bids and notifications of the offer that will be deleted
    foreach ($entities as $entity) {
      $entity->deleteAllLinkedBids();
      $entity->deleteAllLinkedNotifications();
      Cache::invalidateTags(['my_offers_user_' . $entity->getOwnerId()]);
    }
  }
  /**
   * Deletes all bids linked to the offer.
   * @param bool $delete
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteAllLinkedBids($delete = FALSE)
  {
    $id = $this->id();
    $query = \Drupal::entityQuery('bid')
      ->accessCheck(TRUE)
      ->condition('offer_id', $id);
    $bidIds = $query->execute();
    foreach ($bidIds as $id) {
      $bid = Bid::load($id);
      $bid->delete();
    }
  }
  /**
   * Deletes all notifications linked to the offer.
   * @param bool $delete
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteAllLinkedNotifications($delete = FALSE)
  {
    $id = $this->id();
    $query = \Drupal::entityQuery('notification')
      ->accessCheck(TRUE)
      ->condition('offer_id', $id);
    $notificationIds = $query->execute();
    foreach ($notificationIds as $id) {
      $notification = Notification::load($id);
      $notification->delete();
    }
  }
}
