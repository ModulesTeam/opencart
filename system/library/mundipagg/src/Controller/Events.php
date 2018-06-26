<?php
namespace Mundipagg\Controller;

use Mundipagg\Model\Order;
use Mundipagg\Helper\AdminMenu as MundipaggHelperAdminMenu;
use Mundipagg\Helper\ProductPageChanges as MundipaggHelperProductPageChanges;

require_once DIR_SYSTEM . 'library/mundipagg/vendor/autoload.php';

class Events
{
    private $openCart;
    private $template;
    private $load;

    public function __construct($openCart, $template, $load = null)
    {

        $this->openCart = $openCart;
        $this->template = $template;
        $this->load = $load;
    }

    public function __call($name, array $arguments)
    {
        if (method_exists($this, $name)) {
            return call_user_func_array([$this, $name], $arguments);
        }

        return false;
    }

    /**
     * Show the Mundipagg's button in order list
     * @param array $data
     * @return mixed
     */
    public function orderListEntry($data)
    {
        $cancel = [];
        $cancelCapture = [];

        $ids = array_map(function ($row) {
            return (int) $row['order_id'];
        }, $data['orders']);

        $Order = new Order($this->openCart);
        $orders = $Order->getOrders(
            [
                'order_id' => $ids,
                'order_status_id' => [1,15,2]
            ],
            [
                'order_status_id',
                'order_id'
            ]
        );

        foreach ($orders->rows as $order) {
            switch ($order['order_status_id']) {
                case 1: // I can capture, cancel
                    $cancelCapture[] = '#form-order table tbody tr ' .
                        'input[name="selected[]"][value=' . $order['order_id'] . ']';
                    break;
                case 15: // I can cancel
                case 2:
                    $cancel[] = '#form-order table tbody tr ' .
                        'input[name="selected[]"][value=' . $order['order_id'] . ']';
            }
        }

        $templateData['cancelCapture'] = implode(',', $cancelCapture);
        $templateData['cancel'] = implode(',', $cancel);
        $templateData['httpServer'] = HTTPS_SERVER;

        $footer  = $this->openCart->load->view('extension/payment/mundipagg/order_actions', $templateData);

        $data['footer'] = $footer . $data['footer'];

        if (isset($this->openCart->session->data['error_warning'])) {
            $data['error_warning'] = $this->openCart->session->data['error_warning'];
            unset($this->openCart->session->data['error_warning']);
        }

        foreach ($data as $key => $value) {
            $this->template->set($key, $value);
        }

        return $this->template;
    }

    /**
     * Adds the Mundipagg menu on the Opencart admin menu
     * @param array $data
     * @return mixed
     */
    public function columnLeftEntry($data)
    {
        $mundipaggMenuHelper = new MundipaggHelperAdminMenu($this->openCart);
        $mundipaggMenu = $mundipaggMenuHelper->getMenu();

        array_unshift($data['menus'], $mundipaggMenu);

        foreach ($data as $key => $value) {
            $this->template->set($key, $value);
        }

        return $this->template;
    }

    public function productFormEntry($data)
    {
        if (isset($this->openCart->request->get['mundipagg_plan'])) {
            return $this->handleRecurrencePlanTab($data);
        }

        if (isset($this->openCart->request->get['mundipagg_single'])) {
            return $this->handleRecurrenceSingleTab($data);
        }
    }

    public function handleRecurrencePlanTab($data)
    {

       $path = 'extension/payment/mundipagg/recurrence/';

       $productFormTemplate = $this->openCart->load->view(
           $path . 'plans/productFormTabHeader'
       );

       $planform['formPlan'] = $path . 'templates/form_plan.twig';
       $planform['panelPlanFrequency'] = $path . 'templates/panelPlanFrequency.twig';
       $planform['formBase'] = $path . 'templates/form_base.twig';

       $productFormTabContentTemplate = $this->openCart->load->view(
           $path . 'plans/productFormTabContent',
           $planform
       );

       $helper = new MundipaggHelperProductPageChanges($this->openCart);
       $data['heading_title'] = 'Plano';
       $data['text_form'] = 'Criar plano';

       $data['tab_design'] = $data['tab_design'] . $productFormTemplate;
       $data['footer'] = $data['footer'] . $productFormTabContentTemplate;

       foreach ($data as $key => $value) {
           $this->template->set($key, $value);
       }

       return $this->template;
    }

    public function handleRecurrenceSingleTab($data)
    {
       $path = 'extension/payment/mundipagg/recurrence/';

       $productFormTemplate = $this->openCart->load->view(
           $path . 'plans/productFormTabHeader'
       );

       $planform['formPlan'] = $path . 'templates/form_plan.twig';
       $planform['panelPlanFrequency'] = $path . 'templates/panelPlanFrequency.twig';
       $planform['formBase'] = $path . 'templates/form_base.twig';

       $productFormTabContentTemplate = $this->openCart->load->view(
           $path . 'plans/productFormTabContent',
           $planform
       );

       $helper = new MundipaggHelperProductPageChanges($this->openCart);
       $data['heading_title'] = 'Single';
       $data['text_form'] = 'Criar plano';

       $data['tab_design'] = $data['tab_design'] . $productFormTemplate;
       $data['footer'] = $data['footer'] . $productFormTabContentTemplate;

       foreach ($data as $key => $value) {
           $this->template->set($key, $value);
       }

       return $this->template;
    }
}
