<?php

namespace Mundipagg\Controller\Recurrence;

use Mundipagg\Aggregates\Template\DueValueObject;
use Mundipagg\Aggregates\Template\RepetitionValueObject;
use Mundipagg\Factories\TemplateRootFactory;
use Mundipagg\Repositories\Bridges\OpencartDatabaseBridge;
use Mundipagg\Repositories\TemplateRepository;

class Templates extends Recurrence
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
        $templateRepository = new TemplateRepository(new OpencartDatabaseBridge());

        $templateRoots = $templateRepository->listEntities(0, false);
        $this->data['templateRoots'] = $templateRoots;

        $this->data['heading_title'] = $this->language['Templates'];
        $baseLink = 'index.php?route=extension/payment/mundipagg/templates&user_token=' .
            $this->openCart->request->get['user_token'];
        $this->data['createLink'] = $baseLink . '&action=create';
        $this->data['updateLink'] = $baseLink . '&action=update';
        $this->data['deleteLink'] = $baseLink . '&action=delete';

        $this->render('templates/base');
    }


    protected function setBaseCreationFormData()
    {
        $this->data['heading_title'] = $this->language['Templates'];

        $this->data['formAction'] = 'index.php?route=extension/payment/mundipagg/templates&user_token=' .
            $this->openCart->request->get['user_token'] .
            '&action=create';

        $this->data['formPlan'] = 'extension/payment/mundipagg/recurrence/templates/form_plan.twig';
        $this->data['panelPlanFrequency'] = 'extension/payment/mundipagg/recurrence/templates/panelPlanFrequency.twig';

        $this->data['formSingle'] = 'extension/payment/mundipagg/recurrence/templates/form_single.twig';
        $this->data['panelSingleFrequency'] = 'extension/payment/mundipagg/recurrence/templates/panelSingleFrequency.twig';

        $path = 'extension/payment/mundipagg/';
        $this->data['formBase'] = $path . 'recurrence/templates/form_base.twig';

        $this->data['dueTypesArray'] = DueValueObject::getTypesArray();
        $this->data['discountTypesArray'] = RepetitionValueObject::getDiscountTypesArray();
        $this->data['intervalTypesArray'] = RepetitionValueObject::getIntervalTypesArray();

        $this->data['saveAction'] = $this->openCart->url->link(
            'extension/payment/mundipagg/templates',
            [
                'user_token' => $this->openCart->session->data['user_token'],
                'action' => 'save'
            ],
            true
        );
    }

    protected function update()
    {
        $getData = $this->openCart->request->get;
        if (!isset($getData['templateId'])) {
            return $this->create();
        }

        $templateRepository = new TemplateRepository(new OpencartDatabaseBridge());
        $templateRoot = $templateRepository->find($getData['templateId']);
        if ($templateRoot === null) {
            return $this->create();
        }

        $this->setBaseCreationFormData();
        $this->data['selectedTemplateRoot'] = $templateRoot;

        $this->render('templates/create');
    }

    protected function delete()
    {
        $getData = $this->openCart->request->get;
        if (isset($getData['templateId'])) {
            $templateRepository = new TemplateRepository(new OpencartDatabaseBridge());
            $templateRoot = $templateRepository->find($getData['templateId']);
            if ($templateRoot !== null) {
                $templateRepository->delete($templateRoot);
            }
        }

        $this->redirect($this->openCart->url->link('extension/payment/mundipagg/templates',''));
    }

    protected function create()
    {
        $this->setBaseCreationFormData();
        $this->render('templates/create');
    }

    /**
     * @throws \Exception
     */
    protected function save()
    {
        $postData = $this->openCart->request->post;

        $templateRootFactory = new TemplateRootFactory();
        try {
            $templateRoot = $templateRootFactory->createFromPostData($postData);

            $templateRepository = new TemplateRepository(new OpencartDatabaseBridge());

            if (isset($postData['template-id'])) {
                $templateRoot->getTemplate()->setId($postData['template-id']);
            }

            $templateRepository->save($templateRoot);
        }catch(Exception $e) {
            throw $e;
        }

        $this->redirect($this->openCart->url->link('extension/payment/mundipagg/templates',''));
    }

    protected function info()
    {
        header('Content-Type:application/json');
        $getData = $this->openCart->request->get;
        $templateId = isset($getData['template_id']) ? $getData['template_id'] : null;
        if ($templateId) {
            $templateId = filter_var($templateId, FILTER_SANITIZE_NUMBER_INT);
            $templateRepository = new TemplateRepository(new OpencartDatabaseBridge());
            $template = $templateRepository->find($templateId);
            if($template) {
                $templateJson = json_encode($template);
                http_response_code(200);
                echo $templateJson;
                die;
            }
        }
        http_response_code(404);
        echo '{"error":"Resource not found."}';
        die;
    }
}
