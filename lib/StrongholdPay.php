<?php

namespace StrongholdPay;

use StrongholdPay\ApiError\ApiError;
use StrongholdPay\ApiError\NotFound;

class StrongholdPay
{
    private const USER_AGENT_ORIGIN = 'Stronghold Pay PHP';
    private const API_ROOT = 'https://api.strongholdpay.com/v2';

    private static $publishableKey;
    private static $secretKey;

    public static function configure($publishableKey, $secretKey)
    {
        self::$publishableKey = $publishableKey;
        self::$secretKey = $secretKey;
    }

    public static function getCustomerByExternalId($externalCustomerId)
    {
        return self::get("/customers/$externalCustomerId?is_external_id=true");
    }

    public static function createCustomer($firstName, $lastName, $email, $mobile, $country, $state, $external_id)
    {
        $country = strtolower($country);
        if ($country != 'us') {
            throw new ApiError('the Stronghold Pay checkout plugin does not allow non-US customers to checkout');
        }

        $params = [
            'individual' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'mobile' => '+1' . $mobile
            ],
            'country' => $country,
            'state' => self::stateNameToAlpha2($state)
        ];

        if (!empty($external_id)) {
            $params['external_id'] = $external_id;
        }

        return self::post('/customers', $params);
    }

    public static function findCustomer($email, $mobile)
    {
        $params = [];
        if (!empty($email)) {
            $params['email'] = $email;
        }
        if (!empty($mobile)) {
            $params['mobile'] = '+1' . $mobile;
        }

        $query = http_build_query($params);
        $customers = self::get("/customers?$query")['items'];

        $numCustomers = count($customers);
        if ($numCustomers == 0) {
            throw new NotFound('no customer found');
        }

        if ($numCustomers > 1) {
            throw new ApiError('more than one customer found when searching on the Stronghold API; unexpected');
        }

        return $customers[0];
    }

    public static function getChargeByExternalId($externalChargeId)
    {
        return self::get("/charges/$externalChargeId?is_external_id=true");
    }

    public static function createPayLink($customerId, $orderExternalId, $orderTotal, $successUrl, $exitUrl)
    {
        $params = [
            'type' => 'checkout',
            'customer_id' => $customerId,
            'order' => [
                'total_amount' => $orderTotal
            ],
            'charge' => [
                'external_id' => $orderExternalId
            ],
            'callbacks' => [
                'success_url' => $successUrl,
                'exit_url' => $exitUrl
            ]
        ];

        return self::post('/links', $params);
    }

    private static function stateNameToAlpha2($stateName) {
        $states = [
            'Alabama' => 'al',
            'Alaska' => 'ak',
            'Arizona' => 'az',
            'Arkansas' => 'ar',
            'California' => 'ca',
            'Colorado' => 'co',
            'Connecticut' => 'ct',
            'Delaware' => 'de',
            'Florida' => 'fl',
            'Georgia' => 'ga',
            'Hawaii' => 'hi',
            'Idaho' => 'id',
            'Illinois' => 'il',
            'Indiana' => 'in',
            'Iowa' => 'ia',
            'Kansas' => 'ks',
            'Kentucky' => 'ky',
            'Louisiana' => 'la',
            'Maine' => 'me',
            'Maryland' => 'md',
            'Massachusetts' => 'ma',
            'Michigan' => 'mi',
            'Minnesota' => 'mn',
            'Mississippi' => 'ms',
            'Missouri' => 'mo',
            'Montana' => 'mt',
            'Nebraska' => 'ne',
            'Nevada' => 'nv',
            'New Hampshire' => 'nh',
            'New Jersey' => 'nj',
            'New Mexico' => 'nm',
            'New York' => 'ny',
            'North Carolina' => 'nc',
            'North Dakota' => 'nd',
            'Ohio' => 'oh',
            'Oklahoma' => 'ok',
            'Oregon' => 'or',
            'Pennsylvania' => 'pa',
            'Rhode Island' => 'ri',
            'South Carolina' => 'sc',
            'South Dakota' => 'sd',
            'Tennessee' => 'tn',
            'Texas' => 'tx',
            'Utah' => 'ut',
            'Vermont' => 'vt',
            'Virginia' => 'va',
            'Washington' => 'wa',
            'West Virginia' => 'wv',
            'Wisconsin' => 'wi',
            'Wyomin' => 'wy'
        ];

        if (!isset($states[$stateName]))
        {
            throw new ApiError("'$stateName' is not a valid state on the Stronghold API");
        }

        return $states[$stateName];
    }

    private static function get($path)
    {
        return self::request('GET', $path);
    }

    private static function post($path, $params = array())
    {
        return self::request('POST', $path, $params);
    }

    private static function request($method, $path, $params = array())
    {
        $url = self::API_ROOT . $path;

        $headers = array();
        $headers[] = 'SH-SECRET-KEY: ' . self::$secretKey;

        $curl = curl_init();

        if ($method == 'POST') {
            $body = json_encode($params);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, self::USER_AGENT_ORIGIN);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($curl);
        $json = json_decode($response, true);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpStatus >= 200 && $httpStatus < 400) {
            return $json["result"];
        } else {
            \StrongholdPay\Exception::throwException($json);
        }
    }
}