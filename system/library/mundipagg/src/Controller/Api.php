<?php

namespace Mundipagg\Controller;

require_once DIR_SYSTEM . 'library/mundipagg/vendor/autoload.php';

use Mundipagg\Aggregates\RecurrencyProduct\RecurrencyProductRoot;
use Mundipagg\Enum\WebHookEnum;
use Mundipagg\Log;
use Mundipagg\LogMessages;
use Mundipagg\Model\WebHook as WebHookModel;
use Mundipagg\Model\Installments;
use Mundipagg\Enum\OrderstatusEnum;
use Mundipagg\Repositories\Bridges\OpencartDatabaseBridge;
use Mundipagg\Repositories\RecurrencyProductRepository;


class Api
{
    private $data;
    private $verb;
    private $model;
    private $openCart;

    public function __construct($data, $verb, $openCart)
    {
        $this->data = $data;
        $this->verb = $verb;
        $this->openCart = $openCart;

        $this->model = new Installments($openCart->db);
    }

    /**
     * I've made it this way so we can have all the combinations of
     * $verb with $endpoint, things like getInstallments and postInstallments
     * can then exist.
     *
     * @param $endpoint
     * @param $arguments
     * @return array
     */
    public function __call($endpoint, $arguments)
    {
        $method = $this->verb . ucfirst($endpoint);

        if (method_exists($this, $method)) {
            return $this->{$method}($this->data[$this->verb]);
        }

        return [
            'status_code' => 404,
            'payload' => ['error' => 'endpoint not found']
        ];
    }


    private function getRecurrenceProduct()
    {
        //filter products
        $items = $this->openCart->cart->getProducts();

        $plans = [];
        $recurrenceProductRepo = new RecurrencyProductRepository(
            new OpencartDatabaseBridge()
        );

        foreach ($items as $item) {
            $product = $recurrenceProductRepo->getByProductId($item['product_id']);
            if ($product !== null) {
                $plans[] = $product;
            }
        }

        if (count($plans) == 1 && count($items) == 1) {
            return $plans[0];
        }

        return null;
    }

    private function getInstallments($arguments)
    {
        $brand = $arguments['brand'];
        $total = $arguments['total'];

        if (!isset($brand, $total)) {
            return $this->notFoundResponse('missing parameters');
        }

        $installments = $this->model->getInstallmentsFor($brand, $total);

        if (!$installments) {
            return $this->notFoundResponse('wrong request');
        }

        /** @var RecurrencyProductRoot $recurrenceProducty **/
        $recurrenceProduct = $this->getRecurrenceProduct();

        if ($recurrenceProduct !== null) {
            $allowedInstallments = $recurrenceProduct
                ->getTemplate()->getTemplate()
                ->getInstallments();
            array_walk($allowedInstallments, function(&$installment) {
                $installment = $installment->getValue();
            });
        }

        $installments = array_filter($installments, function($installment)
            use ($allowedInstallments){
           return in_array($installment['times'], $allowedInstallments);
        });

        return [
            'status_code' => 200,
            'payload' => $installments
        ];
    }

    private function getCountries()
    {
        $this->openCart->load->model('localisation/country');
        $modelCountry = $this->openCart->model_localisation_country;

        return [
            'status_code' => 200,
            'payload' => $modelCountry->getCountries()
        ];
    }

    private function getStatesByCountry($arguments)
    {
        $formData = [];

        $countryId = 0;
        if (isset($arguments['country_id'])) {
            $countryId = $arguments['country_id'];
        }

        $this->openCart->load->model('localisation/zone');
        $zone = $this->openCart->model_localisation_zone;

        return [
            'status_code' => 200,
            'payload' => $zone->getZonesByCountryId($countryId)
        ];

    }


    private function getStates($arguments)
    {
        $formData = [];

        $this->openCart->load->model('localisation/zone');
        $zone = $this->openCart->model_localisation_zone;
        $formData['zones'] = $zone->getZonesByCountryId(30);

        return $formData;
    }

    private function notFoundResponse($message)
    {
        return [
            'status_code' => 404,
            'payload' => ['error' => $message]
        ];
    }
}
