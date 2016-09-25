<?php

namespace MobileCart\StripePaymentBundle\EventListener\Customer;

use Symfony\Component\EventDispatcher\Event;

class CustomerAddCardViewReturn
{
    protected $entityService;

    protected $stripePaymentMethodService;

    protected $themeService;

    protected $event;

    protected function setEvent($event)
    {
        $this->event = $event;
        return $this;
    }

    protected function getEvent()
    {
        return $this->event;
    }

    protected function getReturnData()
    {
        return $this->getEvent()->getReturnData()
            ? $this->getEvent()->getReturnData()
            : [];
    }

    public function setEntityService($entityService)
    {
        $this->entityService = $entityService;
        return $this;
    }

    public function getEntityService()
    {
        return $this->entityService;
    }

    public function setStripePaymentMethodService($stripePaymentMethodService)
    {
        $this->stripePaymentMethodService = $stripePaymentMethodService;
        return $this;
    }

    public function getStripePaymentMethodService()
    {
        return $this->stripePaymentMethodService;
    }

    public function setThemeService($themeService)
    {
        $this->themeService = $themeService;
        return $this;
    }

    public function getThemeService()
    {
        return $this->themeService;
    }

    public function onCustomerAddCardViewReturn(Event $event)
    {
        $this->setEvent($event);
        $returnData = $this->getReturnData();

        // gather data
        $theme = $event->getTheme()
            ? $event->getTheme()
            : 'stripe_frontend';

        $template = $event->getTemplate()
            ? $event->getTemplate()
            : 'Customer:add_card.html.twig';

        // render template
        $response = $this->getThemeService()
            ->render($theme, $template, $returnData);

        $event->setResponse($response);
    }
}
