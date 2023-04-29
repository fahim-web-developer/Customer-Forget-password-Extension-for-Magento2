<?php

namespace Fahim\CustomerForgotPassword\Controller\Forgot;

use Exception;
use Magento\Captcha\Helper\Data as CaptchaData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Zend_Validate;
use Zend_Validate_Exception;


class Index extends Action
{
    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @type JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @type CaptchaData
     */
    protected $captchaHelper;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param Escaper $escaper
     * @param JsonFactory $resultJsonFactory
     * @param CaptchaData $captchaHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Escaper $escaper,
        JsonFactory $resultJsonFactory,
        CaptchaData $captchaHelper
    ) {
        $this->session                   = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->escaper                   = $escaper;
        $this->resultJsonFactory         = $resultJsonFactory;
        $this->captchaHelper             = $captchaHelper;

        parent::__construct($context);
    }

    /**
     * @return bool
     */
    // public function checkCaptcha()
    // {
    //     $formId       = 'user_forgotpassword';
    //     $captchaModel = $this->captchaHelper->getCaptcha($formId);
    //     $resolve      = $this->socialHelper->captchaResolve($this->getRequest(), $formId);

    //     return !($captchaModel->isRequired() && !$captchaModel->isCorrect($resolve));
    // }

    /**
     * @return $this|ResponseInterface|ResultInterface
     * @throws Zend_Validate_Exception
     */
    public function execute()
    {
        /**
         * @var Json $resultJson
         */
        $resultJson = $this->resultJsonFactory->create();

        $result = [
            'success' => false,
            'message' => []
        ];

        // if (!$this->checkCaptcha()) {
        //     $result['message'] = __('Incorrect CAPTCHA.');

        //     return $resultJson->setData($result);
        // }

        /**
         * @var Redirect $resultRedirect
         */
        $email = (string)$this->getRequest()->getPost('email');

        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                $this->session->setForgottenEmail($email);
                $result['message'][] = __('Please correct the email address.');
            }

            try {
                $this->customerAccountManagement->initiatePasswordReset(
                    $email,
                    AccountManagement::EMAIL_RESET
                );
                $result['success']   = true;
                $result['message'][] = __(
                    'If there is an account associated with %1 you will receive an email with a link to reset your password.',
                    $this->escaper->escapeHtml($email)
                );
            } catch (NoSuchEntityException $e) {
                $result['success']   = true;
                $result['message'][] = __(
                    'If there is an account associated with %1 you will receive an email with a link to reset your password.',
                    $this->escaper->escapeHtml($email)
                );
                // Do nothing, we don't want anyone to use this action to determine which email accounts are registered.
            } catch (SecurityViolationException $exception) {
                $result['error']     = true;
                $result['message'][] = $exception->getMessage();
            } catch (Exception $exception) {
                $result['error']     = true;
                $result['message'][] = __('We\'re unable to send the password reset email.');
            }
        }

        return $resultJson->setData($result);
    }
}
