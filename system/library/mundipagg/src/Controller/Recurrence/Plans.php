<?php

namespace Mundipagg\Controller\Recurrence;

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
        //extension/payment/mundipagg/plans
        $a =1;
    }
}