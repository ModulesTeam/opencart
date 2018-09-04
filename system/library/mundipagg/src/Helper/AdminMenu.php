<?php
namespace Mundipagg\Helper;

use Mundipagg\Settings\Recurrence as RecurrenceSettings;

class AdminMenu
{
    private $openCart;

    public function __construct($openCart)
    {
        $this->openCart = $openCart;
        $this->chargeRecurrenceSettings();
    }

    protected function chargeRecurrenceSettings()
    {
        $this->recurrenceSettings = new RecurrenceSettings($this->openCart);
    }

    protected function isSomeRecurrenceEnable()
    {
        return $this->recurrenceSettings->isSingleRecurrenceEnable() ||
            $this->recurrenceSettings->isSubscriptionByPlanEnable();
    }

    public function getMenu()
    {
        $htmlLogo =
            $this
                ->openCart
                ->load
                ->view('extension/payment/mundipagg/menu/mundipagg');

        $children[] = $this->getMenuChildren('Settings');
        if ($this->isSomeRecurrenceEnable()) {
            $children[] = $this->addRecurrenceMenu();
        }

        $mundipaggMenu = [
            'id'       => 'menu-mundipagg',
            'name'	   => $htmlLogo,
            'children' => $children
        ];

        return $mundipaggMenu;
    }

    private function addRecurrenceMenu()
    {
        $result = [
            'name' => $this->openCart->language->get('Recurrence'),
            'children' => [
                $this->getMenuChildren('Templates'),
                //$this->getMenuChildren('Subscriptions')
            ]
        ];

        if ($this->recurrenceSettings->isSingleRecurrenceEnable()) {
            $result['children'][] = $this->getMenuChildren('Single');
        }

        if ($this->recurrenceSettings->isSubscriptionByPlanEnable()) {
            $result['children'][] = $this->getMenuChildren('Plans');
        }

        return $result;
    }

    private function getMenuChildren($name)
    {
        $path = 'extension/payment/mundipagg/' . strtolower($name);
        $this->openCart->load->language('extension/payment/mundipagg');

        return [
            'name'  => $this->openCart->language->get($name),
            'href'  => $this->getLink($path)
        ];
    }

    private function getLink($path)
    {
        return $this->openCart->url->link(
            $path,
            'user_token=' . $this->openCart->session->data['user_token'],
            true
        );
    }
}
