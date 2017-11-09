<?php

namespace MobileCart\StripePaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use MobileCart\CoreBundle\Constants\EntityConstants;

/**
 * Class StripeTokenPaymentType
 * @package MobileCart\StripePaymentBundle\Form
 */
class StripeTokenPaymentType extends AbstractType
{
    /**
     * @var \MobileCart\CoreBundle\Service\CartService
     */
    protected $cartService;

    /**
     * @param \MobileCart\CoreBundle\Service\CartService $cartService
     * @return $this
     */
    public function setCartService(\MobileCart\CoreBundle\Service\CartService $cartService)
    {
        $this->cartService = $cartService;
        return $this;
    }

    /**
     * @return \MobileCart\CoreBundle\Service\CartService
     */
    public function getCartService()
    {
        return $this->cartService;
    }

    /**
     * @return \MobileCart\CoreBundle\Service\AbstractEntityService
     */
    public function getEntityService()
    {
        return $this->getCartService()->getDiscountService()->getEntityService();
    }

    /**
     * @return array
     */
    public function getTokenOptions()
    {
        $options = [];
        $customerId = $this->getCartService()->getCustomerId();
        if ($customerId) {

            $customerTokens = $this->getEntityService()->findBy(EntityConstants::CUSTOMER_TOKEN, [
                'customer' => $customerId,
            ]);

            if ($customerTokens) {
                foreach($customerTokens as $token) {
                    $options[$token->getToken()] = "{$token->getCcType()} : xxxx-{$token->getCcLastFour()}";
                }
            }
        }
        return $options;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('token', ChoiceType::class, [
            'label' => 'Saved Card',
            'choices' => array_flip($this->getTokenOptions()),
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
            'choices_as_values' => true,
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
