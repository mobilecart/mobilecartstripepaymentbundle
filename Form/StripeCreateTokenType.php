<?php

namespace MobileCart\StripePaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class StripeCreateTokenType
 * @package MobileCart\StripePaymentBundle\Form
 */
class StripeCreateTokenType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('token', TextType::class, [
            'label' => 'Token',
            'required' => 1,
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('payment_method', TextType::class)
        ->add('cc_type', TextType::class)
        ->add('cc_last_four', TextType::class)
        ->add('cc_fingerprint', TextType::class)
        ->add('email', TextType::class, [
            'required' => false,
        ])
        ->add('exp_year', TextType::class, [
            'required' => false
        ])
        ->add('exp_month', TextType::class, [
            'required' => false
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
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
