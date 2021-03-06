<?php

namespace Drupal\commerce_product_saleprice\Resolvers;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Resolver\PriceResolverInterface;

/**
 * Class SalePriceResolver.
 *
 * @package Drupal\commerce_product_saleprice\Resolvers
 */
class SalePriceResolver implements PriceResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    // Make sure that product variation has a field called Sale price.
    if (!$entity->hasField('field_sale_price')) {
      return;
    }

    if ($entity->get('field_sale_price')->isEmpty()) {
      return;
    }

    /** @var \Drupal\commerce_price\Price $sale_price */
    $sale_price = $entity->get('field_sale_price')->first()->toPrice();
    $sale_price_number = $sale_price->getNumber();
    $sale_price_currency_code = $sale_price->getCurrencyCode();

    if (!$sale_price_number) {
      return;
    }

    return new Price($sale_price_number, $sale_price_currency_code);
  }

}
