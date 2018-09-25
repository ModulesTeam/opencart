<?php

namespace Mundipagg\Settings;

class Boleto
{
    private $openCart;

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
    }

    public function isEnabled()
    {
        return $this->getStatus() === '1';
    }

    public function getStatus()
    {
        return $this->openCart->config->get('payment_mundipagg_boleto_status');
    }

    public function getPaymentTitle()
    {
        return $this->openCart->config->get('payment_mundipagg_boleto_title');
    }

    public function getTitle()
    {
        return $this->openCart->config->get('payment_mundipagg_boleto_title');
    }

    public function getName()
    {
        return $this->openCart->config->get('payment_mundipagg_boleto_name');
    }

    public function getBank()
    {
        return $this->openCart->config->get('payment_mundipagg_boleto_bank');
    }

    public function getDueAt()
    {
        return $this->openCart->config->get('payment_mundipagg_boleto_due_date');
    }

    public function getInstructions()
    {
        return $this->openCart->config->get('payment_mundipagg_boleto_instructions');
    }

    public function getCheckoutInstructions()
    {
        return $this->openCart->config->get('payment_mundipagg_boleto_checkout_instructions');
    }

    public function getDueDate()
    {
        return date(
            "Y-m-d",
            mktime(
                0,
                0,
                0,
                date("m"),
                date("d") + $this->getDueAt(),
                date("Y")
            )
        );
    }

    public function getBoletoPageInfo()
    {
        $this->openCart->load->language('extension/payment/mundipagg');

        $boletoText =  $this->openCart->language->get('boleto');

        $checkoutInstructions  = $this->getCheckoutInstructions();
        if ($checkoutInstructions) {
            $boletoText['instructions'] = $checkoutInstructions;
        }

        $info = array(
            'boletoStatus' => $this->getStatus(),
            'boletoText' => $boletoText
        );

        return $info;
    }

    public function getAllSettings()
    {
        return array(
            'boleto_enabled' => $this->getStatus(),
            'boleto_title' => $this->getTitle(),
            'boleto_name' => $this->getName(),
            'boleto_bank' => $this->getBank(),
            'boleto_instructions' => $this->getInstructions(),
            'boleto_due_date' => $this->getDueAt()
        );
    }
}