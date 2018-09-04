<?php

require_once DIR_SYSTEM . 'library/mundipagg/vendor/autoload.php';

use MundiAPILib\MundiAPIClient;
use MundiAPILib\Models\CreateCustomerRequest;
use MundiAPILib\Models\CreateAddressRequest;
use MundiAPILib\Models\UpdateCustomerRequest;

use Mundipagg\Settings\CreditCard as CreditCardSettings;

/**
 * ControllerExtensionPaymentMundipaggEvents deal with module events
 *
 * The purpose of this class is to centralize methods related to important
 * events to the module
 *
 * @package Mundipagg
 *
 */
class ControllerExtensionPaymentMundipaggEvents extends Controller
{
    /**
     * Edit MundiPagg customer information
     *
     * @return void
     */
    public function onCustomerEdit()
    {
        $customer = $this->customer;
        $mPCustomerId = $this->getMPCustomerIdFromOC($customer->getId());

        $this->updateMPCustomer(
            array(
                'name' => $customer->getFirstName() . ' ' . $customer->getLastName(),
                'email' => $customer->getEmail(),
            ),
            $mPCustomerId,
            $this->getStoreCredentials()
        );
    }

    /**
     * Add a new address to Mundipagg customer information
     *
     * @return void
     */
    public function onAddressAdd()
    {
        // this event handler runs right after the new address being added
        // to the address database, so...
        $addressRequest = $this->getAddressRequest($this->db->getLastId());
        
        $customer = $this->customer;
        $mPCustomerId = $this->getMPCustomerIdFromOC($customer->getId());
        
        $this->addAddressToMPCustomer(
            $mPCustomerId,
            $addressRequest,
            $this->getStoreCredentials()
        );
    }

    /**
     * Get mundipagg_customer_id from mundipagg_customer table
     *
     * @param int $oCCustomerId Opencart customer id
     * @return Integer
     */
    private function getMPCustomerIdFromOC($oCCustomerId)
    {
        $this->load->model('extension/payment/mundipagg_events');
        return $this->model_extension_payment_mundipagg_events->getMPCustomerIdFromOC(
            $oCCustomerId
        );
    }

    /**
     * Update MundiPagg customer information
     *
     * @param array $customerInfo
     * @param int $mPCustomerId
     * @param array $storeCredentials
     * @return void
     */
    private function updateMPCustomer($customerInfo, $mPCustomerId, $storeCredentials)
    {
        $addressRequest = $this->getAddressRequest(
            $this->customer->getAddressId()
        );

        \Unirest\Request::verifyPeer(false);

        $client = new MundiAPIClient(
            $storeCredentials['secret_key'],
            $storeCredentials['password']
        );

        $customer = $client->getCustomers();
        $response = $customer->updateCustomer(
            $mPCustomerId,
            $this->getUpdateCustomerRequest($customerInfo, $addressRequest)
        );

        echo json_encode($response);
        exit;
    }

    /**
     * Create the new user in Mundipagg and store it into mundipagg_customer
     *
     * @return Integer Mundipagg customer id
     */
    private function createMPCustomer($customerInfo, $oCCustomerId, $storeCredentials)
    {
        \Unirest\Request::verifyPeer(false);

        $client = new MundiAPIClient(
            $storeCredentials['secret_key'],
            $storeCredentials['password']
        );

        $addressRequest = $this->getAddressRequest($this->customer->getAddressId());
        $customerRequest = $this->getCustomerRequest($customerInfo, $addressRequest);

        $customer = $client->getCustomers();
        $response = $customer->createCustomer($customerRequest);

        $this->saveNewCustomer($response->id, $this->customer->getId());

        return $response->id;
    }

    /**
     * Save new customer into mundipagg_customer table
     *
     * @return void
     */
    private function saveNewCustomer($oCCustomerId, $mPCustomerId)
    {
        $this->load->model('extension/payment/mundipagg_events');
        $this->model_extension_payment_mundipagg_events->saveNewCustomer(
            $oCCustomerId,
            $mPCustomerId
        );
    }

    /**
     * Everytime a client add a new address, this address is also create in Mundipagg
     *
     * @param int $mPCustomerId
     * @param string $addressRequest
     * @param array $storeCredentials
     * @return void
     */
    private function addAddressToMPCustomer($mPCustomerId, $addressRequest, $storeCredentials)
    {
        \Unirest\Request::verifyPeer(false);

         $client = new MundiAPIClient(
             $storeCredentials['secret_key'],
             $storeCredentials['password']
         );
       
        $customer = $client->getCustomers();

        $customer->createAddress($mPCustomerId, $addressRequest);
    }

    /**
     * Return store credentials according to test mode (enabled/disabled)
     *
     * @return Array Containing secret_key and password
     */
    private function getStoreCredentials()
    {
        if ($this->config->get('mundipagg_test_mode')) {
            return array(
                'secret_key' => $this->config->get('mundipagg_test_secret_key'),
                'password' => ''
            );
        }

        return array(
            'secret_key' => $this->config->get('mundipagg_prod_secret_key'),
            'password' => ''
        );
    }

    /**
     * Return an instance of AddressRequest
     *
     * @param int $addressId
     * @return object AddressRequest
     */
    private function getAddressRequest($addressId)
    {
        $this->load->model('account/address');
        $addressInfo = $this->model_account_address->getAddress($addressId);

        $addressRequest = new CreateAddressRequest(
            $addressInfo['address_1'],
            23,                             // FIXME: this MUST come from a custom field
            preg_replace('/[^0-9]+/', '', $addressInfo['postcode']),
            'bairro',                       // FIXME: this MUST come from a custom field
            $addressInfo['city'],
            $addressInfo['zone_code'],
            $addressInfo['iso_code_2'],
            '',
            array('module' => 'opencart')
        );

        return $addressRequest;
    }

    /**
     * Instantiate and return an CreateaddressRequest model
     *
     * @param Array Customer Information
     * @param CreateAddressRequest Address request
     * @return object
     */
    private function getCustomerRequest($customerInfo, $addressRequest)
    {
        $customerRequest = new CreateCustomerRequest(
            $customerInfo['name'],
            $customerInfo['email'],
            '',
            null,
            '',
            $addressRequest,
            array('module' => 'opencart')
        );

        return $customerRequest;
    }

    /**
     * Return information to send to MundiPagg Api
     *
     * @param array $customerInfo
     * @param object $addressRequest
     * @return UpdateCustomerRequest
     */
    private function getUpdateCustomerRequest($customerInfo, $addressRequest)
    {
        return new UpdateCustomerRequest(
            $customerInfo['name'],
            '',
            $customerInfo['email'],
            null,
            'individual',
            $addressRequest,
            array('module' => 'opencart')
        );
    }

    public function showSavedCreditcards(string $route, $data = array(), $template = null)
    {
        $creditCardSettings = new CreditCardSettings($this);
        if (!$creditCardSettings->isSavedCreditcardEnabled()) {
            return;
        }
        $template = new Template($this->registry->get('config')->get('template_engine'));
        $this->load->language('extension/payment/mundipagg');

        $templateData['text'] = $this->language->get('saved_creditcard');
        $templateData['isAccountIndex'] = $route === 'account/account';
        $templateData['my_creditcards_url'] = $this->url->link('extension/payment/mundipagg_saved_creditcards', '', true);

        $view  = $this->load->view('extension/payment/mundipagg/credit_card/saved_credit_card_account_content', $templateData);
        $data['content_bottom'] .= $view;

        if (isset($this->session->data['error_warning'])) {
            $data['error_warning'] = $this->session->data['error_warning'];
            unset($this->session->data['error_warning']);
        }
        foreach ($data as $key => $value) {
            $template->set($key, $value);
        }

        return $template
            ->render(
                $this->
                registry->
                get('config')->
                get('template_directory') .
                $route,
                $this->
                registry->
                get('config')->
                get('template_cache')
            );
    }

    public function prepareCheckoutOrderInfo(string $route, $data = array(), $template = null)
    {
        $this->session->data['boleto_links'] = [];
        if (isset($this->session->data['order_id'])) {
            $orderId = $this->session->data['order_id'];
            $this->load->model('extension/payment/mundipagg_order_boleto_info');
            $boletoLinks = $this->model_extension_payment_mundipagg_order_boleto_info->getBoletoLinks($orderId);
            $this->session->data['boleto_links'] = $boletoLinks;
        }
    }

    public function showCheckoutOrderInfo(string $route, $data = array(), $template = null)
    {
        $template = new Template($this->registry->get('config')->get('template_engine'));
        $this->load->language('extension/payment/mundipagg');

        $templateData = [];
        $boletoLinks = [];
        if (isset($this->session->data['boleto_links'])) {
            $boletoLinks = $this->session->data['boleto_links'];
        }
        if (count($boletoLinks) > 0) {
            $boletoLang = $this->language->get('boleto');
            $boletoLink = $boletoLinks[0]['link'];
            $templateData['boleto_link_message'] = sprintf($boletoLang['click_to_follow'],$boletoLink);
            $templateData['boleto_link'] = $boletoLink;
        }
        unset($this->session->data['boleto_links']);
        $view  = $this->load->view('extension/payment/mundipagg/success/order_info', $templateData);
        $data['content_bottom'] .= $view;

        foreach ($data as $key => $value) {
            $template->set($key, $value);
        }

        return $template
            ->render(
                $this->
                registry->
                get('config')->
                get('template_directory') .
                $route,
                $this->
                registry->
                get('config')->
                get('template_cache')
            );
    }

    public function showAccountOrderInfo(string $route, $data = array(), $template = null)
    {
        $template = new Template($this->registry->get('config')->get('template_engine'));

        $templateData = $this->getShowAccountOrderInfoTemplateData();
        $view  = $this->load->view('extension/payment/mundipagg/account/order_info', $templateData);

        $data['text_history'] = '</h3>' . $view . '<h3>'. $data['text_history'];

        foreach ($data as $key => $value) {
            $template->set($key, $value);
        }

        $config = $this->registry->get('config');
        return $template->render(
            $config->get('template_directory') . $route,
            $config->get('template_cache')
        );
    }

    private function getShowAccountOrderInfoTemplateData()
    {
        $order_id ='0';
        if (isset($this->request->get['order_id'])) {
            $order_id = intval($this->request->get['order_id']);
        }

        $order = new Mundipagg\Model\Order($this);
        $charges = $order->getCharge($order_id);

        setlocale(LC_MONETARY, 'pt_BR.UTF-8');
        array_walk($charges->rows,function(&$row) {
            //formatting amount
            $row['amount'] = money_format('%n', $row['amount'] / 100);
            $row['paid_amount'] = money_format('%n', $row['paid_amount'] / 100);
        });

        $this->load->language('extension/payment/mundipagg');
        $accountInfoLang = $this->language->get('account_info');

        $templateData = array_merge($accountInfoLang,['charges' => $charges->rows]);

        return $templateData;
    }
}
