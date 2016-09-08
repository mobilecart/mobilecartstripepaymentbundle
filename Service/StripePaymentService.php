<?php

namespace MobileCart\StripePaymentBundle\Service;

use Omnipay\Stripe\Gateway; // composer package : omnipay/stripe
use MobileCart\CoreBundle\Payment\PaymentMethodServiceInterface;
use MobileCart\CoreBundle\Payment\TokenPaymentMethodServiceInterface;
use MobileCart\StripePaymentBundle\Form\StripeCcPaymentType;
use MobileCart\StripePaymentBundle\Form\StripeCreateTokenType;
use MobileCart\StripePaymentBundle\Form\StripeTokenPaymentType;

class StripePaymentService
    implements TokenPaymentMethodServiceInterface
{
    protected $formFactory;

    protected $entityService;

    protected $form;

    protected $action;

    protected $defaultAction = self::ACTION_PURCHASE;

    protected $code = 'stripe';

    protected $label = 'Stripe';

    protected $isTestMode = false;

    protected $isRefund = false;

    protected $isSubmission = false;

    /**
     * All customer tokens for the relevant customer
     *
     * @var array
     */
    protected $customerTokens = [];

    /**
     * The token being used for the relevant customer
     *
     * @var string
     */
    protected $paymentCustomerToken;

    protected $subscriptionCustomer;

    protected $paymentData = [];

    protected $orderData = [];

    protected $orderPaymentData = [];

    protected $isAuthorized = false;

    protected $isCaptured = false;

    protected $isPurchased = false;

    protected $isTokenCreated = false;

    protected $isPurchasedStoredToken = false;

    protected $isPurchasedAndSubscribedRecurring = false;

    protected $isCanceledRecurring = false;

    protected $purchaseRequest;

    protected $purchaseResponse;

    protected $authorizeRequest;

    protected $authorizeResponse;

    protected $captureRequest;

    protected $captureResponse;

    protected $tokenCreateRequest;

    protected $tokenCreateResponse;

    protected $tokenPaymentRequest;

    protected $tokenPaymentResponse;

    protected $subscribeRecurringRequest;

    protected $subscribeRecurringResponse;

    protected $cancelRecurringRequest;

    protected $cancelRecurringResponse;

    protected $confirmation = '';

    protected $ccFingerprint = '';

    protected $ccLastFour = '';

    protected $ccType = '';

    protected $testPublicKey = '';

    protected $testPrivateKey = '';

    protected $livePublicKey = '';

    protected $livePrivateKey = '';

    /**
     * @param $formFactory
     * @return $this
     */
    public function setFormFactory($formFactory)
    {
        $this->formFactory = $formFactory;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFormFactory()
    {
        return $this->formFactory;
    }

    /**
     * @param $entityService
     * @return $this
     */
    public function setEntityService($entityService)
    {
        $this->entityService = $entityService;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntityService()
    {
        return $this->entityService;
    }

    /**
     * @param array $customerTokens
     * @return $this
     */
    public function setCustomerTokens(array $customerTokens)
    {
        $this->customerTokens = $customerTokens;
        return $this;
    }

    /**
     * @return array
     */
    public function getCustomerTokens()
    {
        return $this->customerTokens;
    }

    /**
     * @param $token
     * @return $this
     */
    public function setPaymentCustomerToken($token)
    {
        $this->paymentCustomerToken = $token;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentCustomerToken()
    {
        return $this->paymentCustomerToken;
    }

    /**
     * @param $subCustomer
     * @return $this|mixed
     */
    public function setSubscriptionCustomer($subCustomer)
    {
        $this->subscriptionCustomer = $subCustomer;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubscriptionCustomer()
    {
        return $this->subscriptionCustomer;
    }

    /**
     * @param $action
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setDefaultAction($action)
    {
        if (!$this->supportsAction($action)) {
            throw new \InvalidArgumentException("Un-Supported Payment Action specified");
        }

        $this->defaultAction = $action;
        return $this;
    }

    /**
     * @return int
     */
    public function getDefaultAction()
    {
        return $this->defaultAction;
    }

    /**
     * @return array
     */
    public function supportsActions()
    {
        return [
            self::ACTION_AUTHORIZE,
            self::ACTION_CAPTURE,
            self::ACTION_PURCHASE,
            self::ACTION_CREATE_TOKEN,
            self::ACTION_PURCHASE_STORED_TOKEN,
            self::ACTION_PURCHASE_AND_SUBSCRIBE_RECURRING,
        ];
    }

    /**
     * @param $action
     * @return bool
     */
    public function supportsAction($action)
    {
        return in_array($action, $this->supportsActions());
    }

    /**
     * @param $action
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setAction($action)
    {
        if (!$this->supportsAction($action)) {
            throw new \InvalidArgumentException("Invalid Payment Action Specified");
        }

        $this->action = $action;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return isset($this->action)
            ? $this->action
            : $this->getDefaultAction();
    }

    /**
     * @return $this
     */
    public function buildForm()
    {
        switch($this->getAction()) {
            case self::ACTION_AUTHORIZE:
            case self::ACTION_CAPTURE:
            case self::ACTION_PURCHASE:
            case self::ACTION_PURCHASE_AND_SUBSCRIBE_RECURRING:
            case self::ACTION_CREATE_TOKEN:

                /*

                Display a credit card form
                BUT, Submit a token form

                //*/

                $formType = $this->getIsSubmission()
                    ? new StripeCreateTokenType()
                    : new StripeCcPaymentType();

                $form = $this->getFormFactory()->create($formType);
                $this->setForm($form);

                break;
            case self::ACTION_PURCHASE_STORED_TOKEN:

                $formType = new StripeTokenPaymentType();
                //  set possible values to token input
                $choices = [];
                foreach($this->getCustomerTokens() as $token) {
                    //  set labels as : "Visa : xxxx-0123"
                    $label = "{$token->getCcType()} : xxxx-{$token->getCcLastFour()}";
                    $choices[$token->getToken()] = $label;
                }

                $formType->setTokenOptions($choices);

                $form = $this->getFormFactory()->create($formType);
                $this->setForm($form);

                break;
            default:

                break;
        }

        return $this;
    }

    /**
     * @param $form
     * @return $this
     */
    public function setForm($form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param $isTestMode
     * @return $this
     */
    public function setIsTestMode($isTestMode)
    {
        $this->isTestMode = ($isTestMode != '0' && $isTestMode != 'false');
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsTestMode()
    {
        return $this->isTestMode;
    }

    /**
     * @param $isRefund
     * @return $this
     */
    public function setIsRefund($isRefund)
    {
        $this->isRefund = $isRefund;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsRefund()
    {
        return $this->isRefund;
    }

    /**
     * @param $isSubmission
     * @return $this
     */
    public function setIsSubmission($isSubmission)
    {
        $this->isSubmission = $isSubmission;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsSubmission()
    {
        return $this->isSubmission;
    }

    /**
     * @param $paymentData
     * @return $this
     */
    public function setPaymentData($paymentData)
    {
        $this->paymentData = $paymentData;
        return $this;
    }

    /**
     * @return array
     */
    public function getPaymentData()
    {
        return $this->paymentData;
    }

    /**
     * @param $orderData
     * @return $this
     */
    public function setOrderData($orderData)
    {
        $this->orderData = $orderData;
        return $this;
    }

    /**
     * @return array
     */
    public function getOrderData()
    {
        return $this->orderData;
    }

    /**
     * @param $testPublicKey
     * @return $this
     */
    public function setTestPublicKey($testPublicKey)
    {
        $this->testPublicKey = $testPublicKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getTestPublicKey()
    {
        return $this->testPublicKey;
    }

    /**
     * @param $testPrivateKey
     * @return $this
     */
    public function setTestPrivateKey($testPrivateKey)
    {
        $this->testPrivateKey = $testPrivateKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getTestPrivateKey()
    {
        return $this->testPrivateKey;
    }

    /**
     * @param $livePublicKey
     * @return $this
     */
    public function setLivePublicKey($livePublicKey)
    {
        $this->livePublicKey = $livePublicKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getLivePublicKey()
    {
        return $this->livePublicKey;
    }

    /**
     * @param $livePrivateKey
     * @return $this
     */
    public function setLivePrivateKey($livePrivateKey)
    {
        $this->livePrivateKey = $livePrivateKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getLivePrivateKey()
    {
        return $this->livePrivateKey;
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->getIsTestMode()
            ? $this->getTestPublicKey()
            : $this->getLivePublicKey();
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->getIsTestMode()
            ? $this->getTestPrivateKey()
            : $this->getLivePrivateKey();
    }

    /**
     * @param $confirmation
     * @return $this
     */
    public function setConfirmation($confirmation)
    {
        $this->confirmation = $confirmation;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getConfirmation()
    {
        return $this->confirmation;
    }

    /**
     * @param $ccFingerprint
     * @return $this
     */
    public function setCcFingerprint($ccFingerprint)
    {
        $this->ccFingerprint = $ccFingerprint;
        return $this;
    }

    /**
     * @return string
     */
    public function getCcFingerprint()
    {
        return $this->ccFingerprint;
    }

    /**
     * @param $ccLastFour
     * @return $this
     */
    public function setCcLastFour($ccLastFour)
    {
        $this->ccLastFour = $ccLastFour;
        return $this;
    }

    /**
     * @return string
     */
    public function getCcLastFour()
    {
        return $this->ccLastFour;
    }

    /**
     * @param $ccType
     * @return $this
     */
    public function setCcType($ccType)
    {
        $this->ccType = $ccType;
        return $this;
    }

    /**
     * @return string
     */
    public function getCcType()
    {
        return $this->ccType;
    }

    /**
     * @return array
     */
    public function extractOrderPaymentData()
    {
        $orderData = $this->getOrderData();

        return [
            'code' => $this->getCode(),
            'label' => $this->getLabel(),
            'base_currency' => $orderData['base_currency'],
            'base_amount' => $orderData['base_total'],
            'currency' => $orderData['currency'],
            'amount' => $orderData['total'],
            'is_refund' => $this->getIsRefund(),
            'confirmation' => $this->getConfirmation(),
            'cc_fingerprint' => $this->getCcFingerprint(),
            'cc_last_four' => $this->getCcLastFour(),
            'cc_type' => $this->getCcType(),
        ];
    }

    //// Purchase

    public function purchase()
    {
        $this->buildPurchaseRequest()
            ->sendPurchaseRequest();

        return $this;
    }

    /**
     * @return $this
     */
    public function buildPurchaseRequest()
    {
        $orderData = $this->getOrderData();
        $paymentData = $this->getPaymentData();

        $token = isset($paymentData['token'])
            ? $paymentData['token']
            : '';

        $amount = $orderData['total'];
        $currency = $orderData['currency'];

        $this->setPurchaseRequest([
            'amount' => $amount,
            'currency' => $currency,
            'token' => $token,
        ]);

        return $this;
    }

    /**
     * @param $request
     * @return $this
     */
    public function setPurchaseRequest($request)
    {
        $this->purchaseRequest = $request;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPurchaseRequest()
    {
        return $this->purchaseRequest;
    }

    /**
     * @return $this
     */
    public function sendPurchaseRequest()
    {
        $gateway = new Gateway();
        $gateway->setApiKey($this->getPrivateKey());
        $purchaseResponse = $gateway->purchase($this->getPurchaseRequest())->send();
        $this->setPurchaseResponse($purchaseResponse);

        if ($purchaseResponse->isSuccessful()) {
            $this->setIsPurchased(1);
            $this->setIsCaptured(1); // this is to satisfy OrderService when creating OrderPayment
        }

        return $this;
    }

    /**
     * @param $purchaseResponse
     * @return $this
     */
    public function setPurchaseResponse($purchaseResponse)
    {
        $this->purchaseResponse = $purchaseResponse;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPurchaseResponse()
    {
        return $this->purchaseResponse;
    }

    public function setIsPurchased($isPurchased)
    {
        $this->isPurchased = $isPurchased;
        return $this;
    }

    public function getIsPurchased()
    {
        return $this->isPurchased;
    }

    //// Authorize

    /**
     * @return $this
     */
    public function authorize()
    {

        return $this;
    }

    public function buildAuthorizeRequest()
    {

    }

    public function setAuthorizeRequest($authorizeRequest)
    {
        $this->authorizeRequest = $authorizeRequest;
        return $this;
    }

    public function getAuthorizeRequest()
    {
        return $this->authorizeRequest;
    }

    public function sendAuthorizeRequest()
    {

    }

    public function setAuthorizeResponse($authorizeResponse)
    {
        $this->authorizeResponse = $authorizeResponse;
        return $this;
    }

    public function getAuthorizeResponse()
    {
        return $this->authorizeResponse;
    }

    /**
     * @param $yesNo
     * @return $this
     */
    public function setIsAuthorized($yesNo)
    {
        $this->isAuthorized = $yesNo;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsAuthorized()
    {
        return $this->isAuthorized;
    }

    /**
     * @return bool
     */
    public function authorizeAndCapture()
    {
        return $this->authorize() && $this->capture();
    }

    //// Capture (a pre-authorized transaction ONLY)

    /**
     * @return $this
     */
    public function capture()
    {
        $this->buildCaptureRequest()
            ->sendCaptureRequest();

        /** @var \Omnipay\Common\Message\ResponseInterface $captureResponse */
        $captureResponse = $this->getCaptureResponse();

        $this->setIsCaptured($captureResponse->isSuccessful());

        return $this;
    }

    public function buildCaptureRequest()
    {

        return $this;
    }

    public function setCaptureRequest($captureRequest)
    {
        $this->captureRequest = $captureRequest;
        return $this;
    }

    public function getCaptureRequest()
    {
        return $this->captureRequest;
    }

    public function sendCaptureRequest()
    {

        return $this;
    }

    public function setCaptureResponse($captureResponse)
    {
        $this->captureResponse = $captureResponse;
        return $this;
    }

    public function getCaptureResponse()
    {
        return $this->captureResponse;
    }

    /**
     * @param $isCaptured
     * @return $this
     */
    public function setIsCaptured($isCaptured)
    {
        $this->isCaptured = $isCaptured;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsCaptured()
    {
        return $this->isCaptured;
    }

    //// Create Token

    public function createToken()
    {
        // @see https://stripe.com/docs/charges#saving-credit-card-details-for-later

        $this->buildTokenCreateRequest()
            ->sendTokenCreateRequest();

        return $this;
    }

    /**
     * @return $this
     */
    public function buildTokenCreateRequest()
    {
        $paymentData = $this->getPaymentData();

        $token = isset($paymentData['token'])
            ? $paymentData['token']
            : '';

        $email = isset($paymentData['email'])
            ? $paymentData['email']
            : '';

        $request = [
            'token' => $token,
        ];

        if ($email) {
            $request['email'] = $email;
        }

        $this->setTokenCreateRequest($request);

        return $this;
    }

    /**
     * @param $tokenCreateRequest
     * @return $this
     */
    public function setTokenCreateRequest($tokenCreateRequest)
    {
        $this->tokenCreateRequest = $tokenCreateRequest;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTokenCreateRequest()
    {
        return $this->tokenCreateRequest;
    }

    /**
     * @return $this
     */
    public function sendTokenCreateRequest()
    {
        $gateway = new Gateway();
        $gateway->setApiKey($this->getPrivateKey());

        $captureResponse = $gateway->createCustomer($this->getTokenCreateRequest())->send();
        $this->setTokenCreateResponse($captureResponse);

        if ($captureResponse->isSuccessful()) {
            $this->setIsTokenCreated(1);
            $this->setIsCaptured(1); // this is to satisfy OrderService when creating OrderPayment
        }

        return $this;
    }

    /**
     * @param $tokenCreateResponse
     * @return $this
     */
    public function setTokenCreateResponse($tokenCreateResponse)
    {
        $this->tokenCreateResponse = $tokenCreateResponse;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTokenCreateResponse()
    {
        return $this->tokenCreateResponse;
    }

    public function setIsTokenCreated($isTokenCreated)
    {
        $this->isTokenCreated = $isTokenCreated;
        return $this;
    }

    public function getIsTokenCreated()
    {
        return $this->isTokenCreated;
    }

    /**
     * @return mixed|void
     */
    public function extractCustomerTokenData()
    {
        $createTokenResponse = $this->getTokenCreateResponse();
        $paymentData = $this->getPaymentData();
        //$orderData = $this->getOrderData();

        $ccType = isset($paymentData['cc_type'])
            ? $paymentData['cc_type']
            : '';

        $ccLastFour = isset($paymentData['cc_last_four'])
            ? $paymentData['cc_last_four']
            : '';

        $ccFingerprint = isset($paymentData['cc_fingerprint'])
            ? $paymentData['cc_fingerprint']
            : '';

        $expireDate = null;
        if (isset($paymentData['exp_month']) && isset($paymentData['exp_year'])) {
            $expMonth = (int) $paymentData['exp_month'];
            $expYear = (int) $paymentData['exp_year'];
            $expDay = '01';
            $expireDateStr = "{$expYear}-{$expMonth}-{$expDay}";
            $expireDate = new \DateTime($expireDateStr);
        }

        $responseData = $createTokenResponse->getData();
        $accountId = $responseData['id'];

        return [
            'service' => $this->getCode(),
            'service_account_id' => $accountId,
            'token' => $paymentData['token'],
            'cc_type' => $ccType,
            'cc_last_four' => $ccLastFour,
            'cc_fingerprint' => $ccFingerprint,
            'expires_at' => $expireDate,
        ];
    }

    //// Stored Token

    /**
     * @return $this|mixed
     */
    public function purchaseStoredToken()
    {
        $this->buildTokenPaymentRequest()
            ->sendTokenPaymentRequest();

        return $this;
    }

    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function buildTokenPaymentRequest()
    {
        // @see https://stripe.com/docs/charges#saving-credit-card-details-for-later

        if (!$this->getPaymentCustomerToken()) {
            throw new \InvalidArgumentException("Invalid Token Payment Request");
        }

        $orderData = $this->getOrderData();

        $this->setTokenPaymentRequest([
            'customerReference' => $this->getPaymentCustomerToken()->getServiceAccountId(),
            'currency' => $orderData['currency'],
            'amount' => $orderData['total'],
        ]);

        return $this;
    }

    /**
     * @param $tokenPaymentRequest
     * @return $this
     */
    public function setTokenPaymentRequest($tokenPaymentRequest)
    {
        $this->tokenPaymentRequest = $tokenPaymentRequest;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTokenPaymentRequest()
    {
        return $this->tokenPaymentRequest;
    }

    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function sendTokenPaymentRequest()
    {
        if (!$this->getTokenPaymentRequest()) {
            throw new \InvalidArgumentException("Invalid Token Payment Request");
        }

        $gateway = new Gateway();
        $gateway->setApiKey($this->getPrivateKey());
        $tokenPaymentResponse = $gateway->purchase($this->getTokenPaymentRequest())->send();
        $this->setTokenPaymentResponse($tokenPaymentResponse);

        if ($this->tokenPaymentResponse->isSuccessful()) {
            $this->setIsPurchasedStoredToken(1);
            $this->setIsCaptured(1); // this is to satisfy OrderService when creating OrderPayment
        }

        return $this;
    }

    /**
     * @param $tokenPaymentResponse
     * @return $this
     */
    public function setTokenPaymentResponse($tokenPaymentResponse)
    {
        $this->tokenPaymentResponse = $tokenPaymentResponse;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTokenPaymentResponse()
    {
        return $this->tokenPaymentResponse;
    }

    public function setIsPurchasedStoredToken($isPurchasedStoredToken)
    {
        $this->isPurchasedStoredToken = $isPurchasedStoredToken;
        return $this;
    }

    public function getIsPurchasedStoredToken()
    {
        return $this->isPurchasedStoredToken;
    }

    //// Recurring Subscription

    /**
     * @return mixed|void
     */
    public function purchaseAndSubscribeRecurring()
    {
        $this->buildSubscribeRecurringRequest()
            ->sendSubscribeRecurringRequest();

        return $this;
    }

    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function buildSubscribeRecurringRequest()
    {
        // @see https://stripe.com/docs/api#create_subscription
        // @see https://stripe.com/docs/recipes/subscription-signup

        if (!$this->getPaymentCustomerToken()) {
            throw new \InvalidArgumentException("Invalid Token Payment Request");
        }

        $paymentData = $this->getPaymentData();
        $orderData = $this->getOrderData();

        $extPlanId = isset($paymentData['external_plan_id'])
            ? $paymentData['external_plan_id']
            : '';

        if (!$extPlanId) {
            throw new \InvalidArgumentException("Invalid Token Payment Request");
        }

        $this->setSubscribeRecurringRequest([
            'customerReference' => $this->getPaymentCustomerToken()->getServiceAccountId(),
            'plan' => $extPlanId,
            'currency' => $orderData['currency'],
            'amount' => $orderData['total'],
        ]);

        return $this;
    }

    /**
     * @param $subscribeRecurringRequest
     * @return $this
     */
    public function setSubscribeRecurringRequest($subscribeRecurringRequest)
    {
        $this->subscribeRecurringRequest = $subscribeRecurringRequest;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubscribeRecurringRequest()
    {
        return $this->subscribeRecurringRequest;
    }

    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function sendSubscribeRecurringRequest()
    {
        if (!$this->getSubscribeRecurringRequest()) {
            throw new \InvalidArgumentException("Invalid Recurring Payment Request");
        }

        $gateway = new Gateway();
        $gateway->setApiKey($this->getPrivateKey());
        $paymentResponse = $gateway->createSubscription($this->getSubscribeRecurringRequest())->send();
        $this->setSubscribeRecurringResponse($paymentResponse);

        if ($this->subscribeRecurringResponse->isSuccessful()) {
            $this->setIsPurchasedAndSubscribedRecurring(1);
            $this->setIsCaptured(1); // this is to satisfy OrderService when creating OrderPayment
        }

        return $this;
    }

    /**
     * @param $subscribeRecurringResponse
     * @return $this
     */
    public function setSubscribeRecurringResponse($subscribeRecurringResponse)
    {
        $this->subscribeRecurringResponse = $subscribeRecurringResponse;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubscribeRecurringResponse()
    {
        return $this->subscribeRecurringResponse;
    }

    /**
     * @param $isPurchasedAndSubscribedRecurring
     * @return $this
     */
    public function setIsPurchasedAndSubscribedRecurring($isPurchasedAndSubscribedRecurring)
    {
        $this->isPurchasedAndSubscribedRecurring = $isPurchasedAndSubscribedRecurring;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsPurchasedAndSubscribedRecurring()
    {
        return $this->isPurchasedAndSubscribedRecurring;
    }

    /**
     * Cancel Subscription
     *
     * @return mixed|void
     */
    public function cancelRecurring()
    {
        $this->buildCancelRecurringRequest()
            ->sendCancelRecurringRequest();
    }

    public function buildCancelRecurringRequest()
    {
        if (!$this->getSubscriptionCustomer() || !$this->getSubscriptionCustomer()->getCustomerToken()) {
            throw new \InvalidArgumentException("Cannot cancel without a SubscriptionCustomer and CustomerToken object");
        }

        $cusToken = $this->getSubscriptionCustomer()->getCustomerToken()->getServiceAccountId();
        $subToken = '';
        $customerData = [
            'customerReference' => $cusToken,
        ];

        $gateway = new Gateway();
        $gateway->setApiKey($this->getPrivateKey());
        $customerResponse = $gateway->fetchCustomer($customerData)->send();
        $customerResponseData = $customerResponse->getData();

        if (isset($customerResponseData['subscriptions']['data'])) {
            $subscriptions = $customerResponseData['subscriptions']['data'];
            if ($subscriptions) {
                $subToken = $subscriptions[0]['id'];
            }
        }

        if ($subToken) {
            $subData = [
                'customerReference' => $cusToken,
                'subscriptionReference' => $subToken,
            ];
            $this->setCancelRecurringRequest($subData);
        }
        return $this;
    }

    public function setCancelRecurringRequest($cancelRecurringRequest)
    {
        $this->cancelRecurringRequest = $cancelRecurringRequest;
        return $this;
    }

    public function getCancelRecurringRequest()
    {
        return $this->cancelRecurringRequest;
    }

    public function sendCancelRecurringRequest()
    {
        if (!$this->getCancelRecurringRequest()) {
            throw new \InvalidArgumentException("Cannot cancel recurring without building request first.");
        }

        $gateway = new Gateway();
        $gateway->setApiKey($this->getPrivateKey());
        $subData = $this->getCancelRecurringRequest();

        $cancelRequest = $gateway->cancelSubscription($subData);
        $cancelData = $cancelRequest->getData();
        $cancelData['at_period_end'] = 'true';

        $cancelResponse = $cancelRequest->sendData($cancelData);
        $this->setCancelRecurringResponse($cancelResponse);
        if ($cancelResponse->isSuccessful()) {
            $this->setIsCanceledRecurring(1);
        }

        return $this;
    }

    public function setCancelRecurringResponse($cancelRecurringResponse)
    {
        $this->cancelRecurringResponse;
        return $this;
    }

    public function getCancelRecurringResponse()
    {
        return $this->cancelRecurringResponse;
    }

    public function setIsCanceledRecurring($isCanceled)
    {
        $this->isCanceledRecurring = $isCanceled;
        return $this;
    }

    public function getIsCanceledRecurring()
    {
        return $this->isCanceledRecurring;
    }
}
