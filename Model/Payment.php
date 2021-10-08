<?php
namespace StrongholdPay\Checkout\Model;

use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\OrderManagementInterface;
use StrongholdPay\ApiError\NotFound;
use StrongholdPay\StrongholdPay;

class Payment extends AbstractMethod
{
    const CODE = 'strongholdpay_checkout';
    protected $_code = 'strongholdpay_checkout';
    protected $_isInitializeNeeded = true;

    protected $urlBuilder;
    protected $storeManager;
    protected $orderManagement;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        OrderManagementInterface $orderManagement,
        array $data = [],
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null

    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->orderManagement = $orderManagement;

        StrongholdPay::configure(
            $this->getConfigData('publishable_key'),
            $this->getConfigData('secret_key')
        );
    }

    public function getPayLinkRequest(Order $order)
    {
        $payment = $order->getPayment();
        $payment->save();

        $magentoCustomerId = $order->getCustomerId();
        $address = $order->getBillingAddress() ?? $order->getShippingAddress();

        $email = $order->getCustomerEmail();
        $mobile = $address->getTelephone();

        $customer = null;
        // First, attempt to get the customer using the Magento customer ID.
        if (!empty($magentoCustomerId))
        {
            try
            {
                $customer = \StrongholdPay\StrongholdPay::getCustomerByExternalId($magentoCustomerId);
            }
            catch (NotFound $e) {}
        }

        // If not found by Magento customer ID, use email and mobile.
        if ($customer == null) {
            try {
                $customer = \StrongholdPay\StrongholdPay::findCustomer($email, $mobile);
            }
            catch (NotFound $e) {}
        }

        // If still not found, create the customer.
        if ($customer == null) {
            $customer = \StrongholdPay\StrongholdPay::createCustomer(
                $order->getCustomerFirstname(),
                $order->getCustomerLastname(),
                $email,
                $mobile,
                $address->getCountryId(),
                $address->getRegion(),
                $magentoCustomerId
            );
        }

        $payLink = \StrongholdPay\StrongholdPay::createPayLink(
            $customer["id"],
            $order->getIncrementId(),
            (int)($order->getGrandTotal() * 100),
            $this->urlBuilder->getUrl('strongholdpay/payment/successRedirect'),
            $this->urlBuilder->getUrl('strongholdpay/payment/exitRedirect')
        );

        return [
            'status' => true,
            'payment_url' => $payLink['url']
        ];
    }

    public function applyPayment(Order $order): bool
    {
        try {
            $charge = \StrongholdPay\StrongholdPay::getChargeByExternalId($order->getIncrementId());

            // Set order as paid iff status is 'captured' and the amounts match.
            if ($charge['status'] == 'captured' && $charge['amount'] == (int)($order->getGrandTotal() * 100)) {
                $order->setState(Order::STATE_PROCESSING);
                $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
                $order->save();

                return true;
            }
        } catch (\Exception $e) {
            $this->_logger->error($e);
        }

        return false;
    }
}
