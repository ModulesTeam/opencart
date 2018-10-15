<?php

namespace Mundipagg\Decorators;

use Mundipagg\DTO\Product;

class OpencartPlatformCartDecorator extends AbstractPlatformCartDecorator
{
    protected $cart;

    /**
     * OpencartPlatformCartDecorator constructor.
     * @param $cart
     */
    public function __construct($cart)
    {
        $this->cart = $cart;
    }

    public function getProducts()
    {
        $opencartProducts = $this->cart->getProducts();
        $products = [];

        foreach ($opencartProducts as $opencartProduct) {
            $products[] = new Product(
                $opencartProduct['product_id'],
                $opencartProduct['quantity']
            );
        }

        return $products;
    }
}