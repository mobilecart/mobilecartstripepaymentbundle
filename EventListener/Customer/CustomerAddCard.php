<?php

namespace MobileCart\StripePaymentBundle\EventListener\Customer;

use Symfony\Component\EventDispatcher\Event;

class CustomerAddCard
{
    protected $entityService;

    protected $stripePaymentMethodService;

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

    public function onCustomerAddCard(Event $event)
    {
        $this->setEvent($event);
        $returnData = $this->getReturnData();
        $customer = $event->getEntity();
        $customerTokens = $customer->getTokens();
        $request = $event->getRequest();
        $currentToken = null;

        // get card token from request

        if ($customerTokens) {

            foreach($customerTokens as $token) {
                if (substr($token->getServiceAccountId(), 0, 4) == 'cus_') {
                    $currentToken = $token;
                    break;
                }
            }

            if ($request->get('replace_card', '')) {

                // update current customer token
                $currentToken->setToken($request->get('token'))
                    ->setCcType($request->get('cc_type'))
                    ->setCcLastFour($request->get('cc_last_four'))
                    ->setCreatedAt(new \DateTime('now'));

                $this->getEntityService()->persist($currentToken);

                // add card to stripe customer
                $this->getStripePaymentMethodService()
                    ->setPaymentCustomerToken($currentToken)
                    ->createCard();


                if ($this->getStripePaymentMethodService()->getIsCardCreated()) {
                    $event->setSuccess(1);

                    // todo: update stripe customer , set default card on customer
                    $this->getStripePaymentMethodService()
                        ->updateCustomer([
                            'customerReference' => $currentToken->getServiceAccountId(),
                            'default_source' => $request->get('card_id'),
                        ]);
                }

            } else {


            }
        } else {
            // todo : create new stripe customer

            // create new customer token

            // if successful
                // $event->setSuccess(1);

        }

        $event->setReturnData($returnData);
    }
}
