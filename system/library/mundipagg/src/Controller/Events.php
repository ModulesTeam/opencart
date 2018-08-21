<?php
namespace Mundipagg\Controller;

use Mundipagg\Aggregates\RecurrencyProduct\RecurrencyProductRoot;
use Mundipagg\Aggregates\RecurrencyProduct\RecurrencySubproductValueObject;
use Mundipagg\Model\Order;
use Mundipagg\Helper\AdminMenu as MundipaggHelperAdminMenu;
use Mundipagg\Helper\ProductPageChanges as MundipaggHelperProductPageChanges;
use Mundipagg\Repositories\Bridges\OpencartDatabaseBridge;
use Mundipagg\Repositories\RecurrencyProductRepository;
use Mundipagg\Repositories\TemplateRepository;

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

    public function productListEntry($data)
    {
        if (isset($this->openCart->request->get['filter_mp_type'])) {
            $data['mp_selected_product_type_filter'] =
                strtolower($this->openCart->request->get['filter_mp_type']);
        }

        $script = $this->openCart->load
            ->view('extension/payment/mundipagg/product_actions', $data);

        $data['footer'] = $script . $data['footer'];

        foreach ($data as $key => $value) {
            $this->template->set($key, $value);
        }

        return $this->template;
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

    public function productEntry($data)
    {
        $get = $this->openCart->request->get;
        $action = explode('/', $get['route']);
        $action = end($action);

        switch ($action) {
            case "delete":
                return $this->handleProductDelete();
            case "product":
                return $this->handleProductIndex();
        }
    }

    protected function handleProductIndex()
    {
        $opencartSessionData = $this->openCart->session->data;
        $errorData = [];
        if (isset($opencartSessionData['mundipagg-cant-delete-product-data'])) {
            $cantDeleteData = $opencartSessionData['mundipagg-cant-delete-product-data'];
            unset($opencartSessionData['mundipagg-cant-delete-product-data']);

            $errorData['warning'] = '';
            foreach ($cantDeleteData as $product) {
                $productError = "Can't delete product '<strong>{$product['name']}</strong>' because this product is in the following plans:<br /><ul>";
                foreach ($product['plans'] as $planName) {
                    $productError .= "<li>$planName</li>";
                }
                $productError .= "</ul>";
            }
            $errorData['warning'] = $productError;
        }

        $this->openCart->session->data = $opencartSessionData;
        if(count($errorData)) {
            return $this->handleProductIndexError($errorData);
        }

        return $this->handleProductIndexList();
    }

    protected function handleProductDelete()
    {
        //verify if there is plan products on delete command
        $post = $this->openCart->request->post;
        if (isset($post['selected'])) {
            $recurrencyProductRepo = new RecurrencyProductRepository(new OpencartDatabaseBridge());
            $selected = array_map(function($element){
                return intval($element);
            },$post['selected']);

            $plans = $recurrencyProductRepo->listEntities(0,false);

            $subProductsOfPlans = [];

            foreach ($selected as $productId) {
                /** @var RecurrencyProductRoot $product */
                foreach ($plans as $plan) {
                    if ($plan->getProductId() == $productId) {
                        $plan->setDisabled(true);
                        $recurrencyProductRepo->save($plan);
                        continue;
                    }
                    $subProducts = $plan->getSubProducts();
                    if (!in_array($plan->getProductId(),$selected)) {
                        /** @var RecurrencySubproductValueObject $subProduct */
                        foreach ($subProducts as $subProduct) {
                            if ($subProduct->getProductId() == $productId) {
                                if(!isset($subProductsOfPlans[$productId])) {
                                    $subProductsOfPlans[$productId] = [];
                                }
                                $subProductsOfPlans[$productId][$plan->getProductId()] = true;
                            }
                        }
                    }
                }
            }

            if (count($subProductsOfPlans)) {
                $this->openCart->load->model('catalog/product');

                $cantDeleteData = [];
                foreach ($subProductsOfPlans as $subProductId => $planProducts) {
                    $subProduct = $this->openCart->model_catalog_product->getProduct($subProductId);
                    $cantDeleteData[$subProductId] = [
                        "name" => $subProduct["name"],
                        "plans" => []
                    ];
                    foreach ($planProducts as $planId => $discard) {
                        $plan = $this->openCart->model_catalog_product->getProduct($planId);
                        $cantDeleteData[$subProductId]["plans"][] = $plan["name"];
                    }
                }
                $sessionData = $this->openCart->session->data;
                $sessionData['mundipagg-cant-delete-product-data'] = $cantDeleteData;
                $this->openCart->session->data = $sessionData;

                $this->openCart->response->redirect($this->openCart->url->link('catalog/product', 'user_token=' . $this->openCart->session->data['user_token']));
            }
        }
    }

    protected function handleProductIndexError($errorData)
    {
        $opencartReflection = new \ReflectionClass($this->openCart);
        $errorProperty = $opencartReflection->getProperty('error');
        $errorProperty->setAccessible(true);
        $currentErrors = $errorProperty->getValue($this->openCart);

        $currentErrors = array_merge($currentErrors, $errorData);

        $errorProperty->setAccessible(false);

        $opencartReflection = new \ReflectionClass($this->openCart);
        $registryProperty = $opencartReflection->getProperty('registry');
        $registryProperty->setAccessible(true);
        $registry = $registryProperty->getValue($this->openCart);
        $registryProperty->setAccessible(false);

        $file = DIR_APPLICATION . 'controller/catalog/product.php';
        require_once($file);
        $productController = new \ControllerCatalogProduct($registry);

        $productControllerReflection = new \ReflectionClass($productController);
        $errorProperty = $productControllerReflection->getProperty('error');
        $errorProperty->setAccessible(true);
        $errorProperty->setValue($productController, $currentErrors);
        $errorProperty->setAccessible(false);

        $productController->index();

        return $productController->response->getOutput();
    }

    protected function handleProductIndexList()
    {
        $opencartReflection = new \ReflectionClass($this->openCart);
        $registryProperty = $opencartReflection->getProperty('registry');
        $registryProperty->setAccessible(true);
        $registry = $registryProperty->getValue($this->openCart);
        $registryProperty->setAccessible(false);

        $file = DIR_APPLICATION . 'controller/catalog/product.php';
        require_once($file);
        $productController = new \ControllerCatalogProduct($registry);
        $productControllerReflection = new \ReflectionClass($productController);
        $productController->index();

        $this->openCart->load->model('extension/payment/mundipagg_product');

        $registryProperty = $productControllerReflection->getProperty('registry');
        $registryProperty->setAccessible(true);
        $registry = $registryProperty->getValue($productController);
        $registry->set(
            'model_catalog_product',
            $this->openCart->model_extension_payment_mundipagg_product
        );
        $registryProperty->setValue($productController, $registry);
        $registryProperty->setAccessible(false);

        $getListMethod = new \ReflectionMethod(get_class($productController), 'getList');
        $getListMethod->setAccessible(true);
        $getListMethod->invoke($productController);

        $this->openCart->model_extension_payment_mundipagg_product->getProducts();
    }

    public function productFormEntry($data)
    {
        if (isset($this->openCart->request->get['product_id'])) {
            $productId = intval($this->openCart->request->get['product_id']);

            $planRepo = new RecurrencyProductRepository(new OpencartDatabaseBridge());
            $plans = $planRepo->listEntities(0,false);
            /** @var RecurrencyProductRoot $plan */
            foreach ($plans as $plan) {
                if ($plan->getProductId() == $productId) {
                    $get = $this->openCart->request->get;
                    $get['mundipagg_plan'] = '';
                    $this->openCart->request->get = $get;
                    $data['mpEditPlanId'] = $plan->getId();

                    $session = $this->openCart->session->data;
                    $session['mundipagg-template-snapshot-data'] =
                        base64_encode(json_encode($plan->getTemplate()));

                    $this->openCart->load->model('catalog/product');

                    $subProductsToSession = [
                        'cycles' => [],
                        'cycleType' => [],
                        'id' => [],
                        'name' => [],
                        'quantity' => [],
                        'thumb' => []
                    ];
                    $subProducts = $plan->getSubProducts();
                    /** @var RecurrencySubproductValueObject $subProduct */
                    foreach ($subProducts as $index => $subProduct) {
                        $subProductsToSession['cycles'][$index] = $subProduct->getCycles();
                        $subProductsToSession['cycleType'][$index] = $subProduct->getCycleType();
                        $subProductsToSession['quantity'][$index] = $subProduct->getQuantity();
                        $subProductsToSession['id'][$index] = $subProduct->getProductId();

                        $product = $this->openCart->model_catalog_product->getProduct(
                            $subProduct->getProductId()
                        );
                        $subProductsToSession['name'][$index] = $product['name'];

                        if (is_file(DIR_IMAGE . $product['image'])) {
                            $subProductsToSession['thumb'][$index] =
                                $this->openCart->model_tool_image->resize($product['image'], 40, 40);
                        } else {
                            $subProductsToSession['thumb'][$index] =
                                $this->openCart->model_tool_image->resize('no_image.png', 40, 40);
                        }
                    }

                    if (count($subProductsToSession['id'])) {
                        $session['mundipagg-recurrence-products'] =
                            base64_encode(json_encode($subProductsToSession));
                    }

                    $this->openCart->session->data = $session;

                    break;
                }
            }
        }

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
       $planform['preventFormSubmit'] = true;

        if (isset($this->openCart->session->data['mundipagg-template-snapshot-data'])) {
            $planform['MundipaggTemplateSnapshot'] = $this->openCart->session->data['mundipagg-template-snapshot-data'];
        }
        unset($this->openCart->session->data['mundipagg-template-snapshot-data']);
        if (isset($this->openCart->session->data['mundipagg-recurrence-products'])) {
            $planform['MundipaggRecurrenceProducts'] = $this->openCart->session->data['mundipagg-recurrence-products'];
        }
        unset($this->openCart->session->data['mundipagg-recurrence-products']);

        if (isset($this->openCart->error['mundipagg_recurrency_errors'])) {
            $planform['MundipaggRecurrenceErrors'] = $this->openCart->error['mundipagg_recurrency_errors'];
        }

        $templateRepository = new TemplateRepository(new OpencartDatabaseBridge());
        $plans = $templateRepository->listEntities(0, false);
        $planform['plans'] = array_filter($plans, function($templateRoot){
            return !$templateRoot->getTemplate()->isSingle();
        });

       $productFormTabContentTemplate = $this->openCart->load->view(
           $path . 'plans/productFormTabContent',
           $planform
       );

        $planCreationScript = $this->openCart->load->view(
            $path . 'creationScripts',
            $planform
        );

       $helper = new MundipaggHelperProductPageChanges($this->openCart);
       $data['heading_title'] = 'Plano';
       $data['text_form'] = 'Criar plano';

       $data['tab_design'] = $data['tab_design'] . $productFormTemplate;
       $data['footer'] = $data['footer'] . $productFormTabContentTemplate;
       $data['footer'] .= $planCreationScript;

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
       $planform['productCreationForm'] = true;

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
