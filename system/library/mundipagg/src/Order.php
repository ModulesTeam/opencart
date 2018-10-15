<?php
namespace Mundipagg;

require_once DIR_SYSTEM . 'library/mundipagg/vendor/autoload.php';

use MundiAPILib\Models\CreateSubscriptionRequest;
use MundiAPILib\Models\GetOrderResponse;
use MundiAPILib\Models\GetSubscriptionResponse;
use MundiAPILib\MundiAPIClient;
use MundiAPILib\Exceptions\ErrorException;
use MundiAPILib\Models\CreateOrderRequest;
use MundiAPILib\Models\CreateAddressRequest;
use MundiAPILib\Models\CreateCustomerRequest;
use MundiAPILib\Models\CreateShippingRequest;
use Mundipagg\Helper\Customer as CustomerHelper;
use Mundipagg\Helper\OpencartOrderInfo;
use Mundipagg\Settings\AntiFraud as AntiFraudSettings;
use Mundipagg\Settings\Boleto as BoletoSettings;
use Mundipagg\Settings\General as GeneralSettings;

use Mundipagg\Model\Creditcard;

/**
 * @method \MundiAPILib\Controllers\OrdersController getOrders()
 * @method \MundiAPILib\Controllers\CustomersController getCustomers()
 */
class Order
{
    private $orderInterest;
    private $orderInstallments;

    /**
     * @var MundiAPIClient
     */
    private $apiClient;
    private $openCart;
    private $settings;
    private $modelOrder;

    private $mundipaggCustomerModel;

    /**
     * @param array $openCart
     */
    public function __construct($openCart)
    {
        $this->generalSettings = new GeneralSettings($openCart);

        $this->openCart = $openCart;
        $this->orderInterest = 0;

        \Unirest\Request::verifyPeer(false);

        $this->apiClient = new MundiAPIClient($this->generalSettings->getSecretKey(), $this->generalSettings->getPassword());
    }

    public function __call($name, array $arguments)
    {
        if (method_exists($this->apiClient, $name)) {
            return call_user_func_array([$this->apiClient, $name], $arguments);
        }
    }

    public function getCharge($opencart_id, $charge_id = null)
    {
        return $this->modelOrder()->getCharge($opencart_id, $charge_id);
    }

    public function setInterest($interest)
    {
        $this->orderInterest = $interest;
    }

    public function setInstallments($installments)
    {
        $this->orderInstallments = $installments;
    }

    /**
     * Create a MundiPagg order
     * @param array $orderData
     * @param array $cart
     * @param string $paymentMethod
     * @param string $cardToken
     * @param int $cardId
     * @param null $multiBuyer
     * @return object
     * @throws \Exception
     */
    public function create(
        $orderData,
        $cart,
        $paymentMethod,
        $cardToken = null,
        $cardId = null,
        $multiBuyer = null
    ) {
        $items = $this->prepareItems($cart->getProducts());

        $createAddressRequest = $this->createAddressRequest($orderData);
        $createCustomerRequest = $this->createCustomerRequest($orderData, $createAddressRequest);
        $createShippingRequest = $this->createShippingRequest($orderData, $createAddressRequest, $cart);
        $totalOrderAmount = $orderData['total'];

        if (!empty($orderData['amountWithInterest'])) {
            $totalOrderAmount = $orderData['amountWithInterest'];
        }
        if (isset($orderData['boletoCreditCard'])) {
            $this->creditCardAmount = $orderData['creditCardAmount'];
            $interest = $orderData['amountWithInterest'] - $this->creditCardAmount;
            $this->boletoAmount = (floatval($orderData['total']) - $this->creditCardAmount);
            $this->creditCardAmount += $interest;

            $totalOrderAmount = $this->creditCardAmount + $this->boletoAmount;
            $orderData['amountWithInterest'] = $totalOrderAmount;
        }

        $isAntiFraudEnabled = $this->shouldSendAntiFraud($paymentMethod, $totalOrderAmount);
        $payments = $this->preparePayments($paymentMethod, $cardToken, $totalOrderAmount, $cardId, $multiBuyer);

        try {
            $orderInfoHelper = new OpencartOrderInfo($this->openCart);
            $recurrenceProduct = $orderInfoHelper->getRecurrenceProduct($cart);

            $orderType = 'Order';

            if ($recurrenceProduct !== null) {
                $orderType = 'Subscription';
                $CreateOrderRequest = $this->createSubscriptionRequest(
                    $orderData['order_id'],
                    $recurrenceProduct->getMundipaggPlanId(),
                    $payments,
                    $items,
                    $createCustomerRequest,
                    $createShippingRequest,
                    $this->generalSettings->getModuleMetaData(),
                    $isAntiFraudEnabled
                );
            } else {
                $CreateOrderRequest = $this->createOrderRequest(
                    $items,
                    $createCustomerRequest,
                    $payments,
                    $orderData['order_id'],
                    $this->getMundipaggCustomerId($orderData['customer_id']),
                    $createShippingRequest,
                    $this->generalSettings->getModuleMetaData(),
                    $isAntiFraudEnabled
                );
            }


        } catch (\Exception $e) {
            Log::create()
                ->error($e->getMessage(), __METHOD__)
                ->withOrderId($orderData['order_id'])
                ->withBackTraceInfo();
        }

        Log::create()
            ->info(LogMessages::CREATE_ORDER_MUNDIPAGG_REQUEST, __METHOD__)
            ->withOrderId($orderData['order_id'])
            ->withRequest(json_encode($CreateOrderRequest, JSON_PRETTY_PRINT));

        if (!$CreateOrderRequest->items && $orderType == 'Order') {
            return false;
        }

        $base = 'get' . $orderType . 's';
        $create = 'create' . $orderType;
        $order = $this->$base()->$create($CreateOrderRequest);

        $this->createOrUpdateCharge($orderData, $order);

        $this->createCustomerIfNotExists(
            $orderData['customer_id'],
            $order->customer->id
        );

        if (!empty($orderData['saveCreditCard'])) {
            $this->saveCreditCardIfNotExists($order);
        }

        Log::create()
            ->info(LogMessages::CREATE_ORDER_MUNDIPAGG_RESPONSE, __METHOD__)
            ->withOrderId($orderData['order_id'])
            ->withResponse(json_encode($order, JSON_PRETTY_PRINT));

        return $order;
    }

    public function createOrderForTwoCreditCards(
        $orderData,
        $cart,
        $paymentMethod,
        $amounts,
        $tokens,
        $cardIds,
        $multiBuyer
    ) {
        $items = $this->prepareItems($cart->getProducts());

        $createAddressRequest = $this->createAddressRequest($orderData);
        $createCustomerRequest = $this->createCustomerRequest($orderData, $createAddressRequest, $multiBuyer);
        $createShippingRequest = $this->createShippingRequest($orderData, $createAddressRequest, $cart);

        $totalOrderAmount = $orderData['total'];

        if (!empty($orderData['amountWithInterest'])) {
            $totalOrderAmount = $orderData['amountWithInterest'];
        }

        $isAntiFraudEnabled = $this->shouldSendAntiFraud($paymentMethod, $totalOrderAmount);
        $payments = $this->preparePayments($paymentMethod, $tokens, $amounts, $cardIds, $multiBuyer);

        try {
            $createOrderRequest = $this->createOrderRequest(
                $items,
                $createCustomerRequest,
                $payments,
                $orderData['order_id'],
                $this->getMundipaggCustomerId($orderData['customer_id']),
                $createShippingRequest,
                $this->generalSettings->getModuleMetaData(),
                $isAntiFraudEnabled
            );

        } catch (\Exception $e) {
            Log::create()
                ->error($e->getMessage(), __METHOD__)
                ->withOrderId($orderData['order_id'])
                ->withBackTraceInfo();
        }

        Log::create()
            ->info(LogMessages::CREATE_ORDER_MUNDIPAGG_REQUEST, __METHOD__)
            ->withOrderId($orderData['order_id'])
            ->withRequest(json_encode($createOrderRequest, JSON_PRETTY_PRINT));

        if (!$createOrderRequest->items) {
            return false;
        }

        $order = $this->getOrders()->createOrder($createOrderRequest);
        $this->createOrUpdateCharge($orderData, $order);

        $this->createCustomerIfNotExists(
            $orderData['customer_id'],
            $order->customer->id
        );

        // for one credit card
        if (!empty($orderData['saveCreditcard'])) {
            $this->saveCreditCardIfNotExists($order);
        }

        Log::create()
            ->info(LogMessages::CREATE_ORDER_MUNDIPAGG_RESPONSE, __METHOD__)
            ->withOrderId($orderData['order_id'])
            ->withResponse(json_encode($order, JSON_PRETTY_PRINT));

        return $order;
    }

    public function updateCharge($chargeId, $action, $amount = null)
    {
        try {
            $charges = $this->apiClient->getCharges();

            Log::create()
                ->info(LogMessages::UPDATE_CHARGE_MUNDIPAGG_REQUEST, __METHOD__)
                ->withRequest('Action: ' . $action . ',ChargeId: '.$chargeId);

            $data = array($chargeId);
            if ($amount) {
                $data[] = (object) array('amount' => (int) $amount);
            }
            $response = call_user_func_array(array($charges, $action.'Charge'), $data);

            Log::create()
                ->info(LogMessages::UPDATE_CHARGE_MUNDIPAGG_RESPONSE, __METHOD__)
                ->withResponse(json_encode($response, JSON_PRETTY_PRINT));

            return $response;
        } catch (ErrorException $e) {
            Log::create()
                ->error($e->getMessage(), __METHOD__)
                ->withLineNumber(__LINE__);
            throw new \Exception($e->getMessage());
        } catch (\Exception $e) {
            $const = "UNABLE_TO_{$action}_MUNDI_CHARGE";
            Log::create()
            ->error(LogMessages::$const, __METHOD__)
                ->withLineNumber(__LINE__);
                throw new \Exception(LogMessages::$const);
        }
    }

    public function modelOrder()
    {
        if (!$this->modelOrder) {
            $this->modelOrder = new Model\Order($this->openCart);
        }
        return $this->modelOrder;
    }

    public function createOrUpdateCharge(array $opencartOrder, $mundipaggOrder)
    {
        try {
            if (!is_object($mundipaggOrder)) {
                throw new \Exception();
            }
            $ModelOrder = $this->modelOrder();

            if (property_exists($mundipaggOrder, 'charges')) {
                foreach ($mundipaggOrder->charges as $charge) {
                    $data = array(
                        'opencart_id'     => $mundipaggOrder->code,
                        'charge_id'       => $charge->id,
                        'payment_method'  => $charge->paymentMethod,
                        'status'          => $charge->status,
                        'amount'          => $charge->amount,
                    );
                    if (isset($charge->paid_amount)) {
                        $data['paid_amount'] = $charge->paid_amount;
                    }
                    $ModelOrder->saveCharge($data);
                }
            } else {
                $data = array();
                if (property_exists($mundipaggOrder, 'canceledAt')) {
                    $data['canceled_amount'] = $mundipaggOrder->amount;
                } elseif (property_exists($mundipaggOrder, 'paidAt')) {
                    $data['paid_amount'] = $mundipaggOrder->amount;
                }
                if ($data) {
                    $data += array(
                        'opencart_id'     => $mundipaggOrder->code,
                        'charge_id'       => $mundipaggOrder->id,
                        'payment_method'  => $mundipaggOrder->paymentMethod,
                        'status'          => $mundipaggOrder->status,
                    );
                    if (is_a($mundipaggOrder,GetSubscriptionResponse::class)) {
                        $ModelOrder->saveSubscription($data);
                    }
                    else {
                        $ModelOrder->saveCharge($data);
                    }
                    $orderStatusId = $this->translateStatusFromMP($mundipaggOrder);
                    $ModelOrder->updateOrderStatus($mundipaggOrder->code, $orderStatusId);
                }
            }
        } catch (Exception $e) {
            Log::create()
            ->error(LogMessages::UNABLE_TO_SAVE_MUNDI_CHARGE, __METHOD__)
            ->withLineNumber(__LINE__);
        }
    }

    /**
     * @param array $items
     * @param CreateCustomerRequest $customer
     * @param array $payments
     * @param string $code
     * @param string $customerId
     * @param CreateShippingRequest $shipping
     * @param array $metadata
     * @param bool $isAntiFraudEnabled
     * @return CreateOrderRequest
     */
    private function createOrderRequest(
        $items,
        $customer,
        $payments,
        $code,
        $customerId,
        $shipping,
        $metadata = null,
        $isAntiFraudEnabled = false,
        $ip = null,
        $sessionId = null,
        $location = null,
        $device = null
    ) {
        $createOrderRequest = new CreateOrderRequest();

        $createOrderRequest->items = $items;
        $createOrderRequest->customer = $customer;
        $createOrderRequest->payments = $payments;
        $createOrderRequest->code = $code;
        $createOrderRequest->customerId = $customerId;
        $createOrderRequest->shipping = $shipping;
        $createOrderRequest->metadata = $metadata;
        $createOrderRequest->antifraudEnabled = $isAntiFraudEnabled;

        return $createOrderRequest;
    }

    /**
     * @param $code
     * @param $planId
     * @param $payments
     * @param $items
     * @param $customer
     * @param $shipping
     * @param null $metadata
     * @param bool $isAntiFraudEnabled
     * @param $
     */
    private function createSubscriptionRequest(
        $code,
        $planId,
        $payments,
        $items,
        $customer,
        $shipping,
        $metadata = null,
        $isAntiFraudEnabled = false
    ) {
        $createSubscriptionRequest = new CreateSubscriptionRequest();

        $payment = $payments[0];


        $createSubscriptionRequest->code = $code;
        $createSubscriptionRequest->planId = $planId;
        $createSubscriptionRequest->paymentMethod = $payment['payment_method'];
        $createSubscriptionRequest->customer = $customer;
        $createSubscriptionRequest->installments = 1;

        if ($createSubscriptionRequest->paymentMethod == 'credit_card') {
            $createSubscriptionRequest->cardToken = $payment['credit_card']['card_token'];
            $createSubscriptionRequest->installments = $payment['credit_card']['installments'];
        }

        $createSubscriptionRequest->shipping = $shipping;
        $createSubscriptionRequest->metadata = $metadata;
        $createSubscriptionRequest->antifraudEnabled = $isAntiFraudEnabled;

        return $createSubscriptionRequest;
    }

    /**
     * Prepare items to API's format
     * @param array $products
     * @return array
     */
    private function prepareItems($products)
    {
        $items = array();
        foreach ($products as $product) {
            $items[] = array(
                'amount'      => number_format($product['price'], 2, '', ''),
                'description' => $product['name'],
                'quantity'    => $product['quantity']
            );
        }
        return $items;
    }

    /**
     * @param array $orderData
     * @return \MundiAPILib\Models\CreateAddressRequest
     */
    private function createAddressRequest($orderData)
    {
        $config = $this->openCart->config;

        $createAddressRequest = new CreateAddressRequest();

        $createAddressRequest->street =
            $orderData['payment_address_1'];
        $createAddressRequest->number =
            $orderData['payment_custom_field'][$config->get('payment_mundipagg_mapping_number')];
        $createAddressRequest->zipCode =
            preg_replace('/\D/', '', $orderData['payment_postcode']);
        $createAddressRequest->neighborhood =
            $orderData['payment_address_2'];
        $createAddressRequest->city =
            $orderData['payment_city'];
        $createAddressRequest->state =
            $orderData['payment_zone_code'];
        $createAddressRequest->country =
            $orderData['payment_iso_code_2'];
        $createAddressRequest->complement =
            $orderData['payment_custom_field'][$config->get('payment_mundipagg_mapping_complement')];
        $createAddressRequest->metadata = null;

        return $createAddressRequest;
    }

    /**
     * @param array $orderData
     * @param CreateOrderRequest $createAddressRequest
     * @return array
     */
    private function createCustomerRequest($orderData, $createAddressRequest)
    {
        $customerHelper = new CustomerHelper();
        $phoneRequest = $customerHelper->createPhoneRequest($orderData['telephone']);

        return array(
            'name'     => $orderData['payment_firstname']." ".$orderData['payment_lastname'],
            'email'    => $orderData['email'],
            'phone'    => $phoneRequest,
            'document' => null,
            'type'     => "individual",
            'address'   => $createAddressRequest,
            'metadata' => null
        );
    }

    /**
     * @param string $paymentType
     * @param string $cardToken
     * @param float $orderAmount
     * @param array $cardId
     * @param null $multiBuyer
     * @return array
     * @throws \Exception Unsupported payment type
     */
    private function preparePayments($paymentType, $cardToken, $orderAmount, $cardId = [], $multiBuyer = null)
    {
        switch ($paymentType) {
            case 'boleto':
                return $this->getBoletoPaymentDetails($multiBuyer);
            case 'creditCard':
                $cardIdValue = empty($cardId) ? null : $cardId[0];

                return $this->getCreditCardPaymentDetails(
                    $cardToken,
                    $this->orderInstallments,
                    $orderAmount,
                    $cardIdValue,
                    $multiBuyer
                );
            case 'twoCreditCards':
                return $this->getTwoCreditCardsPaymentDetails(
                    $cardToken,
                    $this->orderInstallments,
                    $orderAmount,
                    $cardId,
                    $multiBuyer
                );
            case 'boletoCreditCard':
                $creditCardAmount = $this->creditCardAmount * 100 ;
                $boletoAmount = ceil($this->boletoAmount * 100);

                $boletoPayment = $this->getBoletoPaymentDetails(
                    isset($multiBuyer[4]) ? $multiBuyer[4] : null
                );
                $boletoPayment[0]['amount'] = $boletoAmount;

                $cardIdValue = empty($cardId) ? null : $cardId[0];
                $creditCardPayment = $this->getCreditCardPaymentDetails(
                    $cardToken,
                    $this->orderInstallments,
                    $orderAmount,
                    $cardIdValue,
                    isset($multiBuyer[3]) ? $multiBuyer[3] : null
                );
                $creditCardPayment[0]['amount'] = $creditCardAmount;

                return array_merge($boletoPayment,$creditCardPayment);
            default:
                /** TODO: log it */
                throw new \Exception('Unsupported payment type');
        }
    }

    private function getBoletoPaymentDetails($multiBuyer = null)
    {
        $boletoSettings = new BoletoSettings($this->openCart);

        $paymentDetails = [
            [
                'payment_method' => 'boleto',
                'boleto' => [
                    'bank'         => $boletoSettings->getBank(),
                    'instructions' => $boletoSettings->getInstructions(),
                    'due_at'       => $boletoSettings->getDueDate()
                ]
            ]
        ];

        if ($multiBuyer) {
            $paymentDetails[0]['customer'] = $multiBuyer;
        }

        return $paymentDetails;

    }

    /**
     * Get global setting of module and return true if is AuthAndCapture and
     * false if is AuthOnly
     *
     * @return boolean
     */
    private function isCapture()
    {
        return $this->openCart->config->get('payment_mundipagg_credit_card_operation') != 'Auth';
    }

    /**
     * @param string $token
     * @param int $installments
     * @param float $amount
     * @param int $cardId
     * @param null $multiBuyer
     * @return array
     */
    private function getCreditCardPaymentDetails(
        $token,
        $installments,
        $amount,
        $cardId = null,
        $multiBuyer = null
    ) {
        $amountInCents = number_format($amount, 2, '', '');
        $paymentDeatails = [
            [
                'payment_method' => 'credit_card',
                'amount' => $amountInCents,
                'credit_card' => [
                    'installments' => $installments,
                    'capture' => $this->isCapture()
                ]
            ]
        ];

        if ($token) {
            $paymentDeatails[0]['credit_card']['card_token'] = $token;
        }

        if ($multiBuyer) {
            $paymentDeatails[0]['customer'] = $multiBuyer;
        }

        $mundiPaggCreditcardId = $this->getMundipaggCardId($cardId);

        if ($mundiPaggCreditcardId) {
            $paymentDeatails[0]['credit_card']['card_id'] = $mundiPaggCreditcardId;
        }

        return $paymentDeatails;
    }

    /**
     * @param array $token
     * @param array $installments
     * @param array $amount
     * @param array $cardId
     *
     * @return array
     */
    private function getTwoCreditCardsPaymentDetails($token, $installments, $amount, $cardId, $multiBuyer = [])
    {
        $paymentDetails = [];

        // first card
        $paymentDetails[] = $this->getSingleCardPaymentDetails(
            $token[0],
            $amount[0],
            $installments[0],
            $cardId[0],
            isset($multiBuyer[1]) ? $multiBuyer[1] : []
        );

        // second card
        $paymentDetails[] = $this->getSingleCardPaymentDetails(
            $token[1],
            $amount[1],
            $installments[1],
            $cardId[1],
            isset($multiBuyer[2]) ? $multiBuyer[2] : []
        );

        return $paymentDetails;
    }

    private function getSingleCardPaymentDetails($token, $amount, $installments, $cardId = null, $multiBuyer = [])
    {
        $amountInCents = number_format($amount, 2, '', '');
        $paymentDetails = [
            'payment_method' => 'credit_card',
            'amount' => $amountInCents,
            'credit_card' => [
                'installments' => $installments,
                'capture' => $this->isCapture()
            ]
        ];

        if ($token) {
            $paymentDetails['credit_card']['card_token'] = $token;
        }

        $mundiPaggCreditCardId = $this->getMundipaggCardId($cardId);

        if ($mundiPaggCreditCardId) {
            $paymentDetails['credit_card']['card_id'] = $mundiPaggCreditCardId;
        }

        if ($multiBuyer) {
            $paymentDetails['customer'] = $multiBuyer;
        }

        return $paymentDetails;
    }

     /**
     * @param array $orderData
     * @param CreateAddressRequest $createAddressRequest
     * @param array $cart
     * @return CreateShippingRequest
     */
    private function createShippingRequest($orderData, $createAddressRequest, $cart)
    {
        if ($cart->hasShipping()) {

            $shippingCost = number_format(
                $cart->session->data['shipping_method']['cost'],
                2,
                '',
                ''
            );

            $shipping = [
                'amountInCents' => $shippingCost,
                'description' => $cart->session->data['shipping_method']['title'],
                'recipientName' => $orderData['shipping_firstname'] . " " . $orderData['shipping_lastname'],
                'recipientPhone' => $orderData['telephone'],
                'addressId' => null,
                'maxDeliveryDate',
                'estimatedDeliveryDate'
            ];

            return new CreateShippingRequest(
                $shipping['amountInCents'],
                $shipping['description'],
                $shipping['recipientName'],
                $shipping['recipientPhone'],
                $shipping['addressId'],
                $createAddressRequest,
                $shipping['maxDeliveryDate'],
                $shipping['addreestimatedDeliveryDatessId']
            );
        }

        return null;
    }

    /**
     * @param int $customerId
     * @return String
     */
    private function getMundipaggCustomerId($customerId)
    {
        $customer = $this->mundipaggCustomerModel->get($customerId);
        if ($customer['mundipagg_customer_id']) {
            return $customer['mundipagg_customer_id'];
        }
        return null;
    }

    public function setCustomerModel($mundipaggCustomerModel)
    {
        $this->mundipaggCustomerModel = $mundipaggCustomerModel;
    }

    /**
     * Update opencart order status with the mundipagg translated status
     *
     * @param mixed $orderStatus
     * @param string $comment
     * @return void
     */
    public function updateOrderStatus($orderStatus, $comment = '')
    {
        $this->openCart->load->model('extension/payment/mundipagg_order_processing');
        $model = $this->openCart->model_extension_payment_mundipagg_order_processing;

        $model->addOrderHistory(
            $this->openCart->session->data['order_id'],
            $orderStatus,
            $comment,
            true
        );

        $model->setOrderStatus(
            $this->openCart->session->data['order_id'],
            $orderStatus
        );
    }

    /**
     * It maps the statuses from mundipagg and those used in opencart
     *
     * @param mixed $response
     * @return string
     */
    public function translateStatusFromMP($response)
    {
        $statusFromMP = strtolower($response->status);

        $this->openCart->load->model('localisation/order_status');
        $statusModel = $this->openCart->model_localisation_order_status;

        switch ($statusFromMP) {
            case 'paid':
                $status = $statusModel->getOrderStatus(2)['order_status_id'];
                break;
            case 'canceled':
                $status = $statusModel->getOrderStatus(7)['order_status_id'];
                break;
            case 'failed':
                $status = $statusModel->getOrderStatus(10)['order_status_id'];
                break;
            default: //handles future and active subscriptions.
            case 'pending':
                $status = $statusModel->getOrderStatus(1)['order_status_id'];
                break;
            /*default:
                $status = false;*/

        }

        return $status;
    }

    private function setInterestToAmount($amount, $interest)
    {
        return round($amount + ($amount * ($interest * 0.01)), 2);
    }

    /**
     * Check if anti fraud is enabled and order
     * amount is bigger than minimum value.
     * @param string $paymentMethod
     * @param float $orderAmount
     * @return bool
     */
    private function shouldSendAntiFraud($paymentMethod, $orderAmount)
    {
        $antiFraudSettings = new AntiFraudSettings($this->openCart);

        $minOrderAmount = $antiFraudSettings->getOrderMinVal();
        $antiFraudStatus = $antiFraudSettings->isEnabled();

        if ($antiFraudStatus &&
            $paymentMethod === 'creditCard' &&
            $orderAmount >= $minOrderAmount
        ) {
            return true;
        }

        return false;
    }

    private function createCustomerIfNotExists($opencartCustomerId, $mundipaggCustomerId)
    {
        if (
            !$this->mundipaggCustomerModel->exists($opencartCustomerId)
        ) {
            $this->saveCustomer(
                $opencartCustomerId,
                $mundipaggCustomerId
            );
        }
    }

    /**
     * Save MundiPagg customer in Opencart DB
     * @param GetOrderResponse $mundiPaggOrder
     * @param array $opencartOrder
     */
    private function saveCustomer($opencartCustomerId, $mundipaggCustomerId)
    {
        $this->mundipaggCustomerModel->create(
            $opencartCustomerId,
            $mundipaggCustomerId
        );
    }

    /**
     * Save credit card data when it's enabled
     * @param GetOrderResponse $order
     */
    private function saveCreditCardIfNotExists($order)
    {
        $savedCreditCard = new Creditcard($this->openCart);

        if (!empty($order->charges)) {
            foreach ($order->charges as $charge) {
                if (
                    !$savedCreditCard->creditCardExists(
                        $charge->lastTransaction->card->id
                    )
                ) {
                    $savedCreditCard->saveCreditcard(
                        $order->customer->id,
                        $charge->lastTransaction->card,
                        $order->code
                    );
                }
            }
        }
    }

    /**
     * Get MundiPagg crtedit card id by primary key
     * from cards table(opencart).
     * @param int $id
     * @return string
     */
    private function getMundipaggCardId($cardId)
    {
        if($cardId && $cardId != "") {
            $savedCreditcard = new Creditcard($this->openCart);
            $mundiPaggCreditcardId = $savedCreditcard->getCreditcardById($cardId);

            return $mundiPaggCreditcardId['mundipagg_creditcard_id'];
        }

        return false;
    }
}
