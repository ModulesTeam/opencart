<?php

namespace Mundipagg\Controller\Recurrence;

class Single extends Recurrence
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
            '&mundipagg_single';

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

    protected function validateConfig()
    {
        $errors = [];
        if (!isset($this->openCart->request->post['mundipagg-template-snapshot-data'])) {
            $errors['recurrency_plan_template_error'] = 'A plan configuration must be added.';
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
