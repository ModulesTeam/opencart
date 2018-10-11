<?php

namespace Mundipagg\Aggregates\Cart;

use Unirest\Exception;

class CartRoot
{
    private $cartProducts;

    public function __construct()
    {
        $this->cartProducts = [];
    }

    public function addProduct(ProductValueObject $product)
    {
        $this->verifyPlanConflicts($product);
        $this->verifySingleConflicts($product);
        $this->verifyNormalConflicts($product);

        array_push($this->cartProducts, $product);
    }

    protected function verifyPlanConflicts(ProductValueObject $product)
    {
        if (!$product->equals(ProductValueObject::plan())) {
            return;
        }

        if (count($this->cartProducts) > 0) {
            throw new Exception('You can add a plan only if cart is empty');
        }
    }

    protected function verifySingleConflicts(ProductValueObject $product)
    {
        if ($product->getType() !== ProductValueObject::single()->getType()) {
            return;
        }

        foreach ($this->cartProducts as $cartProduct) {
            if ($cartProduct->equals(ProductValueObject::plan())) {
                throw new Exception("You can't add a single recurrent product with a plan product");
            }

            if (
                $cartProduct->equals(ProductValueObject::normal()) &&
                !$product->getMixed()
            ) {
                throw new Exception(
                    "
                        You can't add a normal product with a single 
                        recurrent product that can't be mixed
                    "
                );
            }

            if (
                $cartProduct->getTemplateId() !== $product->getTemplateId() ||
                $cartProduct->getTemplateId() < 1 ||
                $product->getTemplateId() < 1
            ) {
                throw new Exception(
                    '
                        You can add only more than one single recurrent product 
                        if they have same configuration
                    '
                );
            }
        }
    }

    protected function verifyNormalConflicts(ProductValueObject $product)
    {
        if (!$product->equals(ProductValueObject::normal())) {
            return;
        }

        foreach ($this->cartProducts as $cartProduct) {
            if ($cartProduct->equals(ProductValueObject::plan())) {
                throw new Exception("You can't add a normal product with a plan product");
            }

            if (
                $cartProduct->getType() === ProductValueObject::single()->getType() &&
                !$cartProduct->getMixed()
            ) {
                throw new Exception(
                    "
                        You can't add a normal product with a single 
                        recurrent product that can't be mixed
                    "
                );
            }
        }

    }
}