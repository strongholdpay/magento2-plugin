<?php

namespace StrongholdPay\Checkout\Controller\Payment;

use StrongholdPay\Checkout\Model\Payment as StrongholdPayPayment;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class PlaceOrder extends Action
{
    protected $orderFactory;
    protected $strongholdPayPayment;
    protected $checkoutSession;
    protected $scopeConfig;

    protected $_eventManager;
    protected $quoteRepository;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        Context $context,
        OrderFactory $orderFactory,
        Session $checkoutSession,
        StrongholdPayPayment $strongholdPayPayment,
        ScopeConfigInterface $scopeConfig
    ) {

        parent::__construct($context);
        $this->quoteRepository = $quoteRepository;
        $this->_eventManager = $eventManager;
        $this->orderFactory = $orderFactory;
        $this->strongholdPayPayment = $strongholdPayPayment;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
    }

    protected function _getCheckout()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }


    public function execute()
    {
        $id = $this->checkoutSession->getLastOrderId();

        $order = $this->orderFactory->create()->load($id);

        if (!$order->getIncrementId()) {
            $this->getResponse()->setBody(json_encode([
                'status' => false,
                'reason' => 'Order Not Found',
            ]));
            return;
        }

        // Restores the cart
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(1);
        $this->quoteRepository->save($quote);

        $this->getResponse()->setBody(json_encode($this->strongholdPayPayment->getPayLinkRequest($order)));
    }

}
