<?php

namespace Mundipagg;

use MundiAPILib\Models\CreateCustomerRequest;
use MundiAPILib\Models\CreateAddressRequest;

class MultiBuyer
{
    private $request;
    private $types;

    public function __construct($request, array $types)
    {
        $this->request = $request;
        $this->types = $types;
    }

    public function createCustomers()
    {
        if (!$this->validateStatus()) {
            return [];
        }

        $data = $this->getMultiBuyerData($this->request->post); 
        $result = array_map(function($iten) {
                return $this->getMultiBuyerCustomer($iten);
            },
            $data
        );
        return $result;
    }

    public function validateStatus()
    {
        $result = array_filter($this->types, function($type) {
                $field = 'multi-buyer-status-' . $type;
                return isset($this->request->post[$field])
                    && $this->request->post[$field] == 'on';
            }
        );
        return !empty($result);
    }

    public function getMultiBuyerData($postData)
    {
        $result = [];

        foreach ($postData as $key => $value) {
            if (preg_match('/^multi-buyer/', $key)) {
                $result[$key] = $value;
            }
        }

        return $this->formatMultiBuyerData($result);
    }

    private function formatMultiBuyerData($data)
    {
        $result = [];

        foreach ($data as $key => $value) {
            $keys = explode('multi-buyer-', $key)[1];
            $keys = explode('-', $keys);
            $result[$keys[1]][$keys[0]] = $value;
        }

        return $result;
    }

    public function getMultiBuyerCustomer($multiBuyerData)
    {
        if (!isset($multiBuyerData['status']) || $multiBuyerData['status'] != 'on') {
            return null;
        }

        $addressRequest = new CreateAddressRequest();

        $addressRequest->street = $multiBuyerData['street'];
        $addressRequest->number = $multiBuyerData['number'];
        $addressRequest->neighborhood = $multiBuyerData['neighborhood'];
        $addressRequest->city = $multiBuyerData['city'];
        $addressRequest->state = $multiBuyerData['state'];
        $addressRequest->complement = $multiBuyerData['complement'];
        $addressRequest->zipCode = $multiBuyerData['zipcode'];
        $addressRequest->country = $multiBuyerData['country'];

        $customerRequest = new CreateCustomerRequest();

        $customerRequest->type = 'individual';
        $customerRequest->name = $multiBuyerData['name'];
        $customerRequest->email = $multiBuyerData['email'];
        $customerRequest->document = $multiBuyerData['document'];
        $customerRequest->address = $addressRequest;

        return $customerRequest;
    }
}
