<?php

namespace MobileCart\StripePaymentBundle\EventListener\Checkout;

use Symfony\Component\EventDispatcher\Event;
use MobileCart\CoreBundle\Payment\PaymentMethodServiceInterface;

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

        switch($this->getPaymentMethodService()->getAction()) {
            case PaymentMethodServiceInterface::ACTION_PURCHASE_STORED_TOKEN:

                $returnData['javascripts'][] = [
                    'js_template' => 'MobileCartStripePaymentBundle:Checkout:token_payment_js.html.twig',
                    'data' => [
                        'code' => $this->getPaymentMethodService()->getCode(),
                    ],
                ];

                break;
            default:

                $returnData['javascripts'][] = [
                    'js_template' => 'MobileCartStripePaymentBundle:Checkout:create_token_js.html.twig',
                    'data' => [
                        'code' => $this->getPaymentMethodService()->getCode(),
                        'public_key' => $this->getPaymentMethodService()->getPublicKey(),
                    ],
                ];

                break;
        }



        $event->setReturnData($returnData);
    }
}
