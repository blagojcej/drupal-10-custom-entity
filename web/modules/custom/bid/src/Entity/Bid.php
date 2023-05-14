<?php

namespace Drupal\bid\Entity;

use Drupal\bid\BidInterface;
use Drupal\Core\Cache\Cache;
use Drupal\offer\Entity\Offer;
use Drupal\user\EntityOwnerTrait;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;

/**
 * Defines the bid entity class.
 *
 * @ContentEntityType(
 *   id = "bid",
 *   label = @Translation("Bid"),
 *   label_collection = @Translation("Bids"),
 *   label_singular = @Translation("bid"),
 *   label_plural = @Translation("bids"),
 *   label_count = @PluralTranslation(
 *     singular = "@count bids",
 *     plural = "@count bids",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\bid\BidAccessControlHandler",
 *     "form" = {
 *       "delete" = "Drupal\bid\Form\BidDeleteForm",
 *     }
 *   },
 *   links = {
 *     "delete-form" = "/bid/{bid}/delete",
 *   },
 *   base_table = "bid",
 *   revision_table = "bid_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer bid",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *     "published" = "status",
 *     "uid" = "uid"
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   constraints = {
 *     "AllFieldsRequired" = {}
 *   }
 * )
 */
class Bid extends RevisionableContentEntityBase implements BidInterface
{

  use EntityChangedTrait;
  use EntityOwnerTrait;

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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Status'))
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
      ->setDescription(t('The user that created the bid.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    $fields['offer_id'] =
      BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Offer'))
      ->setDescription(t('The offer the bid is for.'))
      ->setSetting('target_type', 'offer')
      ->setSetting('handler', 'default');
    $fields['bid'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Bid amount'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The bid amount in $.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the bid was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the bid was last edited.'));

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
   * Checks if the bid has revisions
   * @return bool
   * True if it has, false if it does not
   */
  public function hasRevisions()
  {
    $id = $this->id();
    $query = \Drupal::entityQuery('bid')
      ->accessCheck(TRUE)
      ->condition('id', $id);
    $count = $query->allRevisions()->count()->execute();
    if ($count > 1) {
      return true;
    } else {
      return false;
    }
  }
  /**
   * Returns list of revision entity ids of the bid. Key is the revision ID.
   * @return array
   */
  public function getRevisionsList()
  {
    $id = $this->id();
    $query = \Drupal::entityQuery('bid')
      ->accessCheck(TRUE)
      ->condition('id', $id);
    $revisions = $query->allRevisions()->execute();
    return $revisions;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE)
  {
    parent::postSave($storage, $update);
    $offer = Offer::load($this->get('offer_id')->target_id);
    Cache::invalidateTags($offer->getCacheTagsToInvalidate());
  }
  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities)
  {
    parent::preDelete($storage, $entities);
    // Invalidate all caches of offers whenever bids are deleted
    foreach ($entities as $entity) {
      $offer = Offer::load($entity->get('offer_id')->target_id);
      if ($offer) {
        Cache::invalidateTags($offer->getCacheTagsToInvalidate());
      }
    }
  }
}
