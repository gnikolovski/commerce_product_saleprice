<?php

namespace Drupal\commerce_product_saleprice\Resolvers;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Resolver\PriceResolverInterface;

/**
 * Class SalepriceResolver
 *
 * @package Drupal\commerce_product_saleprice\Resolvers
 */
class SalepriceResolver implements PriceResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    // Make sure that product variation has a field called Saleprice.
    if (!$entity->hasField('field_saleprice')) {
      return;
    }

    if ($entity->get('field_saleprice')->isEmpty()) {
      return;
    }

    /** @var \Drupal\commerce_price\Price $sale_price */
    $sale_price = $entity->get('field_saleprice')->first()->toPrice();
    $sale_price_number = $sale_price->getNumber();
    $sale_price_currency_code = $sale_price->getCurrencyCode();

    if (!$sale_price_number || $sale_price_number == 0) {
      return;
    }

    return new Price($sale_price_number, $sale_price_currency_code);
  }

}
