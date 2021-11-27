<?php

namespace Drupal\commerce_product_saleprice\Services;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Class SalepriceService.
 */
class SalepriceService  {

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs SalepriceService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('commerce_product_saleprice.settings');
  }

  /**
   * Checks if product is on sale.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation
   *   The product variation.
   *
   * @return bool
   *   TRUE if product is on sale, FALSE otherwise.
   */
  public function isOnSale(ProductVariationInterface $product_variation) {
    $saleprice_field = $this->config->get('saleprice_field');
    $on_sale_field = $this->config->get('on_sale_field');
    $on_sale_until_field = $this->config->get('on_sale_until_field');

    // Bail if we don't have 'Sale Price' field configured or it's empty.
    if (
      empty($saleprice_field) ||
      $product_variation->get($saleprice_field)->isEmpty()
    ) {
      return FALSE;
    }

    // Bail if we have 'On Sale' field configured and it's not checked.
    if (
      !empty($on_sale_field) &&
      empty($product_variation->get($on_sale_field)->value)
    ) {
      return FALSE;
    }

    // Bail if we have 'On sale until' field configured and it's not empty and
    // date has passed.
    if (
      !empty($on_sale_until_field) &&
      !empty($product_variation->get($on_sale_until_field)->value)
    ) {
      $stores = $product_variation->getStores();
      $store = reset($stores);
      $on_sale_until = new DrupalDateTime($product_variation->get($on_sale_until_field)->value, DateTimeItemInterface::STORAGE_TIMEZONE);
      $on_sale_until->setTimeZone(new \DateTimeZone($store->getTimezone()));
      $now = new DrupalDateTime('now', $store->getTimezone());
      if ($now > $on_sale_until) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
