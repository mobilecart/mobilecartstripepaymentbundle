<?php

namespace MobileCart\StripePaymentBundle\EventListener\Checkout;

use Symfony\Component\EventDispatcher\Event;

class StripeJavascript
{
    protected $stripePaymentService;

    protected $publicKey = '';

    protected $event;

    public function setPaymentMethodService($paymentService)
    {
        $this->stripePaymentService = $paymentService;
        return $this;
    }

    public function getPaymentMethodService()
    {
        return $this->stripePaymentService;
    }

    public function setPublicKey($publicKey)
    {
        $this->publicKey = $publicKey;
        return $this;
    }

    public function getPublicKey()
    {
        return $this->publicKey;
    }

    protected function setEvent($event)
    {
        $this->event = $event;
        return $this;
    }

    protected function getEvent()
    {
        return $this->event;
    }

    public function getReturnData()
    {
        return $this->getEvent()->getReturnData()
            ? $this->getEvent()->getReturnData()
            : [];
    }

    public function onCheckoutViewReturn(Event $event)
    {
        $this->setEvent($event);
        $returnData = $this->getReturnData();

        if (!isset($returnData['javascripts'])) {
            $returnData['javascripts'] = [];
        }

        // todo : figure out if customer already has a customer token

        $returnData['javascripts'][] = [
            'js_template' => 'MobileCartStripePaymentBundle:Checkout:create_token_js.html.twig',
            'data' => [
                'code' => $this->getPaymentMethodService()->getCode(),
                'public_key' => $this->getPaymentMethodService()->getPublicKey(),
            ],
        ];

        $event->setReturnData($returnData);
    }
}
