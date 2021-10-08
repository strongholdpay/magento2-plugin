<?php

namespace StrongholdPay\Checkout\Controller\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class TestConnection extends Action
{
    protected $checkoutSession;
    protected $scopeConfig;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        // No actual test is performed; return success.
        $this->getResponse()->setBody(json_encode([
            'status' => true,
        ]));
    }
}