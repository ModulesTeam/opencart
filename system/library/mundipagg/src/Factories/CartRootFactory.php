<?php

namespace Mundipagg\Factories;

use Mundipagg\Aggregates\Cart\CartRoot;
use Mundipagg\Aggregates\Cart\ProductValueObject;
use Mundipagg\Decorators\AbstractPlatformCartDecorator;
use Mundipagg\DTO\Product;
use Mundipagg\Repositories\RecurrencyProductRepository;

class CartRootFactory
{
    protected $recurrencyProductRepository;

    public function __construct(RecurrencyProductRepository $recurrencyProductRepository)
    {
        $this->recurrencyProductRepository = $recurrencyProductRepository;
    }

    public function createFromPlatformCart(AbstractPlatformCartDecorator $platformCart)
    {
        $cartProducts = $platformCart->getProducts();

        $products = [];

        /**
         * @var Product $cartProduct
         */
        foreach ($cartProducts as $cartProduct) {
            $recurrentProduct = $this->recurrencyProductRepository->getByProductId($cartProduct->getId());

            $type = $this->getType($recurrentProduct);

            for (
                $quantity = $cartProduct->getQuantity();
                $quantity > 0;
                $quantity--
            ) {
                $products[] = $type;
            }
        }

        $cart = new CartRoot();

        foreach ($products as $product) {
            $cart->addProduct($product);
        }

        return $cart;
    }

    protected function getType($recurrentProduct)
    {
        if (!$recurrentProduct) {
            return ProductValueObject::normal();
        }

        if (!$recurrentProduct->isSingle()) {
            return ProductValueObject::plan();
        }

        return ProductValueObject::single(
            $recurrentProduct->getTemplateId(),
            false //@todo Fix it
        );
    }
}