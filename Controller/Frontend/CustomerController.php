<?php

namespace MobileCart\StripePaymentBundle\Controller\Frontend;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use MobileCart\CoreBundle\Event\CoreEvent;
use MobileCart\StripePaymentBundle\Event\StripeEvents;

class CustomerController extends Controller
{

    /**
     * @Route("/customer/stripe/card/add", name="cart_customer_add_card_view_return")
     * @Method("GET")
     */
    public function addCardAction(Request $request)
    {
        $stripe = $this->get('cart.payment.method.stripe');

        $returnData = [
            'code' => $stripe->getCode(),
            'public_key' => $stripe->getPublicKey(),
        ];

        // get form
        $returnData['form'] = $this->get('cart.payment.method.stripe')
            ->buildForm()
            ->getForm()
            ->createView();

        // render template
        $event = new CoreEvent();
        $event->setReturnData($returnData)
            ->setRequest($request);

        $this->get('event_dispatcher')
            ->dispatch(StripeEvents::CUSTOMER_ADD_CARD_VIEW_RETURN, $event);

        return $event->getResponse();
    }

    /**
     * @Route("/customer/stripe/card/add", name="cart_customer_add_card_post")
     * @Method("POST")
     */
    public function addCardPostAction(Request $request)
    {
        $user = $this->getUser();
        $returnData = [];
        // handle api request/response
        $event = new CoreEvent();
        $event->setReturnData($returnData)
            ->setRequest($request)
            ->setEntity($user);

        $this->get('event_dispatcher')
            ->dispatch(StripeEvents::CUSTOMER_ADD_CARD, $event);

        // handle response : json, html
        switch($request->get('format', '')) {
            case 'json':
                return new JsonResponse([
                    'success' => (int) $event->getSuccess(),
                ]);
                break;
            default:
                return new RedirectResponse($this->generateUrl('cart_customer_add_card_view_return', [
                    'success' => (int) $event->getSuccess(),
                ]));
                break;
        }
    }
}
