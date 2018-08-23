<?php

namespace Mundipagg\Controller\Recurrence;

use Action;
use Mundipagg\Aggregates\RecurrencyProduct\RecurrencyProductRoot;
use Mundipagg\Factories\RecurrencyProductRootFactory;
use Mundipagg\Factories\RecurrencySubproductValueObjectFactory;
use Mundipagg\Factories\TemplateRootFactory;
use Mundipagg\Repositories\Bridges\OpencartDatabaseBridge;
use Mundipagg\Repositories\RecurrencyProductRepository;

class Plans extends Recurrence
{
    public function __call($name, array $arguments)
    {
        if (method_exists($this, $name)) {
            return call_user_func_array([$this, $name], $arguments);
        }

        return $this->index();
    }

    public function index()
    {
        $this->data['heading_title'] = $this->language['Plans'];
        $this->data['addLink'] =
            'index.php?route=catalog/product/edit&user_token=' .
            $this->openCart->request->get['user_token'] .
            '&mundipagg_plan';

        $this->data['actionsTemplate'] =
            $this->openCart->load->view(
                $this->templateDir . 'actions', $this->data
            );
        $this->data['breadCrumbTemplate'] =
            $this->openCart->load->view(
                $this->templateDir . 'breadcrumb', $this->data
            );

        $this->data['panelIconsTemplate'] =
            $this->openCart->load->view($this->templateDir . 'panelIcons');
        $this->data['content'] =
            $this->openCart->load->view($this->templateDir . 'plans/grid') .
            $this->openCart->load->view($this->templateDir . 'plans/list');

        $this->render('plans/base');
    }

    public function save()
    {
        $planValidation = $this->validatePlanConfig();
        $formValidation = $this->validateForm();
        if (
            !$planValidation ||
            !$formValidation
        ) {
           return $this->handleValidationError();
        }

        if (($this->openCart->request->server['REQUEST_METHOD'] == 'POST')) {
            $templateSnapshotData = $this->openCart->request->post['mundipagg-template-snapshot-data'];
            $templateSnapshotData = base64_decode($templateSnapshotData);

            //creating a templateRoot from json_data just to validate the input.
            $templateRootFactory = new TemplateRootFactory();
            $templateRoot = $templateRootFactory->createFromJson($templateSnapshotData);

            $recurrencyProductFactory = new RecurrencyProductRootFactory();
            $recurrencySubproductValueObjectFactory = new RecurrencySubproductValueObjectFactory();

            //creating subproducts
            $mundipaggRecurrencyProducts =
                $this->openCart->request->post['mundipagg-recurrence-products'];
            $subProducts = [];
            foreach ($mundipaggRecurrencyProducts['cycles'] as $index => $cycles) {
                $subProducts[] = $recurrencySubproductValueObjectFactory->createFromJson(json_encode([
                    'productId' => $mundipaggRecurrencyProducts['id'][$index],
                    'cycles' => $cycles,
                    'cycleType' => $mundipaggRecurrencyProducts['cycleType'][$index],
                    'quantity' => $mundipaggRecurrencyProducts['quantity'][$index],
                ]));
            }

            //creating plan product
            $recurrencyProduct = $recurrencyProductFactory->createFromJson(json_encode([
                "productId" => null,
                "template" => $templateRoot,
                "isSingle" => false,
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
            }
            else {
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

    public function productSearch()
    {
        $term = $this->openCart->request->get['term'];

        $this->openCart->load->model('tool/image');
        $this->openCart->load->model('catalog/product');

        $products = $this->openCart->model_catalog_product->getProducts([
            'filter_name' => $term
        ]);

        //filtering plans
        $plans = $this->openCart->db->query("
            SELECT product_id FROM `" . DB_PREFIX . "mundipagg_recurrency_product`;
        ");

        $planIds = [];
        foreach ($plans->rows as $row) {
            $planIds[] = $row["product_id"];
        }

        header('Content-Type: application/json');
        header("HTTP/1.1 200 OK");
        http_response_code(200);
        $result = [];
        foreach ($products as $product) {
            if (in_array($product['product_id'],$planIds)) {
                continue;
            }
            $data = new \stdClass();
            $data->label = $product['name'];
            $data->value = $product['product_id'];
            if (is_file(DIR_IMAGE . $product['image'])) {
                $data->thumb  = $this->openCart->model_tool_image->resize($product['image'], 40, 40);
            } else {
                $data->thumb  = $this->openCart->model_tool_image->resize('no_image.png', 40, 40);
            }
            $result[] = $data;
        }
        echo json_encode($result);
        die;
    }

    protected function validatePlanConfig()
    {
        $errors = [];
        if (!isset($this->openCart->request->post['mundipagg-template-snapshot-data'])) {
            $errors['recurrency_plan_template_error'] = 'A plan configuration must be added.';
        }
        if (!isset($this->openCart->request->post['mundipagg-recurrence-products'])) {
            $errors['recurrency_plan_product_error'] = 'At least one product must be added to a plan';
        }

        if (count($errors)) {
            $currentErrors = $this->openCart->error;
            $currentErrors['mundipagg_recurrency_errors'] = $errors;
            $this->openCart->error = $currentErrors;
            return false;
        };
        return true;
    }

    protected function validateForm() {
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
        $registryProperty->setValue($this->openCart,$controllerRegistry);
        $registryProperty->setAccessible(false);

        return $validationReturn;
    }

    protected function handleValidationError()
    {
        if (isset($this->openCart->request->post['mundipagg-template-snapshot-data'])) {
            $this->openCart->session->data['mundipagg-template-snapshot-data'] =
                $this->openCart->request->post['mundipagg-template-snapshot-data']
            ;
        }
        if (isset($this->openCart->request->post['mundipagg-recurrence-products'])) {
            $this->openCart->session->data['mundipagg-recurrence-products'] =
                base64_encode(json_encode($this->openCart->request->post['mundipagg-recurrence-products']));
            ;
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
        $errorProperty->setValue($productController,$this->openCart->error);
        $errorProperty->setAccessible(false);

        $output = $productController->add();

        // Trigger the post events
        $result = $this->openCart->event->trigger('controller/' . $route . '/after', array(&$route, &$output));

        if (!is_null($result)) {
            return $result;
        }

        return;
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
}