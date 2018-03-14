<?php
/**
 * ControllerExtensionPaymentMundipagg is the payment module controller
 *
 * @package Mundipagg
 */
require_once DIR_SYSTEM.'library/mundipagg/vendor/autoload.php';

use Mundipagg\Controller\Api;
use Mundipagg\Controller\SavedCreditCard;
use Mundipagg\Controller\TwoCreditCards;
use Mundipagg\Log;
use Mundipagg\LogMessages;
use Mundipagg\Order;
use Mundipagg\Settings\Boleto as BoletoSettings;
use Mundipagg\Settings\CreditCard as CreditCardSettings;
use Mundipagg\Settings\BoletoCreditCard as BoletoCreditCardSettings;
use Mundipagg\Settings\General as GeneralSettings;

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

    private $onOff = [
        'off' => false,
        'on' => true
    ];

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
        $this->load->model('extension/payment/mundipagg');
        $this->load->model('extension/payment/mundipagg_credit_card');
        $this->load->model('extension/payment/mundipagg_orderdata_update');
        $this->load->language('extension/payment/mundipagg');

        $this->data['misc'] = $this->language->get('misc');
        $this->setting = $this->model_setting_setting;
        $this->mundipaggModel = $this->model_extension_payment_mundipagg;
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
        $boletoSettings = new BoletoSettings($this);
        $boletoCreditCardSettings = new BoletoCreditCardSettings($this);
        $generalSettings = new GeneralSettings($this);
        $creditCardSettings = new CreditCardSettings($this);
        $savedCreditcard = new SavedCreditCard($this);

        $this->data['publicKey'] = $generalSettings->getPublicKey();

        $this->getDirectories();
        $this->loadUrls();

        if ($creditCardSettings->isEnabled()) {
            $this->data = array_merge($this->data, $creditCardSettings->getCreditCardPageInfo());
        }

        // check if payment with two credit cards is enabled
        if ($creditCardSettings->isTwoCreditCardsEnabled()) {
            $this->data = array_merge($this->data, $creditCardSettings->getTwoCreditCardsPageInfo());
            $this->data['twoCreditCardsPaymentTitle'] = $creditCardSettings->getTwoCreditCardsPaymentTitle();
        }

        if ($boletoSettings->isEnabled()) {
            $this->data = array_merge($this->data, $boletoSettings->getBoletoPageInfo());
        }

        if ($boletoCreditCardSettings->isEnabled()) {
            $this->data = array_merge($this->data, $boletoCreditCardSettings->getBoletoCreditCardPageInfo());
        }

        $isSavedCreditCardEnabled = $creditCardSettings->isSavedCreditcardEnabled();
        $this->data['isSavedCreditcardEnabled'] = $isSavedCreditCardEnabled;

        if ($isSavedCreditCardEnabled) {
            $this->data['savedCreditcards'] =
                $savedCreditcard->getSavedCreditcardList($this->customer->getId());
        }

        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $this->data['amount'] = $order['total'];

        $this->loadPaymentTemplates();

        return $this->load->view('extension/payment/mundipagg/mundipagg', $this->data);
    }

    private function loadUrls()
    {
        $generateUrl = 'extension/payment/mundipagg/generateBoleto';

        $this->data['generate_boleto_url'] = $this->url->link($generateUrl);
        $this->data['checkout_success_url'] = $this->url->link('checkout/success');
        $this->data['payment_failure_url'] = $this->url->link('checkout/failure');
    }

    private function loadPaymentTemplates()
    {
        $path = 'default/template/extension/payment/mundipagg/';

        $this->data['creditCardTemplate'] = $path . 'credit_card/credit_card.twig';
        $this->data['baseCreditCardtemplate'] = $path . 'credit_card/base.twig';
        $this->data['baseTwoCreditCardstemplate'] = $path . 'credit_card/base_two.twig';
        $this->data['twoCreditCardsTemplate'] = $path . 'credit_card/two.twig';
        $this->data['savedCreditcardTemplate'] = $path . 'credit_card/saved.twig';
        $this->data['newCreditcardTemplate'] = $path . 'credit_card/new.twig';
        $this->data['savedSelectCreditcardTemplate'] = $path . 'credit_card/saved_select.twig';
        $this->data['brandsTemplate'] = $path . 'credit_card/brands.twig';
        $this->data['baseCreditCardtemplate'] = $path . 'credit_card/base.twig';
        $this->data['baseBoletoTemplate'] = $path . 'boleto/base.twig';
        $this->data['infoBoletoTemplate'] = $path . 'boleto/info.twig';
        $this->data['submitBoletoTemplate'] = $path . 'boleto/submit.twig';
        $this->data['orderAmountInput'] = $path . 'credit_card/order_amount_input.twig';
        $this->data['submitTemplate'] = $path . 'credit_card/submit.twig';
        $this->data['boletoCreditCardTemplate'] = $path . 'boleto_credit_card.twig';
    }

    /**
     * Save payment info and print boleto url
     * @param string $response Api's response
     * @return void
     */
    private function saveBoletoInfoInOrderHistory($response)
    {
        $orderComment =
                $this->language->get('boleto')['pending_order_status'] . " <br>" .
                "<a href='" .
                $response->charges[0]->lastTransaction->url.
                "' target='_blank'>" .
                $this->language->get('boleto')['click_to_generate'] .
                "</a>";

        $model = $this->model_extension_payment_mundipagg_order_processing;

        $model->addOrderHistory(
            $this->session->data['order_id'],
            1,
            $orderComment,
            1
        );
    }

    private function printBoletoUrl($response)
    {
        echo $this->getBoletoUrl($response);
    }

    private function getBoletoUrl($response)
    {
        return $response->charges[0]->lastTransaction->url;
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
     * @param $cardId
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

        $paymentMethod = 'creditCard';

        if (isset($orderData['boletoCreditCard'])) {
           $paymentMethod = 'boletoCreditCard';
        }
        return $order->create($orderData, $this->cart, $paymentMethod, $cardToken, $cardId);
    }
    
    private function getOrder()
    {
        if (!is_object($this->order)) {
            $this->order = new Order($this);
            $this->order->setCustomerModel($this->model_extension_payment_mundipagg_customer);
        }

        return $this->order;
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
     * Generate boleto
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
                    $cart = $this->cart;
                    $response = $this->getOrder()->create($orderData, $cart, 'boleto');

                    if (isset($response->charges[0]->lastTransaction->success)) {

                        $this->load->model('extension/payment/mundipagg_order_processing');
                        $this->load->model('extension/payment/mundipagg_boleto_link');
                        $model = $this->model_extension_payment_mundipagg_order_processing;
                        $this->saveBoletoInfoInOrderHistory($response);
                        $this->printBoletoUrl($response);
                        $this->model_extension_payment_mundipagg_boleto_link->saveBoletoLink(
                            $this->session->data['order_id'],
                            $this->getBoletoUrl($response)
                        );
                        $model->setOrderStatus($orderData['order_id'], 1);
                        return;

                    } else{
                        Log::create()
                            ->error(LogMessages::API_REQUEST_FAIL, __METHOD__)
                            ->withOrderId($this->session->data['order_id'])
                            ->withRequest(json_encode($response, JSON_PRETTY_PRINT));

                        Log::create()
                            ->error(LogMessages::UNKNOWN_API_RESPONSE, __METHOD__)
                            ->withOrderId($this->session->data['order_id']);
                        $this->response->redirect($this->url->link('checkout/failure', '', true));
                    }
                }
            }
        }

        Log::create()->error(LogMessages::ORDER_ID_NOT_FOUND, __METHOD__);
        $this->response->redirect($this->url->link('checkout/cart'));
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

        $card = $this->fillCreditCardData(0);
        $orderData = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $orderData['saveCreditCard'] = $card['saveThisCard'];

        try {
            $response = $this->createCreditCardOrder(
                (double)$card['paymentDetails'][2],
                $card['paymentDetails'][0],
                $orderData,
                $card['cardToken'],
                [$card['cardId']]
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

        $newOrder = $this->getOrder();
        $newOrder->updateOrderStatus($orderStatus);

        $this->saveMPOrderId($response->id, $this->session->data['order_id']);
        $this->response->redirect($this->url->link('checkout/success', '', true));
    }

    private function getPostData()
    {
        $postData = $this->request->post;
        for ($index = 1; $index <= 2; $index++) {
            $paymentDetailsKey = 'saved-creditcard-installments-' . $index;
            if (
                $postData['mundipaggSavedCreditCard-' . $index] === 'new' ||
                $postData['mundipaggSavedCreditCard-' . $index] === null
            ) {
                $paymentDetailsKey = 'new-creditcard-installments-' . $index;
            }
            if (!isset($postData['payment-details-' . $index])) {
                $postData['payment-details-' . $index] = $postData[$paymentDetailsKey];
            }
        }
        return $postData;
    }

    public function processTwoCreditCards()
    {
        $this->load();

        if (!$this->isValidTwoCreditCardsRequest($this->request->post)) {
            Log::create()
                ->error(LogMessages::INVALID_CREDIT_CARD_REQUEST, __METHOD__)
                ->withOrderId($this->session->data['order_id']);

            $this->response->redirect($this->url->link('checkout/failure'));
        }

        try {
            $postData = $this->getPostData();
            $twoCreditCards = new TwoCreditCards($this, $postData, $this->cart);
            $response = $twoCreditCards->processPayment();
        } catch (\Exception $e) {
            Log::create()
                ->error(LogMessages::CANNOT_CREATE_TWO_CREDIT_CARDS_ORDER, __METHOD__)
                ->withOrderId($this->session->data['order_id']);

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

    public function processBoletoCreditCard()
    {
        $this->load();

        if (!$this->isValidateCreditCardRequest()) {
            Log::create()
                ->error(LogMessages::INVALID_CREDIT_CARD_REQUEST, __METHOD__)
                ->withOrderId($this->session->data['order_id']);
            $this->response->redirect($this->url->link('checkout/failure'));
        }

        $post = $this->request->post;
        $formId = $post['mundipagg-formid'];

        //validate creditcard amount;
        $creditCardAmount = floatval($post['amount-' . $formId]);
        $orderId = $this->session->data['order_id'];
        $orderDetails = $this->model_checkout_order->getOrder($orderId);
        $orderTotal = floatval($orderDetails['total']);
        if ($creditCardAmount <= 0 || $creditCardAmount >= $orderTotal) {
            Log::create()
                ->error(LogMessages::INVALID_CREDIT_CARD_REQUEST, __METHOD__)
                ->withOrderId($this->session->data['order_id']);
            $this->response->redirect($this->url->link('checkout/failure'));
        }

        $card = $this->fillCreditCardData($formId);
        $orderData = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $orderData['saveCreditCard'] = $card['saveThisCard'];
        $orderData['boletoCreditCard'] = true;
        $orderData['creditCardAmount'] = floatval($card['amount']);

        try {
            $response = $this->createCreditCardOrder(
                (double)$card['paymentDetails'][2],
                $card['paymentDetails'][0],
                $orderData,
                $card['cardToken'],
                [$card['cardId']]
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

        if (isset($response->charges[0]->lastTransaction->success)) {

            $this->load->model('extension/payment/mundipagg_order_processing');
            $this->load->model('extension/payment/mundipagg_boleto_link');
            $model = $this->model_extension_payment_mundipagg_order_processing;
            $this->saveBoletoInfoInOrderHistory($response);
            $this->printBoletoUrl($response);
            $this->model_extension_payment_mundipagg_boleto_link->saveBoletoLink(
                $this->session->data['order_id'],
                $this->getBoletoUrl($response)
            );

        } else{
            Log::create()
                ->error(LogMessages::API_REQUEST_FAIL, __METHOD__)
                ->withOrderId($this->session->data['order_id'])
                ->withRequest(json_encode($response, JSON_PRETTY_PRINT));

            Log::create()
                ->error(LogMessages::UNKNOWN_API_RESPONSE, __METHOD__)
                ->withOrderId($this->session->data['order_id']);
            $this->response->redirect($this->url->link('checkout/failure', '', true));
        }

        $newOrder = $this->getOrder();
        $newOrder->updateOrderStatus($orderStatus);

        $this->saveMPOrderId($response->id, $this->session->data['order_id']);
        $this->response->redirect($this->url->link('checkout/success', '', true));
    }

    /**
     * @param $formId
     * @return array
     */
    private function fillCreditCardData($formId)
    {
        $post = $this->request->post;

        $card['saveThisCard'] = false;
        $card['cardId'] = null;

        // Payment with saved credit card
        if (
            !empty($post['mundipaggSavedCreditCard-' . $formId]) &&
            $post['mundipaggSavedCreditCard-' . $formId] != 'new'
        ) {
            return $this->fillSavedCreditCardData($post, $formId);
        }

        // New credit card
        return $this->fillNewCreditCardData($post, $formId);
    }

    private function fillSavedCreditCardData($post, $formId)
    {
        $installments = $post['saved-creditcard-installments-' . $formId];
        $card['paymentDetails'] = explode('|', $installments);
        $card['cardId'] = $post['mundipaggSavedCreditCard-' . $formId];
        $card['cardToken'] = null;
        $card['saveThisCard'] = false;

        if (isset($post['amount-' . $formId])) {
            $card['amount'] = $post['amount-' . $formId];
        }

        return $card;
    }

    private function fillNewCreditCardData($post, $formId)
    {
        $card['cardToken'] = $post['munditoken-' . $formId];
        $card['saveThisCard'] = false;
        $card['cardId'] = null;

        if (isset($post['save-this-credit-card-' . $formId])) {
            $card['saveThisCard'] =
                $this->onOff[
                $post['save-this-credit-card-' . $formId]]
            ;
        }

        $installments = $post['new-creditcard-installments-' . $formId];
        $card['paymentDetails'] = explode('|', $installments);
        if (isset($post['amount-' . $formId])) {
            $card['amount'] = $post['amount-' . $formId];
        }
        return $card;
    }

    private function prepareCreditCardsValidationData($requestData)
    {
        $cardsData = [];
        foreach ($requestData as $input => $value) {
            $inputData = explode('-',$input);
            $inputId = array_pop($inputData);

            if (!is_numeric($inputId)) {
                continue;
            }

            $key = implode('-',$inputData);

            if (!isset($cardsData[$inputId])) {
                $cardsData[$inputId] = [];
            }

            $cardsData[$inputId][$key] = $value;
        }
        return $cardsData;
    }

    private function isSavedCreditCardValidationNeeded()
    {
        $creditCardSettings = new CreditCardSettings($this);
        $savedCreditcard = new SavedCreditCard($this);

        $isSavedCreditCardEnabled = $creditCardSettings->isSavedCreditcardEnabled();
        $savedCreditcards = null;

        if ($isSavedCreditCardEnabled) {
            $savedCreditcards = $savedCreditcard->getSavedCreditcardList($this->customer->getId());
        }

        return $isSavedCreditCardEnabled && $savedCreditcards != null;
    }
    private function validateCreditCardData($cardData,$validateSaved)
    {
        if (!$validateSaved) {
            $cardData['mundipaggSavedCreditCard'] = 'new';
        }

        if (!isset($cardData['mundipaggSavedCreditCard'])) {
            return false;
        }

        if (!isset($cardData['amount'])) {
            return false;
        }

        $check = 'saved-creditcard-installments';
        if ($cardData['mundipaggSavedCreditCard'] === 'new') {
            $check = 'new-creditcard-installments';
            if (!isset($cardData['munditoken'])) {
                return false;
            }
        }

        if (!isset($cardData[$check])) {
            return false;
        }

        if (count(explode('|',$cardData[$check])) !== 3) {
            return false;
        }

        return true;
    }

    private function validateAmount($totalAmount)
    {
        $orderId = $this->session->data['order_id'];
        $orderDetails = $this->model_checkout_order->getOrder($orderId);
        $orderTotal = floatval($orderDetails['total']);

        return $totalAmount === $orderTotal;
    }

    private function isValidTwoCreditCardsRequest($requestData)
    {
        //prepare card data
        $cardsData = $this->prepareCreditCardsValidationData($requestData);
        $validateSaved = $this->isSavedCreditCardValidationNeeded();

        //do the validations
        $totalAmount = 0;
        foreach ($cardsData as $inputId => $cardData) {
            if (!$this->validateCreditCardData($cardData,$validateSaved)) {
                return false;
            }
            $totalAmount += floatval($cardData['amount']);
        }

        return $this->validateAmount($totalAmount);
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
            $total = $orderData['total'];
            if (isset($orderData['boletoCreditCard'])) {
                $total = $orderData['creditCardAmount'];
            }
            
            $amountWithInterest = $this->setInterestToAmount($total, $interest);
            $interestAmount = $amountWithInterest - $total;
            $totalAmountWithInterest = $orderData['total'] + $interestAmount;

            $this->mundipaggOrderUpdateModel->
                updateOrderAmountInOrder(
                    $orderData['order_id'],
                    $totalAmountWithInterest
                );

            $this->mundipaggOrderUpdateModel->
                updateOrderAmountInOrderTotals(
                    $orderData['order_id'],
                    $totalAmountWithInterest
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

    // index.php?route=extension/payment/mundipagg/api/installments&brand=visa&total=

    /**
     * The API responds to
     */
    public function api()
    {
        $postData = $this->request->post;
        $getData = $this->request->get;

        $action = explode('/', $getData['route']);
        $action = array_pop($action);

        $data = [
            'post' => $postData,
            'get' => $getData
        ];

        $verb = strtolower($_SERVER['REQUEST_METHOD']);

        $api = new Api($data, $verb, $this);
        $result = $api->{$action}();

        $this->sendResponse($result);
    }

    private function sendResponse($result)
    {
        $responseStatus = $result['status_code'];
        $responseData = $result['payload'];

        http_response_code($responseStatus);

        echo json_encode($responseData);
    }
}