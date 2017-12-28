<?php
/**
 * ControllerExtensionPaymentMundipagg is the payment module controller
 *
 * @package Mundipagg
 */
require_once DIR_SYSTEM.'library/mundipagg/vendor/autoload.php';

use Mundipagg\Order;
use Mundipagg\Log;
use Mundipagg\LogMessages;
use Mundipagg\Controller\Boleto;
use Mundipagg\Controller\CreditCard;
use Mundipagg\Controller\Settings;
use Mundipagg\Controller\SavedCreditCard;

class ControllerExtensionPaymentMundipagg extends Controller
{
    /**
     * @var array $data
     */
    private $data;

    /**
     * @var array $setting
     */
    private $setting;

    /**
     * @var object $creditCardModel
     */
    private $creditCardModel;

    /**
     * @var object $mundipaggModel
     */
    private $mundipaggModel;

    /**
     * @var object $mundipaggOrderUpdateModel
     */
    private $mundipaggOrderUpdateModel;

    /**
     * It loads opencart/mundipagg models
     *
     * From time to time it is necessary to load a ton of models. This method just
     * group this statements together in order to make a cleaner code
     *
     * @return void
     */
    private function load()
    {
        $this->load->model('checkout/order');
        $this->load->model('setting/setting');
        $this->load->model('extension/payment/mundipagg_customer');
        $this->load->model('extension/payment/mundipagg_credit_card');
        $this->load->model('extension/payment/mundipagg_orderdata_update');
        $this->load->language('extension/payment/mundipagg');

        $this->data['misc'] = $this->language->get('misc');
        $this->setting = $this->model_setting_setting;
        $this->mundipaggModel = $this->model_mundipagg;
        $this->creditCardModel = $this->model_extension_payment_mundipagg_credit_card;
        $this->mundipaggOrderUpdateModel = $this->model_extension_payment_mundipagg_orderdata_update;
    }

    /**
     * This method sets the customized css file path
     *
     * The user can provide a custom css file to be used instead of modules default.
     * It must be inside theme stylesheet directory and be named mundipagg_theme.css.
     * (not implemented yet)
     *
     * @return Void
     */
    private function getDirectories()
    {
        $this->load();

        $theme = $this->config->get('config_theme');
        $themeDirectory = $this->config->get('theme_' . $theme . '_directory');
        $this->data['themeDirectory'] = 'catalog/view/theme/' . $themeDirectory;

        $customizedFile = $this->data['themeDirectory'] . '/stylesheet/mundipagg/mundipagg_customized.css';

        if (file_exists($customizedFile)) {
            $this->data['customizedFile'] = $customizedFile;
        }
    }

    /**
     * This method is called when user has to choose between the installed payment methods.
     *
     * @return mixed
     */
    public function index()
    {
        $boleto = new Boleto($this);
        $creditCard = new CreditCard($this);
        $settings = new Settings($this);
        $savedCreditcard = new SavedCreditCard($this);

        $this->data['publicKey'] = $settings->getPublicKey();

        $this->getDirectories();

        if ($creditCard->isEnabled()) {
            $this->data = array_merge($this->data, $creditCard->getCreditCardPageInfo());
        }

        if ($boleto->isEnabled()) {
            $this->data = array_merge($this->data, $boleto->getBoletoPageInfo());
        }
        $this->data['generate_boleto_url'] =
            $this->url->link('extension/payment/mundipagg/generateBoleto');
        $this->data['checkout_success_url'] =
            $this->url->link('checkout/success');

        //@todo get from config
        $isSavedCreditcardEnabled = true;

        if ($isSavedCreditcardEnabled) {
            $this->data['savedCreditcards'] =
                $savedCreditcard
                    ->getSavedCreditcardList($this->customer->getId());
        }

        $this->loadPaymentTemplates();

        return $this->load->view('extension/payment/mundipagg', $this->data);
    }

    private function loadPaymentTemplates()
    {
        $this->data['savedCreditcardTemplate'] =
            $this->load->view('extension/payment/mundipagg_saved_credit_card', $this->data);
        $this->data['newCreditcardTemplate'] =
            $this->load->view('extension/payment/mundipagg_new_credit_card', $this->data);
        $this->data['creditcardTemplate'] =
            $this->load->view('extension/payment/mundipagg_credit_card', $this->data);
        $this->data['boletoTemplate'] =
            $this->load->view('extension/payment/mundipagg_boleto', $this->data);
    }


    /**
     * Generate boletos page
     *
     * @return void
     */
    public function generateBoleto()
    {
        if (!$this->customer->isLogged()) {
            $this->response->redirect($this->url->link('checkout/failure', '', true));
        }

        $this->load();

        if (isset($this->session->data['order_id'])) {
            $orderData = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            if ($this->validate($orderData)) {
                if ($orderData['payment_code'] === 'mundipagg') {
                    $response = $this->getOrder()->create($orderData, $this->cart, 'boleto');

                    if (isset($response->charges[0]->lastTransaction->success)) {
                        $this->success($response);
                    } else{
                        Log::create()
                            ->error(LogMessages::UNKNOWN_API_RESPONSE, __METHOD__)
                            ->withOrderId($this->session->data['order_id']);
                        $this->response->redirect($this->url->link('checkout/failure', '', true));
                    }
                }
            }
        }

        Log::create()
            ->error(LogMessages::ORDER_ID_NOT_FOUND, __METHOD__);
        $this->response->redirect($this->url->link('checkout/cart'));
    }

    /**
     * Save payment and redirect user to boleto url
     *
     * @param string $response Api's response
     * @return void
     */
    private function success($response)
    {
        $orderComment =
                $this->language->get('boleto')['pending_order_status'] . " <br>" .
                "<a href='" .
                $response->charges[0]->lastTransaction->url.
                "' target='_blank'>" .
                $this->language->get('boleto')['click_to_generate'] .
                "</a>";
        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $orderComment, true);
        $this->response->redirect($response->charges[0]->lastTransaction->url);
    }

    /**
     * Validate order data
     *
     * @return boolean
     */
    private function validate($orderData)
    {
        if (isset($orderData['order_id']) &&
            $orderData['order_id'] !== null) {
            $pattern = array(
               "customer_id",
               "email",
               "payment_firstname",
               "payment_lastname",
               "payment_address_1",
               "payment_address_2",
               "payment_postcode",
               "payment_city",
               "payment_zone_id",
               "payment_zone",
               "payment_zone_code",
               "payment_country_id",
               "payment_country",
               "payment_custom_field",
               "payment_method",
               "payment_code",
               "currency_id",
               "currency_code",
               "ip"
            );

            // kind of trick, but it counts how much indexes are in the difference between the intersection
            // between pattern and indexes in orderData
            if (count(array_diff($pattern, array_intersect(array_keys($orderData), $pattern))) > 0) {
                Log::create()->error(LogMessages::MALFORMED_REQUEST, __METHOD__);

                return false;
            }

            return true;
        }

        Log::create()
            ->error(LogMessages::ORDER_ID_NOT_FOUND, __METHOD__)
            ->withOrderId($orderData['order_id']);

        return false;
    }

    /**
     * Group credit card request validations in one method
     *
     * @return bool
     */
    private function isValidateCreditCardRequest()
    {
        if (!isset($this->session->data['order_id'])) {
            return false;
        }
        
        if ($this->session->data['payment_method']['code'] !== 'mundipagg') {
            return false;
        }
        
        if (!$this->customer->isLogged()) {
            return false;
        }
        
        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            return false;
        }
        
        return true;
    }

    /**
     * Create credit card order using mundipagg SDK
     *
     * @param $interest
     * @param $installments
     * @param $orderData
     * @param $cardToken
     * @return mixed
     */
    private function createCreditCardOrder($interest, $installments, $orderData, $cardToken, $cardId)
    {
        $this->load();
        
        $order = $this->getOrder();
        
        $order->setInterest($interest);
        $order->setInstallments($installments);

        $orderData['amountWithInterest'] =
            $this->setInterestToOrder($orderData, $interest);
        
        return $order->create($orderData, $this->cart, 'creditCard', $cardToken, $cardId);
    }
    
    private function getOrder()
    {
        if (!is_object($this->Order)) {
            $this->Order = new Order($this);
            $this->Order->setCustomerModel(
                $this->model_extension_payment_mundipagg_customer
            );
        }
        return $this->Order;
    }

    /**
     * This method stores the received order id from mundipagg with the opencart order id
     *
     * @param string $mundiOrderId
     * @param string $openCartOrderId
     * @return void
     */
    public function saveMPOrderId($mundiOrderId, $openCartOrderId)
    {
        $this->load->model('extension/payment/mundipagg_credit_card');
        $this->model_extension_payment_mundipagg_credit_card->saveMundiOrder(
            $mundiOrderId,
            $openCartOrderId
        );
    }

    /**
     * This method process the credit card transaction
     *
     * @return void
     */
    public function processCreditCard()
    {
        $this->load();
        
        if (!$this->isValidateCreditCardRequest()) {
            Log::create()
                ->error(LogMessages::INVALID_CREDIT_CARD_REQUEST, __METHOD__)
                ->withOrderId($this->session->data['order_id']);

            $this->response->redirect($this->url->link('checkout/failure'));
        }

        $orderData = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if (isset($this->request->post['mundipaggSavedCreditCard'])) {
            $cardId = $this->request->post['mundipaggSavedCreditCard'];
            $cardToken = null;
        } else{
            $cardToken = $this->request->post['munditoken'];
            //@todo get from frontend
            $orderData['saveCreditcard'] = false;
            $cardId = null;
        }

        $paymentDetails = explode('|', $this->request->post['payment-details']);

        try {
            $response = $this->createCreditCardOrder(
                (double)$paymentDetails[1],
                $paymentDetails[0],
                $orderData,
                $cardToken,
                $cardId
            );

        } catch (Exception $e) {
            Log::create()
                ->error(LogMessages::UNABLE_TO_CREATE_ORDER, __METHOD__)
                ->withOrderId($this->session->data['order_id'])
                ->withException($e);

            $this->response->redirect($this->url->link('checkout/failure'));
        }

        $orderStatus = $this->getOrder()->translateStatusFromMP($response);
        if (!$orderStatus) {
            Log::create()
            ->error(LogMessages::UNKNOWN_ORDER_STATUS, __METHOD__)
            ->withResponseStatus($response->status)
            ->withOrderId($this->session->data['order_id']);

            $this->response->redirect($this->url->link('checkout/failure'));
        }
        $this->getOrder()->updateOrderStatus($orderStatus);
        $this->saveMPOrderId($response->id, $this->session->data['order_id']);
        $this->response->redirect($this->url->link('checkout/success', '', true));
    }

    /**
     * Update order data in database
     * @param array $orderData
     * @param float $interest
     * @return mixed (bool, float)
     */
    private function setInterestToOrder($orderData, $interest)
    {
        if ($interest > 0) {
            $amountWithInterest = $this->setInterestToAmount($orderData['total'], $interest);
            $interestAmount = $amountWithInterest - $orderData['total'];

            $this->mundipaggOrderUpdateModel->
                updateOrderAmountInOrder(
                    $orderData['order_id'],
                    $amountWithInterest
                );

            $this->mundipaggOrderUpdateModel->
            updateOrderAmountInOrderTotals(
                $orderData['order_id'],
                $amountWithInterest
            );

            $this->mundipaggOrderUpdateModel->
            insertInterestInOrderTotals(
                $orderData['order_id'],
                $interestAmount
            );

            return $amountWithInterest;
        }
        return false;
    }

    private function setInterestToAmount($amount, $interest)
    {
        return round($amount + ($amount * ($interest * 0.01)), 2);
    }
}