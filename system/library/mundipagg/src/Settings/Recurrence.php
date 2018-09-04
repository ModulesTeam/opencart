<?php

namespace Mundipagg\Settings;

class Recurrence
{
    private $openCart;

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
    }

    public function getSingleSubscritionEnable()
    {
        return $this->openCart->config->get('payment_mundipagg_recurrence_singleSubscription_status');
    }

    public function getSubscritionByPlanEnable()
    {
        return $this->openCart->config->get('payment_mundipagg_recurrence_subscriptionByPlan_status');
    }

    public function getPaymentUpdateCustomerEnable()
    {
        return $this->openCart->config->get('payment_mundipagg_recurrence_paymentUpdateCustomer_status');
    }

    public function getCreditCardUpdateCustomerEnable()
    {
        return $this->openCart->config->get('payment_mundipagg_recurrence_creditcardUpdateCustomer_status');
    }

    public function getSubscritionInstallmentEnable()
    {
        return $this->openCart->config->get('payment_mundipagg_recurrence_subscriptionInstallment_status');
    }

    public function getCheckoutConflictMessage()
    {
        return $this->openCart->config->get('payment_mundipagg_recurrence_checkoutconflictmessage');
    }

    public function getAllSettings()
    {
        return [
            'recurrence_singleSubscription' => $this->getSingleSubscritionEnable(),
            'recurrence_subscriptionByPlan' => $this->getSubscritionByPlanEnable(),
            'recurrence_paymentUpdateCustomer' => $this->getPaymentUpdateCustomerEnable(),
            'recurrence_creditcardUpdateCustomer' => $this->getCreditCardUpdateCustomerEnable(),
            'recurrence_subscriptionInstallment' => $this->getSubscritionInstallmentEnable(),
            'recurrence_checkoutConflictMessage' => $this->getCheckoutConflictMessage()
        ];
    }

    public function isSingleRecurrenceEnable()
    {
       return $this->getSingleSubscritionEnable() == '1'; 
    }

    public function isSubscriptionByPlanEnable()
    {
        return $this->getSubscritionByPlanEnable() == '1';
    }
}
