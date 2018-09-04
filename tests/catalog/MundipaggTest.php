<?php

namespace Tests;

class MundipaggCatalogTest extends OpenCartTest
{
    public function setUp()
    {
        parent::setUp();
        if (!$loaded) {
            static $loaded = true;
            $this->loadModel('account/customer');
            $customer_id = $this->model_account_customer->addCustomer([
                'customer_group_id' => 1,
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'email' => 'customer@mundipagg.com',
                'telephone' => 'telephone',
                'password' => 'password',
                'custom_field' => array(),
            ]);
            $this->model_account_customer->editPassword('customer@mundipagg.com', 'password');
            $this->login('customer@mundipagg.com', 'password');
        }
    }

    public function testAddCart()
    {
        $response = $this->dispatchAction('checkout/cart/add', 'POST', [
            'quantity'   => 1,
            'product_id' => 40
        ]);
        $this->assertRegExp('/Success: You have added/', $response->getOutput());
    }

    public function testBillingDetails()
    {
        $response = $this->dispatchAction('checkout/payment_address/save', 'POST', [
            'firstname' => 'José',
            'lastname' => 'Das Couves',
            'company' => '',
            'address_2' => 'Rua dos Bobos',
            'address_1' => '171',
            'city' => 'Neverland',
            'postcode' => '171171171',
            'country_id' => '30',
            'zone_id' => '446',
            'custom_field' => array(
                'address' => array(
                    2 => 'fundos',
                    1 => 171
                )
            )
        ]);
        $this->assertEquals('[]', $response->getOutput());
    }
    
    public function testDeliveryMethod()
    {
        $response = $this->dispatchAction('checkout/shipping_method/save', 'POST', [
            'shipping_method' => 'flat.flat',
            'comment' => ''
        ]);
        $this->assertRegExp('/route=checkout/', $response->getOutput());
    }
    
    public function testPaymentMethod()
    {
        $response = $this->dispatchAction('checkout/payment_method/save', 'POST', [
            'payment_method' => 'mundipagg',
            'comment' => '',
            'agree' => 1
        ]);
        $this->assertRegExp('/route=checkout/', $response->getOutput());
    }

    public function testConfirmOrder()
    {
        $response = $this->dispatchAction('extension/payment/mundipagg/processCreditCard', 'POST', [
            'payment-details' => '1|0',
            'munditoken' => $this->getTokenId()
        ]);
        $this->assertRegExp('/route=checkout/', $response->getOutput());
    }

    private function getTokenId()
    {
        $this->markTestSkipped('must be revisited.');

        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://api.mundipagg.com/core/v1/tokens?appId=' . getenv('TEST_PUBLIC_KEY'), [
            'body' => [
                'type' => 'credit_card',
                'number' => '4556809418730432',
                'exp_month' => '1',
                'exp_year' => date('Y') + 1,
                'holder_name' => 'Jose das Couves',
                'cvv' => '123'
            ]
        ]);
        return json_decode($response->getBody())->id;
    }

    public function _tearDown()
    {
        $response = $this->dispatchAction(
            'customer/customer/delete',
            'POST',
            ['selected'=>[1]]
            );
    }
}
