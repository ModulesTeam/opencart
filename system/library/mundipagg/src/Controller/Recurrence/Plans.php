<?php

namespace Mundipagg\Controller\Recurrence;

use Mundipagg\Factories\TemplateRootFactory;

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
            $data->price = number_format($product['price'], 2, '.', '');
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

    protected function validateConfig()
    {
        $errors = [];
        $errors['recurrency_plan_template_error'] = 'A plan configuration must be added.';

        if (isset($this->openCart->request->post['mundipagg-template-snapshot-data'])) {
            unset($errors['recurrency_plan_template_error']);

            $templateSnapshotData = $this->openCart->request->post['mundipagg-template-snapshot-data'];
            $templateSnapshotData = base64_decode($templateSnapshotData);

            try {
                //creating a templateRoot from json_data just to validate the input.
                (new TemplateRootFactory)->createFromJson($templateSnapshotData);
            } catch (\Exception $exception) {
                $errors['recurrency_plan_input_error'] = $exception->getMessage();
            }
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
}