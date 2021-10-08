<?php

namespace StrongholdPay\Checkout\Controller\Payment;

use Magento\Framework\App\Action\Action;

class ExitRedirect extends Action
{
    protected function _getCheckout()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }

    public function execute()
    {
        if ($this->_getCheckout()->getLastRealOrderId()) {
            $order = $this->_getCheckout()->getLastRealOrder();
            if ($order->getId() && !$order->isCanceled()) {
                $order->registerCancellation('Payment canceled by customer')->save();
            }

            $this->_getCheckout()->restoreQuote();
            $this->_redirect('checkout/cart');
        }
    }
}