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
     * @var \MobileCart\CoreBundle\Service\CartSessionService
     */
    protected $cartSessionService;

    /**
     * @param \MobileCart\CoreBundle\Service\CartSessionService $cartSessionService
     * @return $this
     */
    public function setCartSessionService(\MobileCart\CoreBundle\Service\CartSessionService $cartSessionService)
    {
        $this->cartSessionService = $cartSessionService;
        return $this;
    }

    /**
     * @return \MobileCart\CoreBundle\Service\CartSessionService
     */
    public function getCartSessionService()
    {
        return $this->cartSessionService;
    }

    /**
     * @return \MobileCart\CoreBundle\Service\AbstractEntityService
     */
    public function getEntityService()
    {
        return $this->getCartSessionService()->getDiscountService()->getEntityService();
    }

    /**
     * @return array
     */
    public function getTokenOptions()
    {
        $options = [];
        $customerId = $this->getCartSessionService()->getCustomerId();
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
            'choices' => $this->getTokenOptions(),
            'required' => true,
            'constraints' => [
                new NotBlank(),
            ],
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
