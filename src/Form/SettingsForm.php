<?php

namespace Drupal\commerce_product_saleprice\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Commerce Product Saleprice settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs SettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   The entity bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_bundle_info, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityBundleInfo = $entity_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_product_saleprice_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_product_saleprice.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['saleprice_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Saleprice field'),
      '#options' => $this->getFields('commerce_price'),
      '#description' => $this->t('This must be a field of Price type on Product variation.'),
      '#default_value' => $this->config('commerce_product_saleprice.settings')->get('saleprice_field'),
    ];

    $form['on_sale_field'] = [
      '#type' => 'select',
      '#title' => $this->t('On sale field'),
      '#options' => $this->getFields('boolean'),
      '#description' => $this->t('This must be a field of Boolean type on Product variation.'),
      '#default_value' => $this->config('commerce_product_saleprice.settings')->get('on_sale_field'),
    ];

    $form['on_sale_until_field'] = [
      '#type' => 'select',
      '#title' => $this->t('On sale until'),
      '#options' => $this->getFields('datetime'),
      '#description' => $this->t('This must be a field of Date type on Product variation.'),
      '#default_value' => $this->config('commerce_product_saleprice.settings')->get('on_sale_until_field'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $saleprice_field = $form_state->getValue('saleprice_field') == '_none' ? '' : $form_state->getValue('saleprice_field');
    $on_sale_field = $form_state->getValue('on_sale_field') == '_none' ? '' : $form_state->getValue('on_sale_field');
    $on_sale_until_field = $form_state->getValue('on_sale_until_field') == '_none' ? '' : $form_state->getValue('on_sale_until_field');

    $this->config('commerce_product_saleprice.settings')
      ->set('saleprice_field', $saleprice_field)
      ->set('on_sale_field', $on_sale_field)
      ->set('on_sale_until_field', $on_sale_until_field)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets a list of fields for provided field type.
   *
   * @param string $field_type
   *   The field type.
   *
   * @return array
   *   An array of fields.
   */
  private function getFields($field_type) {
    $fields = [];
    $fields['_none'] = '- None -';

    $bundles = $this->entityBundleInfo->getBundleInfo('commerce_product_variation');

    foreach ($bundles as $bundle => $data) {
      $field_definitions = $this->entityFieldManager
        ->getFieldDefinitions('commerce_product_variation', $bundle);

      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
      foreach ($field_definitions as $field) {
        if ($field->getType() == $field_type) {
          $fields[$field->getName()] = $field->getLabel();
        }
      }
    }

    return $fields;
  }

}
