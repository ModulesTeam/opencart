<?php

namespace Mundipagg\Model\Api;

use Mundipagg\Aggregates\Template\DueValueObject;
use Mundipagg\Settings\General as GeneralSettings;
use MundiAPILib\MundiAPIClient;
use MundiAPILib\Models\CreatePlanRequest;

class Plan
{
    public $openCart;

    public function __construct($opencart)
    {
        $this->openCart = $opencart;
    }

    public function createPlan($plan)
    {
        $request = $this->createPlanRequest($plan);

        $generalSettings = new GeneralSettings($this->openCart);
        $planApi = new MundiAPIClient($generalSettings->getSecretKey(), $generalSettings->getPassword());
//        $planApi = new MundiAPIClient("teste", "t");

        return $planApi->getPlans()->createPlan($request->jsonSerialize());
    }

    public function updatePlan($plan)
    {
        //@todo
    }

    public function deletePlan($plan)
    {
        //@todo
    }

    protected function createPlanRequest($plan)
    {
        $request = new CreatePlanRequest();

        $name = $plan->getTemplate()->getTemplate()->getName();

        $request->name                  = $name;
        $request->description           = $plan->getTemplate()->getTemplate()->getDescription();
        $request->shippable             = $this->hasShipping($plan->getSubProducts());
        $request->paymentMethods        = $this->getPaymentMethodsFromTemplate($plan->getTemplate());
        $request->statementDescriptor   = substr($name, 0, 22);
        $request->currency              = $this->getCurrency()['code'];
        $request->cycles                = $plan->getTemplate()->getRepetitions()[0]->getCycles();
        $request->interval              = $plan->getTemplate()->getRepetitions()[0]->getIntervalTypeApiValue();
        $request->intervalCount         = $plan->getTemplate()->getRepetitions()[0]->getFrequency();
        $request->billingType           = $plan->getTemplate()->getDueAt()->getDueApiValue();
        $request->items                 = $this->getItemsFromTemplate($plan->getSubProducts());

        if ($plan->getTemplate()->getDueAt()->getType() == DueValueObject::TYPE_EXACT) {
            $request->billingDays = [ $plan->getTemplate()->getDueAt()->getValue() ];
        }

        $trialDays = $plan->getTemplate()->getTemplate()->getTrial();
        if ($trialDays > 0) {
            $request->trialPeriodDays = $trialDays;
        }

        return $request;

//        $json['installments']         = $request->installments;
//        $json['minimum_price']        = $request->minimumPrice;
//        $json['quantity']             = $request->quantity;
    }

    private function getCurrency()
    {
        $this->openCart->load->model('localisation/currency');
        $currencyModel = $this->openCart->model_localisation_currency;

        return $currencyModel->getCurrencyByCode($this->openCart->config->get('config_currency'));
    }

    protected function getPaymentMethodsFromTemplate($plan)
    {
        $methods = [];

        if ($plan->getTemplate()->isAcceptCreditCard()) {
            $methods[] = 'credit_card';
        }

        if ($plan->getTemplate()->isAcceptBoleto()) {
            $methods[] = 'boleto';
        }

        return $methods;
    }

    protected function getItemsFromTemplate($subProducts)
    {
        $items = [];
        foreach ($subProducts as $subProduct) {

            $product = $this->openCart->model_catalog_product->getProduct($subProduct->getProductId());

            $item['name'] = $product['name'];
            $item['quantity'] = $subProduct->getQuantity();
            $item['cycles'] = $subProduct->getCycles();
            $item['pricing_scheme'] = ['price' => (float) $product['price']];

            $items[] = $item;
        }

        return $items;
    }

    protected function hasShipping($subProducts)
    {
        $result = array_filter(
            $subProducts,
            function ($subProduct) {
                $product = $this->openCart->model_catalog_product->getProduct($subProduct->getProductId());

                return $product['shipping'] == "1" ? true : false;
            }
        );

        return count($result) > 0;

    }

}