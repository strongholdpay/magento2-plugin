<?php

namespace StrongholdPay\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\AttributeMerger;

class PhoneValidation
{
    public function afterMerge(AttributeMerger $subject, $result)
    {
        $result['telephone']['validation'] = [
            'required-entry'  => true,
            'max_text_length' => 10,
            'validate-number' => true
        ];
        return $result;
    }
}