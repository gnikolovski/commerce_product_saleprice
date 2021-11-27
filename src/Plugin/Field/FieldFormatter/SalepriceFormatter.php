<?php

namespace Drupal\commerce_product_saleprice\Plugin\Field\FieldFormatter;

use Drupal\commerce\Context;
use Drupal\commerce_price\Plugin\Field\FieldFormatter\PriceCalculatedFormatter;
use Drupal\commerce_product_saleprice\Services\SalepriceService;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'saleprice' formatter.
 *
 * @FieldFormatter(
 *   id = "saleprice",
 *   label = @Translation("Saleprice"),
 *   field_types = {
 *     "commerce_price"
 *   }
 * )
 */
class SalepriceFormatter extends PriceCalculatedFormatter implements ContainerFactoryPluginInterface {

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The saleprice service.
   *
   * @var \Drupal\commerce_product_saleprice\Services\SalepriceService
   */
  protected $salepriceService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setConfigFactory($container->get('config.factory'));
    $instance->setSalepriceService($container->get('commerce_product_saleprice.saleprice_service'));
    return $instance;
  }

  /**
   * Sets the config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('commerce_product_saleprice.settings');
  }

  /**
   * Sets the saleprice service.
   *
   * @param \Drupal\commerce_product_saleprice\Services\SalepriceService $saleprice_service
   *   The saleprice service.
   */
  public function setSalepriceService(SalepriceService $saleprice_service) {
    $this->salepriceService = $saleprice_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_savings_number' => FALSE,
      'show_savings_percentage' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['show_savings_number'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show savings number'),
      '#default_value' => $this->getSetting('show_savings_number'),
      '#weight' => -100,
    ];

    $elements['show_savings_percentage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show savings percentage'),
      '#default_value' => $this->getSetting('show_savings_percentage'),
      '#weight' => -99,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('show_savings_number')) {
      $summary[] = $this->t('Show savings number.');
    }

    if ($this->getSetting('show_savings_percentage')) {
      $summary[] = $this->t('Show savings percentage.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if (!$items->isEmpty()) {
      $context = new Context($this->currentUser, $this->currentStore->getStore(), NULL, [
        'field_name' => $items->getName(),
      ]);

      /** @var \Drupal\commerce\PurchasableEntityInterface $purchasable_entity */
      $purchasable_entity = $items->getEntity();
      $original_price = $purchasable_entity->getPrice();
      $resolved_price = $this->chainPriceResolver->resolve($purchasable_entity, 1, $context);
      $savings_price = $purchasable_entity->getPrice()->subtract($resolved_price);
      $savings_percentage = round(100 * ($savings_price->getNumber() / $purchasable_entity->getPrice()->getNumber()));
      $options = $this->getFormattingOptions();

      $on_sale = $this->salepriceService->isOnSale($purchasable_entity);

      $on_sale_until = NULL;
      $on_sale_until_field = $this->config->get('on_sale_until_field');
      if (
        $on_sale === TRUE &&
        !empty($on_sale_until_field) &&
        $purchasable_entity->get($on_sale_until_field)->isEmpty() === FALSE
      ) {
        $store = $context->getStore();
        $on_sale_until = new DrupalDateTime($purchasable_entity->get($on_sale_until_field)->value, $store->getTimezone());
        $on_sale_until = $on_sale_until->format('d-m-Y H:i');
      }

      $elements[0] = [
        '#theme' => 'commerce_product_saleprice',
        '#price' => $this->currencyFormatter->format($resolved_price->getNumber(), $resolved_price->getCurrencyCode(), $options),
        '#original_price' => $this->currencyFormatter->format($original_price->getNumber(), $original_price->getCurrencyCode(), $options),
        '#savings_number' => $this->currencyFormatter->format($savings_price->getNumber(), $savings_price->getCurrencyCode(), $options),
        '#savings_percentage' => $savings_percentage,
        '#show_savings_number' => (bool) $this->getSetting('show_savings_number'),
        '#show_savings_percentage' => (bool) $this->getSetting('show_savings_percentage'),
        '#on_sale' => $on_sale,
        '#on_sale_until' => $on_sale_until,
        '#cache' => [
          'tags' => $purchasable_entity->getCacheTags(),
          'contexts' => Cache::mergeContexts($purchasable_entity->getCacheContexts(), [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
            'country',
          ]),
        ],
      ];
    }

    return $elements;
  }

}
