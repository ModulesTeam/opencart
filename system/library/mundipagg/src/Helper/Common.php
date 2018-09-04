<?php
namespace Mundipagg\Helper;

/**
 * Class Common
 * Helpfull functions
 * @package Mundipagg\Helper
 */
class Common
{
    private $openCart;

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
    }

    /**
     * @param string $snake
     * @return string
     */
    public function fromSnakeToCamel($snake)
    {
        $result = [];
        $length = strlen($snake);

        for ($i = 0; $i < $length ; $i++) {
            if ($snake[$i] === '_') {
                $result[] = ucfirst($snake[++$i]);
            } else {
                $result[] = $snake[$i];
            }
        }

        return implode($result);
    }

    public function currencyFormat($price, $orderInfo, $productTax = 0)
    {
        $tax = $this->openCart->config->get('config_tax') ? $productTax : 0;

        return $this->openCart->currency->format(
            $price + $tax,
            $orderInfo['currency_code'],
            $orderInfo['currency_value']
        );
    }
}