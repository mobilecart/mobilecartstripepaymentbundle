<?php

namespace MobileCart\StripePaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class StripeTokenPaymentType extends AbstractType
{
    protected $tokenOptions = [];

    public function setTokenOptions(array $tokenOptions)
    {
        $this->tokenOptions = $tokenOptions;
    }

    public function getTokenOptions()
    {
        return $this->tokenOptions;
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('token', 'choice', [
            'label' => 'Saved Card',
            'choices' => $this->getTokenOptions(),
            'required' => 1,
            'constraints' => [
                new NotBlank(),
            ],
        ]);
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
