<?php

namespace StrongholdPay\Checkout\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use StrongholdPay\Checkout\Model\Payment as StrongholdPayPayment;

class SuccessRedirect extends Action
{
    protected $order;
    protected $strongholdPayPayment;

    public function __construct(
        Context $context,
        Order $order,
        StrongholdPayPayment $strongholdPayPayment
    ) {

        parent::__construct($context);
        $this->order = $order;
        $this->strongholdPayPayment = $strongholdPayPayment;
    }

    public function execute()
    {
        $session = $this->_objectManager->get('Magento\Checkout\Model\Session');
        $order = $this->order->loadByIncrementId(($session->getLastRealOrderId()));
        if ($this->strongholdPayPayment->applyPayment($order)) {
            $this->_redirect('checkout/onepage/success');
        } else {
            $this->_redirect('strongholdpay/payment/exitRedirect');
        }
    }
}