<?php

namespace StrongholdPay;

use StrongholdPay\ApiError\ApiError;
use StrongholdPay\ApiError\NotFound;

class Exception
{
    public static function throwException($errorResponse)
    {
        $code = $errorResponse['error']['code'];
        $message = "Stronghold API error on response with ID '{$errorResponse['response_id']}', status code {$errorResponse['status_code']}, type: {$errorResponse['error']['type']}, code: {$code}";
        if (isset($errorResponse['error']['message'])) {
            $message = "$message, message: '{$errorResponse['error']['message']}";
        }

        switch ($code)
        {
            case 'not_found':
                throw new NotFound($message);
            default:
                throw new ApiError($message);
        }
    }
}