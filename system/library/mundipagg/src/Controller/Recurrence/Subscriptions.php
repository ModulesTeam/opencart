<?php

namespace Mundipagg\Controller\Recurrence;

class Subscriptions extends Recurrence
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
        $this->data['heading_title'] = $this->language['Subscriptions'];
        $this->render('subscriptions/base');
    }

    protected function edit()
    {
    }

    protected function delete()
    {
    }

    protected function create()
    {
        $this->data['heading_title'] = $this->language['Subscriptions'];
        $this->render('subscriptions/create');
    }
}