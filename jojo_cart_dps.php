<?php
/**
 *                    Jojo CMS
 *                ================
 *
 * Copyright 2008 Harvey Kane <code@ragepank.com>
 * Copyright 2008 Michael Holt <code@gardyneholt.co.nz>
 *
 * See the enclosed file license.txt for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Harvey Kane <code@ragepank.com>
 * @license http://www.fsf.org/copyleft/lgpl.html GNU Lesser General Public License
 * @link    http://www.jojocms.org JojoCMS
 */

define('_DPS_CURRENCY', 'NZD'); //Currently hard-coded to NZD

class jojo_plugin_jojo_cart_dps extends JOJO_Plugin
{
    function getPaymentOptions()
    {
        /* ensure the order currency is the same as DPS currency */
        $currency = call_user_func(array(Jojo_Cart_Class, 'getCartCurrency'));
        if ($currency != _DPS_CURRENCY) return array();

        global $smarty;
        $options = array();

        /* get available card types (specified in options) */
        $cardtypes = explode(',', Jojo::getOption('dps_card_types', 'Visa,Mastercard'));

        /* uppercase first letter of each card type */
        foreach ($cardtypes as $k => $v) {
            $cardtypes[$k] = trim(ucwords($v));
            if ($cardtypes[$k] == 'Visa') {
                $cardimages[$k] = '<img class="creditcard-icon" src="images/creditcardvisa.gif" alt="Visa" />';
            } elseif ($cardtypes[$k] == 'Mastercard') {
                $cardimages[$k] = '<img class="creditcard-icon" src="images/creditcardmastercard.gif" alt="Mastercard" />';
            } elseif ($cardtypes[$k] == 'Amex') {
                $cardimages[$k] = '<img class="creditcard-icon" src="images/creditcardamex.gif" alt="American Express" />';
            }
        }
        $smarty->assign('cardtypes', $cardtypes);
        $options[] = array('id' => 'dps', 'label' => 'Pay now by Credit card '.implode(', ', $cardimages), 'html' => $smarty->fetch('jojo_cart_dps_checkout.tpl'));
        return $options;
    }

    /*
    * Determines whether this payment plugin is active for the current payment.
    */
    function isActive()
    {
        /* Look for a post variable specifying DPS */
        return (Jojo::getFormData('handler', false) == 'dps') ? true : false;
    }

    function process()
    {
        $testmode = call_user_func(array(Jojo_Cart_Class, 'isTestMode'));

        $errors  = array();

        /* ensure the order currency is the same as DPS currency */
        $currency = call_user_func(array(Jojo_Cart_Class, 'getCartCurrency'));
        if ($currency != _DPS_CURRENCY) {
            return array(
                        'success' => false,
                        'receipt' => '',
                        'errors'  => array('DPS is only able to process transactions in '._DPS_CURRENCY.'.')
                        );
        }

        /* read vars */
        $name      = Jojo::getFormData('cardName', '');
        $number    = Jojo::getFormData('cardNumber', ''); //use 4111111111111111 as the test number
        $exp_month = Jojo::getFormData('cardExpiryMonth', '');
        $exp_year  = Jojo::getFormData('cardExpiryYear', '');
        $amount    = number_format(call_user_func(array(Jojo_Cart_Class, 'total')), 2, '.', ''); //DPS amounts MUST be in the format '1.00'
        $merchRef  = Jojo::getFormData('token', '');

        /* error checking */

        /* set DPS authentication constants, used in the DPS script */
        if ($testmode) {
            define('DPS_USERNAME', Jojo::getOption('dps_test_username', false));
            define('DPS_PASSWORD', Jojo::getOption('dps_test_password', false));
        } else {
            define('DPS_USERNAME', Jojo::getOption('dps_username', false));
            define('DPS_PASSWORD', Jojo::getOption('dps_password', false));
        }

        /* include the DPS code */
        foreach (Jojo::listPlugins('external/dps/dps.php') as $pluginfile) {
            require_once($pluginfile);
            break;
        }

        /* process the transaction */
        $params = process_request($name, $amount, $number, $exp_month, $exp_year, $merchRef);
        $success  = $params['TXN']['SUCCESS'];
        $response = $params['TXN'][$success];


        if ($success) {
            //$receipt = array('Transaction reference' => $params['TXN']['DPSTXNREF'], 'Response' => $params['TXN']['RESPONSETEXT']);
            $receipt = $response;
        } else {
            $receipt = array('HELPTEXT' => $params['TXN']['HELPTEXT'], 'DPSTXNREF' => $params['TXN']['DPSTXNREF']);
            $errors[] = $params['TXN']['HELPTEXT'];
        }

        $message = ($success) ? 'Thank you for your payment via Credit Card.': '';

        return array(
                    'success' => $success,
                    'receipt' => $receipt,
                    'errors'  => $errors,
                    'message' => $message
    );
    }
}