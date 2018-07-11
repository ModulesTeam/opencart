<?php

require_once DIR_SYSTEM . 'library/mundipagg/vendor/autoload.php';

use Mundipagg\Settings\CreditCard as CreditCardSettings;
use Mundipagg\Settings\Boleto as BoletoSettings;
use Mundipagg\Settings\BoletoCreditCard as BoletoCreditCardSettings;
use Mundipagg\Settings\Recurrence as RecurrenceSettings;
use Mundipagg\Controller\Events as MundipaggEvents;
use Mundipagg\Helper\Common as MundipaggHelperCommon;
use Mundipagg\Controller\Charges as MundipaggCharges;
use Mundipagg\Controller\Recurrence\Plans as MundipaggRecurrencePlans;
use Mundipagg\Controller\Recurrence\Subscriptions as MundipaggSubscriptions;
use Mundipagg\Controller\Recurrence\Single as MundipaggRecurrenceSingle;
use Mundipagg\Controller\Recurrence\Templates as MundipaggRecurrenceTemplates;

use MundiAPILib\MundiAPIClient;
use Mundipagg\Order;

/**
 * ControllerExtensionPaymentMundipagg
 *
 * @package Mundipagg
 */
class ControllerExtensionPaymentMundipagg extends Controller
{
    /**
     * @var array
     */
    private $error = array();

    /**
     * @var array
     */
    private $data = array();

    /**
     * This method creates a route to module settings
     */
    public function settings()
    {
        $this->index();
    }

    /**
     * Load basic data and call postRequest or getRequest methods accordingly
     *
     * @return void
     */
    public function index()
    {
        $this->load->language('extension/payment/mundipagg');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $this->loadPaymentTemplates();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->postRequest();
        } else {
            $this->getRequest();
        }
    }

    private function loadPaymentTemplates()
    {
        $path = 'extension/payment/mundipagg/';

        $this->data['recurrenceSettings'] = $path . 'recurrence/settings.twig';
    }

    /**
     * This method is called when the module is being installed
     *
     * First, it save an event and then call the install method from
     * module model
     *
     * @return void
     */
    public function install()
    {
        $this->load->model('extension/payment/mundipagg');
        $this->model_extension_payment_mundipagg->install();
    }

    /**
     * This method is called when the module is being removed
     *
     * It calls the methods from model responsible for delete all
     * data created and used by module
     *
     * @return void
     */
    public function uninstall()
    {
        $this->load->model('extension/payment/mundipagg');
        $this->model_extension_payment_mundipagg->uninstall();
    }

    public function subscriptions()
    {
        $subscriptions = new MundipaggSubscriptions($this);

        if (isset($this->request->get['action'])) {
            $action = $this->request->get['action'];
            $subscriptions->$action();

            return;
        }

        $subscriptions->index();
    }

    public function templates()
    {
        $templates = new MundipaggRecurrenceTemplates($this);

        if (isset($this->request->get['action'])) {
            $action = $this->request->get['action'];
            $templates->$action();

            return;
        }

        $templates->index();
    }

    public function single()
    {
        $single = new MundipaggRecurrenceSingle($this);

        if (isset($this->request->get['action'])) {
            $action = $this->request->get['action'];
            $single->$action();

            return;
        }

        $single->index();
    }

    public function plans()
    {
        $plans = new MundipaggRecurrencePlans($this);

        if (isset($this->request->get['action'])) {
            $action = $this->request->get['action'];
            $plans->$action();

            return;
        }

        $plans->index();
    }

    public function previewChangeCharge()
    {
        $charges = new MundipaggCharges($this);

        $previewHtml = $charges->getPreviewHtml();
        $this->response->setOutput($previewHtml);
    }

    public function getTotalsData($order_info)
    {
        $data = [];
        $totals = $this->model_sale_order
            ->getOrderTotals($this->request->get['order_id']);

        foreach ($totals as $total) {
            $data[] = [
                'title' => $total['title'],
                'text'  => $this->currencyFormat($total['value'], $order_info)
            ];
        }

        return $data;
    }

    public function getVoucherData($order_info)
    {
        $data = [];
        $vouchers = $this->model_sale_order
            ->getOrderVouchers($this->request->get['order_id']);

        foreach ($vouchers as $voucher) {
            $data[] = [
                'description' => $voucher['description'],
                'amount' => $this->currencyFormat(
                    $voucher['amount'],
                    $order_info
                ),
                'href' => $this->url->link(
                    'sale/voucher/edit', 'user_token=' .
                    $this->session->data['user_token'] .
                    '&voucher_id=' . $voucher['voucher_id'],
                true)
            ];
        }

        return $data;
    }

    public function getDataProducts($order_info)
    {
        $products = $this->model_sale_order
                        ->getOrderProducts($this->request->get['order_id']);

        $data = [];
        foreach ($products as $product) {
            $option_data =
                $this->getProductOptionsData($product['order_product_id']);

            $data[] = [
                'order_product_id' => $product['order_product_id'],
                'product_id'       => $product['product_id'],
                'name'             => $product['name'],
                'model'            => $product['model'],
                'option'           => $option_data,
                'quantity'         => $product['quantity'],
                'price'            => $this->currencyFormat(
                    $product['price'],
                    $order_info,
                    $product['tax']
                ),
                'total' => $this->currencyFormat(
                    $product['total'],
                    $order_info,
                    $product['tax'] * $product['quantity']
                ),
                'href' => $this->url->link(
                    'catalog/product/edit', 'user_token=' .
                    $this->session->data['user_token'] .
                    '&product_id=' . $product['product_id'],
                true)
            ];
        }

        return $data;
    }

    public function getProductOptionsData($orderProductId)
    {
        $data = [];
        $options = $this->model_sale_order
                    ->getOrderOptions(
                        $this->request->get['order_id'],
                        $orderProductId
                    );

        foreach ($options as $option) {
            if ($option['type'] != 'file') {
                $data[] = [
                    'name'  => $option['name'],
                    'value' => $option['value'],
                    'type'  => $option['type']
                ];
                continue;
            }

            $this->load->model('tool/upload');

            $upload_info = $this->model_tool_upload
                                ->getUploadByCode($option['value']);

            if ($upload_info) {
                $data[] = [
                    'name'  => $option['name'],
                    'value' => $upload_info['name'],
                    'type'  => $option['type'],
                    'href'  => $this->url->link(
                        'tool/upload/download', 'user_token=' .
                        $this->session->data['user_token'] .
                        '&code=' . $upload_info['code'],
                        true)
                ];
            }
        }

        return $data;
    }

    public function currencyFormat($price, $orderInfo, $productTax = 0)
    {
        $tax = $this->config->get('config_tax') ? $productTax : 0;

        return $this->currency->format(
            $price + $tax,
            $orderInfo['currency_code'],
            $orderInfo['currency_value']
        );
    }

    public function getChargesData($order_info, $status)
    {
        $charges = new MundipaggCharges($this);
        return $charges->getData($order_info, $status);
    }

    private function validateUpdateCharge()
    {
        if (!isset($this->request->get['order_id'])) {
            return false;
        }
        if (!in_array($this->request->get['status'], array('cancel', 'capture'))) {
            return false;
        }
        return true;
    }

    public function confirmUpdateCharge()
    {
        $this->setLayoutComponents();

        $theme = $this->config->get('config_theme');
        $themeDirectory = $this->config->get('theme_' . $theme . '_directory');
        $this->data['themeDirectory'] = '../catalog/view/theme/' . $themeDirectory;
        $this->data['heading_title'] = 'Confirm value';
        $this->data['cancel']   = 'javascript:history.go(-1);';
        $chargeId   = $this->request->get['charge'];
        $orderId = $this->request->get['order_id'];
        $orderInfo = $this->getOrderInfo($orderId);
        $order = new Mundipagg\Order($this);
        $charge = $order->getCharge(
            $orderId,
            $chargeId
        );

        $amountInCents = str_replace('.', ',', $charge->row['amount'] / 100);

        $this->data['charge_amount'] = $amountInCents;
        $this->data['currency_code'] =
            $this->currency->getSymbolLeft($orderInfo['currency_code']);
        $this->data['form_action'] =  $this->url->link(
            'extension/payment/mundipagg/updateCharge',
            'user_token=' . $this->session->data['user_token'] .
            '&order_id=' . $orderId .
            '&charge=' . $chargeId .
            '&status=' . $this->request->get['status'],
            true
        );
        $this->response->setOutput(
            $this->load->view(
                'extension/payment/mundipagg_confirmUpdateCharge',
                $this->data
            )
        );
    }

    public function getOrderInfo($orderId)
    {
        $this->load->model('sale/order');

        return $this->model_sale_order->getOrder($orderId);
    }

    public function updateCharge()
    {
        try {
            if (!$this->validateUpdateCharge()) {
                throw new \Exception('Invalid data');
            }
            $this->load->model('sale/order');
            $order_id = $this->request->get['order_id'];
            $order_info = $this->model_sale_order->getOrder($order_id);
            if (!$order_info) {
                throw new \Exception('Order not found');
            }
            $status = $this->request->get['status'];
            $order = new Mundipagg\Order($this);
            $charges = $order->getCharge($order_id);
            $charge_id = $this->request->get['charge'];
            $chargeAmount = floatval(str_replace(',','.',$this->request->post['charge_amount']));
            $chargeAmount *= 100;
            foreach ($charges->rows as $charge) {
                if ($charge['charge_id'] == $charge_id) {
                    if ($charge['can_cancel']) {
                        $charge = $order->updateCharge($charge_id, $status, $chargeAmount);
                        $word = $status == 'cancel' ? 'canceled' : 'captured';
                        $this->session->data['success'] = "Charge $word with sucess!";
                    } else {
                        $word = $status == 'cancel' ? 'cancel' : 'capture';
                        $this->session->data['error_warning'] = "Charge don't available to $word";
                    }
                    break;
                }
            }
            if (empty($this->session->data['success']) && !$this->session->data['error_warning']) {
                $word = $status == 'cancel' ? 'cancel' : 'capture';
                $this->session->data['error_warning'] = "Fail on $word charge";
            } else {
                $order->createOrUpdateCharge($order_info, $charge);
                $this->load->model('extension/payment/mundipagg_order');
                $this->model_extension_payment_mundipagg_order->addOrderHistory(
                    $order_id,
                    $order->translateStatusFromMP($charge),
                    "Charge $word. Amount: " . $this->request->post['charge_amount']
                );
            }
        } catch (\Exception $e) {
            $this->session->data['error_warning'] = $e->getMessage();
        }
        $this->redirect('sale/order');
    }

    /**
     * Deal with post requests, in other words, settings being changed
     *
     * @return void
     */
    private function postRequest()
    {
        // validate data here
        if (!$this->validate()) {
            return;
        }

        $this->saveSettings($this->request->post);

        $this->session->data['success'] = $this->language->get('misc')['success'];
        $this->redirect('marketplace/extension', '&type=payment');
    }

    private function redirect($route, $queryString = '', $responseCode = 302)
    {
        http_response_code($responseCode);
        $this->response->addHeader('Location: '.
            str_replace(
                ['&amp;', "\n", "\r"],
                ['&', '', ''],
                $this->url->link(
                    $route,
                    'user_token=' . $this->session->data['user_token'] . $queryString,
                    true
                )
            ));
        $this->response->setOutput(true);
    }

    /**
     * Deal with get requests
     *
     * Get layout components, set language, get bread crumbs
     * and show the form with the previously saved settings
     *
     * @return void
     */
    private function getRequest()
    {
        $this->setLayoutComponents();
        $this->setLanguageData();
        $this->setBreadCrumbs();
        $this->setFormControlData();
        $this->getSavedSettings();
        $this->getCustomFields();

        $this->response->setOutput(
            $this->load->view(
                'extension/payment/mundipagg',
                $this->data
            )
        );
    }

    /**
     * This method saves module settings
     *
     * @param array $postRequest Settings posted from admin panel
     * @return void
     */
    private function saveSettings($postRequest)
    {
        // save credit card payment information
        $this->load->model('extension/payment/mundipagg');
        $this->model_extension_payment_mundipagg->savePaymentInformation(
            $postRequest['creditCard']
        );

        unset($postRequest['creditCard']);

        // save module settings
        $modules = ['payment_mundipagg' => '/^payment_mundipagg/'];

        foreach ($modules as $module => $pattern) {
            $this->model_setting_setting->editSetting(
                $module,
                $this->getSettingsFor($pattern, $postRequest)
            );
        }
    }

    /**
     * It gets the request and return an array with the correct information to be stored
     *
     * @param string $pattern Contain the pattern to be searched for
     * @param array $request Post request from admin panel
     * @return array
     */
    private function getSettingsFor($pattern, $request)
    {
        return array_filter(
            $request,
            function ($key) use ($pattern) {
                return preg_match($pattern, $key);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Sets opencart dashboard layout components
     *
     * It puts opencart header, left column and footer
     *
     * @return void
     */
    private function setLayoutComponents()
    {
        $this->data['heading_title'] = $this->language->get('heading_title');
        $this->data['header'] = $this->load->controller('common/header');
        $this->data['column_left'] = $this->load->controller('common/column_left');
        $this->data['footer'] = $this->load->controller('common/footer');
    }

    /**
     * Get language data from language file
     *
     * @return void
     */
    private function setLanguageData()
    {
        $this->data['general'] = $this->language->get('general');
        $this->data['credit_card'] = $this->language->get('credit_card');
        $this->data['boleto'] = $this->language->get('boleto');
        $this->data['boletoCreditCard'] = $this->language->get('boletoCreditCard');
        $this->data['antifraud'] = $this->language->get('antifraud');
        $this->data['misc'] = $this->language->get('misc');
        $this->data['extra'] = $this->language->get('extra');
        $this->data['recurrence'] = $this->language->get('recurrence');
    }

    /**
     * Set bread crumbs, which will be shown in the dashboard panel
     *
     * @return void
     */
    private function setBreadCrumbs()
    {
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(
                'common/dashboard',
                'user_token=' . $this->session->data['user_token'],
                true
            ));

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link(
                'marketplace/extension',
                'user_token=' . $this->session->data['user_token'] . '&type=payment',
                true
            ));

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'extension/payment/mundipagg',
                'user_token=' . $this->session->data['user_token'],
                true
            ));
    }

    /**
     * Set the action to the save/cancel buttons from dashboard
     *
     * @return void
     */
    private function setFormControlData()
    {
        $this->data['action'] = $this->url->link(
            'extension/payment/mundipagg',
            'user_token=' . $this->session->data['user_token'],
            true
        );

        $this->data['cancel'] = $this->url->link(
            'marketplace/extension',
            'user_token=' . $this->session->data['user_token'] . '&type=payment',
            true
        );
    }


    /**
     * Get the previously saved module settings
     *
     * @return void
     */
    private function getSavedSettings()
    {
        $creditCardSettings = new CreditCardSettings($this);
        $boletoSettings = new BoletoSettings($this);
        $boletoCreditCardSettings = new BoletoCreditCardSettings($this);
        $recurrenceSettings = new RecurrenceSettings($this);

        $this->data['settings'] = array(
            'general_status'             => $this->config->get('payment_mundipagg_status'),
            'general_mapping_number'     => $this->config->get('payment_mundipagg_mapping_number'),
            'general_mapping_complement' => $this->config->get('payment_mundipagg_mapping_complement'),
            'general_prod_secret_key'    => $this->config->get('payment_mundipagg_prod_secret_key'),
            'general_test_secret_key'    => $this->config->get('payment_mundipagg_test_secret_key'),
            'general_prod_pub_key'       => $this->config->get('payment_mundipagg_prod_public_key'),
            'general_test_pub_key'       => $this->config->get('payment_mundipagg_test_public_key'),
            'general_test_mode'          => $this->config->get('payment_mundipagg_test_mode'),
            'general_log_enabled'        => $this->config->get('payment_mundipagg_log_enabled'),
            'general_payment_title'      => $this->config->get('payment_mundipagg_title'),
            'extra_multibuyer_enabled'   => $this->config->get('payment_mundipagg_multibuyer_enabled'),
            'antifraud_status'           => $this->config->get('payment_mundipagg_antifraud_status'),
            'antifraud_minval'           => $this->config->get('payment_mundipagg_antifraud_minval'),
        );

        $this->data['settings'] = array_merge(
            $this->data['settings'],
            $creditCardSettings->getAllSettings(),
            $boletoSettings->getAllSettings(),
            $boletoCreditCardSettings->getAllSettings(),
            $recurrenceSettings->getAllSettings()
        );

        $this->load->model('extension/payment/mundipagg');
        $this->data['creditCard'] = $this->model_extension_payment_mundipagg->getCreditCardInformation();
        $this->data['boletoInfo'] = $this->model_extension_payment_mundipagg->getBoletoInformation();
        $this->data['logs_files'] = $this->getLogFiles();
    }

    private function getLogFiles()
    {
        $files = array();
        foreach (glob(DIR_LOGS . '*.log') as $filename) {
            $shortName = str_replace(array(DIR_LOGS, '.log'), '', $filename);
            $files[] = array(
                'name' => $shortName,
                'size' => filesize($filename),
                'url_download' => $this->url->link(
                    'extension/payment/mundipagg/downloadLog',
                    'user_token=' . $this->session->data['user_token'].
                    '&name=' . $shortName,
                    true
                )
            );
        }
        return $files;
    }

    public function downloadLog()
    {
        $filename = $this->request->get['name'];
        $filename = str_replace(array('.', '/'), '', $filename) . '.log';
        if (is_file(DIR_LOGS.$filename)) {
            header('Content-Type: application/octet-stream');
            header('Content-Transfer-Encoding: Binary');
            header('Content-disposition: attachment; filename="' .$filename.'"');
            readfile(DIR_LOGS.$filename);
        }
    }

    /**
     * Set error messages
     *
     * TODO: make this method cover more possible errors
     *
     * @return void
     */
    private function setError()
    {
        if (isset($this->error['error_service_key'])) {
            $data['error_service_key'] = $this->error['error_service_key'];
        } else {
            $data['error_service_key'] = '';
        }
    }

    /**
     * Simple validation
     *
     * Validates user permission
     * TODO: think about (and implement) other validation necessities
     *
     * @return Boolean
     */
    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/mundipagg')) {
            // put error message inside language file
            $this->error['warning'] = 'Permission error';
        }

        return !$this->error;
    }

    /**
     * Return an array with custom fields
     */
    private function getCustomFields()
    {
        $this->load->model('customer/custom_field');
        $customFields = $this->model_customer_custom_field->getCustomFields();
        $this->data['general_custom_fields'] = $customFields;
    }

    /**
     * @param string $route
     * @param array $data
     * @param $template
     * @return mixed
     * @throws Exception
     */
    public function callEvents($route, $data = array(), $template = null)
    {
        $mundipaggEvents = new MundipaggEvents(
            $this,
            new Template($this->registry->get('config')->get('template_engine'))
        );

        $helper = new MundipaggHelperCommon($this);
        $method =
            $helper->fromSnakeToCamel(explode('/', $route)[1]) . "Entry";
        $template = $mundipaggEvents->$method($data);

        if ($template) {
            return $template->render(
                $this->config->get('template_directory') . $route,
                $this->config->get('template_cache')
            );
        }
    }

    public function getChargeModalInformation()
    {
        $a = 1;
    }
}
