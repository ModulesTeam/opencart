<?php

namespace Mundipagg\Controller\Recurrence;

use Mundipagg\Settings\Recurrence as RecurrenceSettings;

use Action;
use Mundipagg\Aggregates\RecurrencyProduct\RecurrencyProductRoot;
use Mundipagg\Factories\RecurrencyProductRootFactory;
use Mundipagg\Factories\RecurrencySubproductValueObjectFactory;
use Mundipagg\Factories\TemplateRootFactory;
use Mundipagg\Repositories\Bridges\OpencartDatabaseBridge;
use Mundipagg\Repositories\RecurrencyProductRepository;

class Recurrence
{
    public $data;
    public $openCart;
    public $language;
    public $templateDir = 'extension/payment/mundipagg/recurrence/';
    protected $recurrenceSettings;

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
        $lang = $this->openCart->load->language('extension/payment/mundipagg');
        $this->language = $lang['recurrence'];

        $this->chargeRecurrenceSettings();
        $this->setLayoutComponents();
    }

    /**
     * Sets opencart dashboard layout components
     *
     * It puts opencart header, left column and footer
     *
     * @return void
     */
    protected function setLayoutComponents()
    {
        $this->data['header'] =
            $this->openCart->load->controller('common/header');
        $this->data['column_left'] =
            $this->openCart->load->controller('common/column_left');
        $this->data['footer'] = $this->openCart->load->controller('common/footer');
    }

    public function render($path)
    {
        $this->openCart->response->setOutput(
            $this->openCart->load->view(
                $this->templateDir . $path,
                $this->data
            )
        );
    }

    protected function chargeRecurrenceSettings()
    {
        $this->recurrenceSettings = new RecurrenceSettings($this->openCart);
        $this->data['recurrenceSettings'] = $this->recurrenceSettings->getAllSettings();
    }

    public function save($isSingle = false)
    {
        $planValidation = $this->validateConfig();
        $formValidation = $this->validateForm();
        
        if (!$planValidation || !$formValidation) {
            return $this->handleValidationError();
        }

        if (isset($this->openCart->request->get['is_single'])) {
            $isSingle = true;
        }

        if (($this->openCart->request->server['REQUEST_METHOD'] == 'POST')) {
            $templateSnapshotData = $this->openCart->request->post['mundipagg-template-snapshot-data'];
            $templateSnapshotData = base64_decode($templateSnapshotData);

            //creating a templateRoot from json_data just to validate the input.
            $templateRootFactory = new TemplateRootFactory();
            $templateRoot = $templateRootFactory->createFromJson($templateSnapshotData);

            $recurrencyProductFactory = new RecurrencyProductRootFactory();
            $recurrencySubproductValueObjectFactory = new RecurrencySubproductValueObjectFactory();

            $subProducts = $this->createSubProducts($recurrencySubproductValueObjectFactory);

            //creating plan product
            $recurrencyProduct = $recurrencyProductFactory->createFromJson(json_encode([
                "productId" => null,
                "template" => $templateRoot,
                "isSingle" => $isSingle,
                'subProducts' => $subProducts
            ]));

            $this->openCart->load->model('catalog/product');
            $recurrencyProductRepo = new RecurrencyProductRepository(new OpencartDatabaseBridge());

            $isEdit = false;
            //check if is edit
            if (isset($this->openCart->request->get['product_id'])) {
                $productId = intval($this->openCart->request->get['product_id']);
                $plans = $recurrencyProductRepo->listEntities(0, false);
                /** @var RecurrencyProductRoot $plan */
                foreach ($plans as $plan) {
                    if ($plan->getProductId() == $productId) {
                        $isEdit = true;
                        $planId = $plan->getId();
                        $mundipaggPlanId = $plan->getMundipaggPlanId();
                        $opencartProductId = $plan->getProductId();
                        break;
                    }
                }
            }

            //@todo start database transaction
            if ($isEdit) {
                //edit base product on opencart
                $this->openCart->model_catalog_product->editProduct(
                    $this->openCart->request->get['product_id'],
                    $this->openCart->request->post
                );
                $recurrencyProduct->setId($planId);
            } else {
                //save base product on opencart.
                $opencartProductId = $this->openCart->model_catalog_product
                    ->addProduct($this->openCart->request->post);
                //@todo: create plan on mundipagg
                $mundipaggPlanId = 'plan_xxxxxxxxxxxxxxxx'; //@todo this is a placeholder.
            }

            $recurrencyProduct->setProductId($opencartProductId);
            $recurrencyProduct->setMundipaggPlanId($mundipaggPlanId);

            //save plan product
            $recurrencyProductRepo->save($recurrencyProduct);

            //@todo: commit database transaction only if mundipagg plan creation was successful.

            //redirect to success.
            $this->openCart->response->redirect(
                $this->openCart->url->link(
                    'catalog/product',
                    'user_token=' . $this->openCart->session->data['user_token'])
            );
        }
    }

    protected function handleValidationError()
    {
        if (isset($this->openCart->request->post['mundipagg-template-snapshot-data'])) {
            $this->openCart->session->data['mundipagg-template-snapshot-data'] =
                $this->openCart->request->post['mundipagg-template-snapshot-data'];
        }
        if (isset($this->openCart->request->post['mundipagg-recurrence-products'])) {
            $this->openCart->session->data['mundipagg-recurrence-products'] =
                base64_encode(json_encode($this->openCart->request->post['mundipagg-recurrence-products']));;
        }

        $route = 'catalog/product/add';

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
        $errorProperty->setValue($productController, $this->openCart->error);
        $errorProperty->setAccessible(false);

        $output = $productController->add();

        // Trigger the post events
        $result = $this->openCart->event->trigger('controller/' . $route . '/after', array(&$route, &$output));

        if (!is_null($result)) {
            return $result;
        }

        return;
    }

    /**
     * Validate product using Opencart validation
     * @return mixed
     * @throws \ReflectionException
     */
    protected function validateForm()
    {
        //instantiate opencart controller
        $productController = $this->getOpencartProductController();
        //change validateForm visibility

        $validateFormMethod = new \ReflectionMethod($productController, 'validateForm');
        $validateFormMethod->setAccessible(true);

        $validationReturn = $validateFormMethod->invoke($productController);

        $productControllerReflection = new \ReflectionClass($productController);
        $registryProperty = $productControllerReflection->getProperty('registry');
        $registryProperty->setAccessible(true);
        $controllerRegistry = $registryProperty->getValue($productController);
        $registryProperty->setAccessible(false);

        $opencartReflection = new \ReflectionClass($this->openCart);
        $registryProperty = $opencartReflection->getProperty('registry');
        $registryProperty->setAccessible(true);
        $registryProperty->setValue($this->openCart, $controllerRegistry);
        $registryProperty->setAccessible(false);

        return $validationReturn;
    }

    protected function getOpencartProductController()
    {
        $opencartReflection = new \ReflectionClass($this->openCart);
        $registryProperty = $opencartReflection->getProperty('registry');
        $registryProperty->setAccessible(true);
        $registry = $registryProperty->getValue($this->openCart);
        $registryProperty->setAccessible(false);

        $file = DIR_APPLICATION . 'controller/catalog/product.php';
        require_once($file);
        return new \ControllerCatalogProduct($registry);
    }

    protected function createSubProducts($recurrencySubproductValueObjectFactory)
    {
        $mundipaggRecurrencyProducts =
            $this->openCart->request->post['mundipagg-recurrence-products'];

        $subProducts = [];

        foreach ($mundipaggRecurrencyProducts['cycles'] as $index => $cycles) {
            $subProducts[] =
                $recurrencySubproductValueObjectFactory->createFromJson(
                    json_encode([
                        'productId' => $mundipaggRecurrencyProducts['id'][$index],
                        'cycles' => $cycles,
                        'cycleType' => $mundipaggRecurrencyProducts['cycleType'][$index],
                        'quantity' => $mundipaggRecurrencyProducts['quantity'][$index],
                    ])
                );
        }

        return $subProducts;
    }
}