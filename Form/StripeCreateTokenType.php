<?php

namespace MobileCart\StripePaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class StripeCreateTokenType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('token', 'text', [
            'label' => 'Token',
            'required' => 1,
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('payment_method', 'text')
        ->add('cc_type', 'text')
        ->add('cc_last_four', 'text')
        ->add('cc_fingerprint', 'text')
        ->add('email', 'text', [
            'required' => 0,
        ])
        ->add('exp_year', 'text', [
            'required' => 0
        ])
        ->add('exp_month', 'text', [
            'required' => 0
        ])
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'stripe';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
