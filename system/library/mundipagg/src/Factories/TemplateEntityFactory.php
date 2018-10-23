<?php
namespace Mundipagg\Factories;

use Mundipagg\Aggregates\Template\TemplateEntity;

class TemplateEntityFactory
{

    /**
     * @param $postData
     * @return TemplateEntity
     */
    public function createFromPostData($postData)
    {
        $templateEntity = new TemplateEntity();
        $templateEntity
            ->setName($postData['name'])
            ->setDescription($postData['description'])
        ;

        if (isset($postData['single'])) {
            $templateEntity->setIsSingle($postData['single']);
        }

        if (isset($postData['trial'])) {
            $templateEntity->setTrial(intval($postData['trial']));
        }

        $paymentMethods =
            isset($postData['payment_method']) ? $postData['payment_method'] : [];
        foreach( $paymentMethods as $paymentMethod)
        {
            switch($paymentMethod)
            {
                case 'credit_card':
                    $templateEntity
                        ->setAcceptCreditCard(true)
                        ->setAllowInstallments($postData['allow_installment'])
                        ->addInstallments(explode(",", $postData['installments']));
                    break;
                case 'boleto':
                    $templateEntity->setAcceptBoleto(true);
                    break;
            }
        }

        return $templateEntity;
    }

    public function createFromDBData($dbData)
    {
        $templateEntity = new TemplateEntity();
        $templateEntity
            ->setId($dbData['id'])
            ->setName($dbData['name'])
            ->setDescription($dbData['description'])
            ->setIsSingle($dbData['is_single'])
            ->setAcceptBoleto($dbData['accept_boleto'])
            ->setAcceptCreditCard($dbData['accept_credit_card'])
            ->setAllowInstallments($dbData['allow_installments'])
            ->setTrial($dbData['trial'])
            ->addInstallments(json_decode($dbData['installments'], true))
        ;

        return $templateEntity;
    }

    public function createFromJson($jsonData)
    {
        $data = json_decode(utf8_decode($jsonData));

        if (json_last_error() == JSON_ERROR_NONE) {
            $installments = [];
            if (isset($data->installments)) {
                $installments = json_decode($data->installments);
            }
            if (!is_array($installments)) {
                $installments = explode(",", $data->installments);
            }

            $trial = 0;
            if (!empty($data->trial)) {
                $trial = $data->trial;
            }

            $templateEntity = new TemplateEntity();
            $templateEntity
                ->setName($data->name)
                ->setDescription($data->description)
                ->setIsSingle($data->isSingle)
                ->setAcceptBoleto($data->acceptBoleto)
                ->setAcceptCreditCard($data->acceptCreditCard)
                ->setAllowInstallments($data->allowInstallments)
                ->setTrial($trial)
                ->addInstallments($installments)
            ;

            if (isset($data->id)) {
                $templateEntity->setId($data->id);
            }
            return $templateEntity;
        }
        throw new \Exception('Invalid json data!');
    }
}