<?php

namespace Mundipagg\Controller;

use Mundipagg\Order;
use Mundipagg\Model\Order as OrderModel;
use Mundipagg\Settings\CreditCard;
use Mundipagg\Model\Creditcard as CreditCardModel;
use Mundipagg\Log;
use Mundipagg\LogMessages;

class TwoCreditCards
{
    private $openCart;
    private $details;
    private $orderDetails;
    private $model;
    private $cart;
    private $cardId;
    private $order;
    private $multiBuyer;

    private $amount;
    private $amountWithInterest;
    private $interest;
    private $installments;
    private $token;
    private $saveCreditCards;

    public function __construct($openCart, $details, $cart, $multiBuyer = null)
    {
        $this->openCart = $openCart;
        $this->details = $details;
        $this->cart = $cart;

        $this->order = new Order($openCart);
        $this->order->setCustomerModel($this->openCart->model_extension_payment_mundipagg_customer);

        $this->model = new OrderModel($openCart);

        $this->multiBuyer = $multiBuyer;

        $orderId = $this->openCart->session->data['order_id'];
        $this->orderDetails = $this->openCart->model_checkout_order->getOrder($orderId);
    }

    public function processPayment()
    {
        $this->setDetails();
        $this->saveOrderDetails();

        $order = $this->createOrder();
        $this->saveCreditCards($order);

        return $order;
    }

    private function createOrder()
    {
        try {
            $order = $this->order->createOrderForTwoCreditCards(
                $this->orderDetails,
                $this->cart,
                'twoCreditCards',
                $this->amountWithInterest,
                $this->token,
                $this->cardId,
                $this->multiBuyer
            );
        } catch (\Exception $e) {
            Log::create()
                ->error(LogMessages::UNABLE_TO_CREATE_ORDER, __METHOD__)
                ->withOrderId($this->openCart->session->data['order_id'])
                ->withException($e);

            $this
                ->openCart
                ->response
                ->redirect(
                    $this->openCart->url->link('checkout/failure')
                );
        }

        return $order;
    }

    private function saveOrderDetails()
    {
        $amountWithInterest = $this->getAmountWithInterest();
        $interestAmount = $amountWithInterest - $this->orderDetails['total'];

        $this->model->updateAmount($this->orderDetails['order_id'], $amountWithInterest);
        $this->model->updateAmountInOrderTotals($this->orderDetails['order_id'], $amountWithInterest);
        $this->model->insertInterestInOrderTotals($this->orderDetails['order_id'], $interestAmount);
    }

    private function getAmountWithInterest()
    {
        $firstCardAmount =
            intval($this->amount[0]) +
            (
                (floatval($this->interest[0]) / 100) *
                intval($this->amount[0])
            );

        $secondCardAmount =
            intval($this->amount[1]) +
            (
                (floatval($this->interest[1]) / 100 ) *
                intval($this->amount[1])
            );

        return $firstCardAmount + $secondCardAmount;
    }

    private function setDetails()
    {
        $this->setAmount();
        $this->setInterest();
        $this->setAmountWithInterest();
        $this->setToken();
        $this->setCardId();
        $this->setInstallments();
        $this->setSaveCreditCard();

        $this->orderDetails['amountWithInterest'] = $this->getAmountWithInterest();
    }

    private function setAmount()
    {
        $this->amount[] = $this->details['amount-1'];
        $this->amount[] = $this->details['amount-2'];
    }

    private function setInterest()
    {
        $this->interest[] = explode('|', $this->details['payment-details-1'])[2];
        $this->interest[] = explode('|', $this->details['payment-details-2'])[2];
    }

    private function setAmountWithInterest()
    {
        $firstCardAmount =
            intval($this->amount[0]) +
            (
                (floatval($this->interest[0]) / 100) *
                intval($this->amount[0])
            );

        $secondCardAmount =
            intval($this->amount[1]) +
            (
                (floatval($this->interest[1]) / 100 ) *
                intval($this->amount[1])
            );

        $this->amountWithInterest[] = $firstCardAmount;
        $this->amountWithInterest[] = $secondCardAmount;
    }

    private function setToken()
    {
        $this->token[0] = null;
        if (isset($this->details['munditoken-1'])) {
            $this->token[0] = $this->details['munditoken-1'];
        }

        $this->token[1] = null;
        if (isset($this->details['munditoken-2'])) {
            $this->token[1] = $this->details['munditoken-2'];
        }
    }

    private function setCardId()
    {
        if (isset($this->details['mundipaggSavedCreditCard-1'])) {
            $this->cardId[0] = $this->details['mundipaggSavedCreditCard-1'];
        }

        if (isset($this->details['mundipaggSavedCreditCard-2'])) {
            $this->cardId[1] = $this->details['mundipaggSavedCreditCard-2'];
        }
    }

    private function setInstallments()
    {
        $this->installments[] = explode('|', $this->details['payment-details-1'])[0];
        $this->installments[] = explode('|', $this->details['payment-details-2'])[0];

        $this->order->setInstallments($this->installments);
    }

    private function setSaveCreditCard()
    {
        if (isset($this->details['save-this-credit-card-1'])) {
            $this->saveCreditCards[0] = $this->details['save-this-credit-card-1'] === 'on';
        }
        if (isset($this->details['save-this-credit-card-2'])) {
            $this->saveCreditCards[1] = $this->details['save-this-credit-card-2'] === 'on';
        }
    }

    private function saveCreditCards($orderResponse)
    {
        $chargeFirstCard = $orderResponse->charges[0];
        $chargeSecondCard = $orderResponse->charges[1];

        if (!empty($this->saveCreditCards[0]) && $this->saveCreditCards[0]) {
            $this->saveCard(
                $orderResponse->customer->id,
                $chargeFirstCard->lastTransaction->card,
                $orderResponse->code,
                $chargeFirstCard->lastTransaction->card->id
            );
        }

        if (!empty($this->saveCreditCards[1]) && $this->saveCreditCards[1]) {

            $this->saveCard(
                $orderResponse->customer->id,
                $chargeSecondCard->lastTransaction->card,
                $orderResponse->code,
                $chargeSecondCard->lastTransaction->card->id
            );
        }
    }

    private function saveCard($customerId, $card, $code, $cardId)
    {
        $creditCard = new CreditCardModel($this->openCart);

        if (!$creditCard->creditCardExists($cardId)) {
            $creditCard->saveCreditcard($customerId, $card, $code);
        }
    }
}
