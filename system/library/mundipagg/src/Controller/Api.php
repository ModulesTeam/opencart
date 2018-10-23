<?php

namespace Mundipagg\Controller;

require_once DIR_SYSTEM . 'library/mundipagg/vendor/autoload.php';

use Mundipagg\Aggregates\RecurrencyProduct\RecurrencyProductRoot;
use Mundipagg\Aggregates\Template\RepetitionValueObject;
use Mundipagg\Enum\WebHookEnum;
use Mundipagg\Helper\OpencartOrderInfo;
use Mundipagg\Log;
use Mundipagg\LogMessages;
use Mundipagg\Model\WebHook as WebHookModel;
use Mundipagg\Model\Installments;
use Mundipagg\Enum\OrderstatusEnum;
use Mundipagg\Repositories\Decorators\OpencartPlatformDatabaseDecorator;
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


        $orderInfoHelper = new OpencartOrderInfo($this->openCart);
        /** @var RecurrencyProductRoot $recurrenceProducty **/
        $recurrenceProduct = $orderInfoHelper->getRecurrenceProduct($this->openCart->cart);

        if ($recurrenceProduct !== null) {
            $allowedInstallments = $recurrenceProduct
                ->getTemplate()->getTemplate()
                ->getInstallments();

            array_walk($allowedInstallments, function(&$installment) {
                $installment = $installment->getValue();
            });

            $repetition = json_decode(base64_decode($arguments['repetitions']));
            if (!empty($repetition)) {
                $allowedInstallments = $this->filterInstallmentsFromRepetition(
                    $repetition,
                    $allowedInstallments
                );
            }

            $installments = array_filter(
                $installments,
                function($installment) use (
                    $allowedInstallments
                ) {
                    return in_array($installment['times'], $allowedInstallments);
                }
            );
        }

        return [
            'status_code' => 200,
            'payload' => $installments
        ];
    }

    protected function filterInstallmentsFromRepetition($repetition, $allowedInstallments)
    {
        $selectedRepetition = $this->getInstallmentsFromRepetition($repetition);
        $installments = array_filter(
            $allowedInstallments,
            function($installment) use (
                $selectedRepetition
            ) {
                return in_array($installment, $selectedRepetition);
            }
        );

        return $installments;
    }

    protected function getInstallmentsFromRepetition($repetition)
    {
        if ($repetition->intervalType == RepetitionValueObject::INTERVAL_TYPE_WEEKLY) {
            return [1];
        }

        if ($repetition->intervalType == RepetitionValueObject::INTERVAL_TYPE_MONTHLY) {
            return range(1, $repetition->frequency);
        }

        if ($repetition->intervalType == RepetitionValueObject::INTERVAL_TYPE_YEARLY) {
            return range(1, 12);
        }

        return null;
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
