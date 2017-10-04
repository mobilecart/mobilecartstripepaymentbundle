<?php

namespace MobileCart\StripePaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class StripeCcPaymentType
 * @package MobileCart\StripePaymentBundle\Form
 */
class StripeCcPaymentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $months = [
            '1' => '01 - January',
            '2' => '02 - February',
            '3' => '03 - March',
            '4' => '04 - April',
            '5' => '05 - May',
            '6' => '06 - June',
            '7' => '07 - July',
            '8' => '08 - August',
            '9' => '09 - September',
            '10' => '10 - October',
            '11' => '11 - November',
            '12' => '12 - December',
        ];

        $years = [];
        $thisYear = (int) date('Y');
        $maxYear = $thisYear + 10;
        for ($year = $thisYear; $year <= $maxYear; $year++) {
            $years[$year] = $year;
        }

        $builder->add('number', TextType::class, [
                'label' => 'Credit Card Number',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'data-stripe' => 'number'
                ]
            ])
            ->add('expiryMonth', ChoiceType::class, [
                'label' => 'Expiration Month',
                'required' => true,
                'choices' => $months,
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'data-stripe' => 'exp-month',
                ]
            ])
            ->add('expiryYear', ChoiceType::class, [
                'label' => 'Expiration Year',
                'required' => true,
                'choices' => $years,
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'data-stripe' => 'exp-year',
                ]
            ])
            ->add('cvv', TextType::class, [
                'label' => 'CVV',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'data-stripe' => 'cvc',
                ]
            ])
        ;
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
