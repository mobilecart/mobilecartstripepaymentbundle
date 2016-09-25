<?php

namespace MobileCart\StripePaymentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MobileCartStripePaymentBundle extends Bundle
{
    public function boot()
    {
        $this->container->get('cart.theme.config')
            ->setTheme(
                'stripe_frontend',
                'MobileCartFrontendBundle::Frontend/frontend-layout.html.twig',
                'MobileCartStripePaymentBundle:',
                'bundles/mobilecartfrontend'
            );

    }
}
