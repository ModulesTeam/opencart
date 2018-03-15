<?php

namespace Mundipagg\Controller;

class Settings
{
    private $openCart;

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
    }

    public function isModuleEnabled()
    {
        return $this->openCart->config->get('payment_mundipagg_status') === '1';
    }

    public function isTestModeEnabled()
    {
        return $this->openCart->config->get('payment_mundipagg_test_mode') === '1';
    }

    public function isLogEnabled()
    {
        return $this->openCart->config->get('payment_mundipagg_log_enabled') === '1';
    }

    public function isAntiFraudEnabled()
    {
        return $this->openCart->config->get('payment_mundipagg_antifraud_status') === '1';
    }

    public function getPaymentTitle()
    {
        return $this->openCart->config->get('payment_mundipagg_title');
    }

    public function getProdSecretKey()
    {
        return $this->openCart->config->get('payment_mundipagg_prod_secret_key');
    }

    public function getTestSecretKey()
    {
        return $this->openCart->config->get('payment_mundipagg_test_secret_key');
    }

    public function getProdPublicKey()
    {
        return $this->openCart->config->get('payment_mundipagg_prod_public_key');
    }

    public function getTestPublicKey()
    {
        return $this->openCart->config->get('payment_mundipagg_test_public_key');
    }

    public function getPassword()
    {
        return '';
    }

    public function getSecretKey()
    {
        if ($this->isTestModeEnabled()) {
            return $this->getTestSecretKey();
        }

        return $this->getProdSecretKey();
    }

    public function getPublicKey()
    {
        if ($this->isTestModeEnabled()) {
            return $this->getTestPublicKey();
        }

        return $this->getProdPublicKey();
    }

    public function getModuleVersion()
    {
        return '1.2.10';
    }

    public function getModuleMetaData()
    {
        return array(
            'module_name' => 'Opencart',
            'module_version' => $this->getModuleVersion()
        );
    }

    public function getSortOrder()
    {
        // @todo: make this customizable from module administration panel
        return 0;
    }

    public function getTerms()
    {
        // @todo: make this customizable from module administration panel
        return '';
    }

    public function getCode()
    {
        // @todo: make this customizable from module administration panel
        return 'mundipagg';
    }

    public function getAntiFraudMinVal()
    {
        return $this->openCart->config->get('payment_mundipagg_antifraud_minval');
    }
}
