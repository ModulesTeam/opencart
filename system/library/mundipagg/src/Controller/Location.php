<?php

namespace Mundipagg\Controller;

class Location
{
    private $openCart;

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
    }

    public function index() {
        $lang = $this->openCart->load->language('extension/payment/mundipagg');
        header('Content-Type:application/json');
        http_response_code(200);
        echo json_encode($lang['mundipagg']);
        die;
    }
}