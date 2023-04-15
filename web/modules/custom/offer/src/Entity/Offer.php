<?php

namespace Drupal\offer\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\offer\OfferInterface;
use Drupal\user\EntityOwnerTrait;

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
    switch ($this->get('field_offer_type')->getString()) {
      case 'with_minimum':
        return '$' . $this->get('field_price')->getString();
      case 'no_minimum':
        return 'Start bidding at $0';
    }
    return '';
  }
}
