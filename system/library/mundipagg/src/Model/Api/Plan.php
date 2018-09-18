<?php

namespace Mundipagg\Model\Api;

use Mundipagg\Aggregates\RecurrencyProduct\RecurrencyProductRoot;
use Mundipagg\Aggregates\Template\DueValueObject;
use Mundipagg\Settings\General as GeneralSettings;
use MundiAPILib\MundiAPIClient;
use MundiAPILib\Models\CreatePlanRequest;
use MundiAPILib\Models\UpdatePlanRequest;

class Plan
{
    public $openCart;
    protected $planApi;

    public function __construct($opencart)
    {
        $this->openCart = $opencart;
        $this->openCart->load->model('catalog/product');
        $generalSettings = new GeneralSettings($this->openCart);
        $this->planApi = new MundiAPIClient($generalSettings->getSecretKey(), $generalSettings->getPassword());
    }

    /**
     * @param RecurrencyProductRoot $plan
     * @return mixed
     */
    public function save(RecurrencyProductRoot $plan)
    {
        if ($plan->getMundipaggPlanId() === null) {
            return $this->createPlan($plan);
        }
        return $this->updatePlan($plan);
    }

    protected function createPlan(RecurrencyProductRoot $plan)
    {
        $request = $this->getCreatePlanRequest($plan);
        return $this->planApi->getPlans()->createPlan($request->jsonSerialize());
    }

    protected function updatePlan(RecurrencyProductRoot $plan)
    {
        $request = $this->getUpdatePlanRequest($plan);
        return $this->planApi->getPlans()->updatePlan($plan->getMundipaggPlanId(), $request->jsonSerialize());
    }

    public function deletePlan($plan)
    {
        return $this->planApi->getPlans()->deletePlan($plan->getMundipaggPlanId());
    }

    protected function getCreatePlanRequest($plan)
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

        $installments = $plan->getTemplate()->getTemplate()->getInstallments();
        foreach ($installments as $installment) {
            $request->installments[] = $installment->getValue();
        }

        if ($plan->getTemplate()->getDueAt()->getType() == DueValueObject::TYPE_EXACT) {
            $request->billingDays = [ $plan->getTemplate()->getDueAt()->getValue() ];
        }

        $trialDays = $plan->getTemplate()->getTemplate()->getTrial();
        if ($trialDays > 0) {
            $request->trialPeriodDays = $trialDays;
        }

        return $request;
    }


    protected function getUpdatePlanRequest($plan)
    {
        $baseRequest = $this->getCreatePlanRequest($plan)->jsonSerialize();
        $request = new UpdatePlanRequest();

        array_walk($baseRequest, function ($item, $key) use ($request) {
            $attr = lcfirst(ucwords(str_replace("_", " ", $key)));
            $attribute = str_replace(" ", "", $attr);
            $request->{$attribute} = $item;
        });

        $request->status = $plan->getMundipaggPlanStatus();

        return $request;
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