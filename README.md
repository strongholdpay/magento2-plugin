# Stronghold Pay Magento 2 Plugin

This plugin adds [Stronghold Checkout](https://stronghold.co/checkout) to your Magento 2 instance.

## Installation via Composer

You can install the plugin via [Composer](http://getcomposer.org/). Run the following command in your
terminal:

1. Navigate to your Magento 2 root folder.

2. Install the plugin:
    ```bash
    composer require strongholdpay/magento2-plugin
    ```

3. Enable the plugin:

    ```bash
    php bin/magento module:enable StrongholdPay_Checkout --clear-static-content
    php bin/magento setup:upgrade
    ```
## Plugin Configuration

Enable and configure the plugin in the Magento Admin area under `Stores / Configuration / Sales / Payment Methods / Stronghold Checkout`.

You will need either sandbox or live keys for configuration. These can be retrieved from your Stronghold Pay Dashboard, or contact happiness@stronghold.co.