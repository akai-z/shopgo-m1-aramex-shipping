<?php

class Shopgo_AramexShipping_Helper_Data
    extends Shopgo_ShippingCore_Helper_Abstract
{
    const LOG_EMAIL_TEMPLATE                   = 'aramex_shipping_log_email_template';
    const SUPPLIER_NOTIFICATION_EMAIL_TEMPLATE = 'aramex_shipping_supplier_notification_email_template';
    const GENERAL_CONTACT_EMAIL                = 'trans_email/ident_general/email';

    const CARRIERS_ARAMEX_SYSTEM_PATH          = 'carriers/aramex/';
    const SHIPPING_ORIGIN_SYSTEM_PATH          = 'shipping/origin/';
    const ADDITIONAL_INFO_SYSTEM_PATH          = 'shipping/additional_info/';
    const ARAMEX_SETTINGS_SYSTEM_PATH          = 'shipping/aramex_settings/';

    const AUTHOR_EMAIL                         = 'mageamex@gmail.com';


    protected $_logFile = 'aramex_shipping.log';


    public function __construct()
    {
        $code  = 'aramex_cod';
        $cfesm = $this->codFilteringEnabledShippingMethods();

        if (!empty($cesm)) {
            $cfesm[] = $code;
        } else {
            $cfesm = array($code);
        }

        $this->codFilteringEnabledShippingMethods('set', $cfesm);
    }

    public function getSuppliersCollection($id = null)
    {
        $collection = Mage::getModel('aramexshipping/supplier')->getCollection();

        if ($id) {
            $collection->addFieldToFilter('asv_id', array('eq' => $id));
        }

        $suppliers = array();
        foreach ($collection as $supplier) {
            $suppliers[] = $supplier;
        }

        if (isset($suppliers[0]) && $id) {
            $suppliers = $suppliers[0];
        }

        return $suppliers;
    }

    public function getOriginSupplier($section = '')
    {
        $data = array();
        $generalInfo   = array();
        $aramexAccount = array();

        if ($section == 'general_info' || empty($section)) {
            $systemSettings = $this->getShippingSettings(array('origin', 'additional_info'));

            $generalInfo = $data = array(
                'country_code'           => strtoupper($this->getConfigData('country_id', 'shipping_origin')),
                'state_or_province_code' => $this->getConfigData('region_id', 'shipping_origin'),
                'post_code'              => $this->getConfigData('postcode', 'shipping_origin'),
                'city'                   => ucwords(strtolower($this->getConfigData('city', 'shipping_origin'))),
                'address_line1'          => $this->getConfigData('street_line1', 'shipping_origin'),
                'address_line2'          => $this->getConfigData('street_line2', 'shipping_origin'),
                'address_line3'          => $systemSettings['origin']['street_line3'],
                'department'             => $this->getConfigData('department', 'aramex_settings'),
                'person_name'            => $systemSettings['additional_info']['person_name'],
                'person_title'           => $systemSettings['additional_info']['person_title'],
                'company_name'           => $systemSettings['additional_info']['company'],
                'phone_number1'          => $systemSettings['additional_info']['phone_number'],
                'phone_number1_ext'      => $systemSettings['additional_info']['phone_number_ext'],
                'phone_number2'          => $systemSettings['additional_info']['phone_number2'],
                'phone_number2_ext'      => $systemSettings['additional_info']['phone_number2_ext'],
                'fax_number'             => $systemSettings['additional_info']['faxnumber'],
                'cellphone'              => $systemSettings['additional_info']['cellphone'],
                'email'                  => $systemSettings['additional_info']['email'],
                'type'                   => $this->getConfigData('type', 'aramex_settings')
            );
        }

        if ($section == 'aramex_account' || empty($section)) {
            $aramexAccount = $data = array(
                'username'             => $this->getConfigData('username', 'carriers_aramex'),
                'password'             => Mage::helper('core')->decrypt($this->getConfigData('password', 'carriers_aramex')),
                'account_country_code' => $this->getConfigData('account_country_code', 'carriers_aramex'),
                'account_entity'       => $this->getConfigData('account_entity', 'carriers_aramex'),
                'account_number'       => $this->getConfigData('account_number', 'carriers_aramex'),
                'account_pin'          => Mage::helper('core')->decrypt($this->getConfigData('account_pin', 'carriers_aramex')),
                'cod_account_number'   => $this->getConfigData('cod_account_number', 'carriers_aramex'),
                'cod_account_pin'      => Mage::helper('core')->decrypt($this->getConfigData('cod_account_pin', 'carriers_aramex'))
            );
        }

        if (empty($section)) {
            $data = array_merge($aramexAccount, $generalInfo);
        }

        return $data;
    }

    public function getClientInfo($source, $method = '')
    {
        if (empty($source)) {
            $source = $this->getOriginSupplier('aramex_account');
        }

        $clientInfo = array(
            'UserName' => $source['username'],
            'Password' => $source['password'],
            'Version'  => 'v1.0'
        );

        if ($source['account_country_code']) {
            $clientInfo['AccountCountryCode'] = strtoupper($source['account_country_code']);
        }
        if ($source['account_entity']) {
            $clientInfo['AccountEntity'] = strtoupper($source['account_entity']);
        }

        if ($method == 'cod' && $this->isCodAccountSet($source)) {
            if ($source['cod_account_number']) {
                $clientInfo['AccountNumber'] = $source['cod_account_number'];
            }
            if ($source['cod_account_pin']) {
                $clientInfo['AccountPin'] = $source['cod_account_pin'];
            }
        } else {
            if ($source['account_number']) {
                $clientInfo['AccountNumber'] = $source['account_number'];
            }
            if ($source['account_pin']) {
                $clientInfo['AccountPin'] = $source['account_pin'];
            }
        }

        return $clientInfo;
    }

    public function isCodAccountSet($info = array())
    {
        $accountNumber = isset($info['cod_account_number'])
            ? $info['cod_account_number']
            : $this->getConfigData('cod_account_number', 'carriers_aramex');

        $accountPin = isset($info['cod_account_pin'])
            ? $info['cod_account_number']
            : Mage::helper('core')->decrypt($this->getConfigData('cod_account_pin', 'carriers_aramex'));

        return $this->getConfigData('cod_account', 'carriers_aramex') && $accountNumber && $accountPin;
    }

    public function getConfigData($var, $type, $store = null)
    {
        $path = '';
        switch ($type) {
            case 'carriers_aramex':
                $path = self::CARRIERS_ARAMEX_SYSTEM_PATH;
                break;
            case 'shipping_origin':
                $path = self::SHIPPING_ORIGIN_SYSTEM_PATH;
                break;
            case 'additional_info':
                $path = self::ADDITIONAL_INFO_SYSTEM_PATH;
                break;
            case 'aramex_settings':
                $path = self::ARAMEX_SETTINGS_SYSTEM_PATH;
                break;
        }

        return Mage::getStoreConfig($path . $var, $store);
    }

    public function soapClient($wsdlName, $callParams, $scOptions = array())
    {
        $wsdl = $this->_getWsdl($wsdlName);
        $result = null;

        if (!isset($scOptions['soap_version'])) {
            $scOptions['soap_version'] = SOAP_1_1;
        } else if (!$scOptions['soap_version']) {
            $scOptions['soap_version'] = SOAP_1_1;
        }

        try {
            $soapClient = new SoapClient($wsdl, $scOptions);
            $result = $this->_soapClientCall($wsdlName, $soapClient, $callParams);
        } catch (SoapFault $sf) {
            $this->log($sf->faultstring);
            $result = '[SoapFault]';
        }

        return $result;
    }

    private function _soapClientCall($service, $soapClient, $callParams)
    {
        $result = null;

        switch ($service) {
            case 'rates_calculator':
                $result = $soapClient->CalculateRate($callParams);
                break;
            case 'shipping_service':
                $result = $soapClient->CreateShipments($callParams);
                break;
            case 'pickup_create_service':
                $result = $soapClient->CreatePickup($callParams);
                break;
            case 'pickup_cancel_service':
                $result = $soapClient->CancelPickup($callParams);
                break;
            case 'tracking_service':
                $result = $soapClient->TrackShipments($callParams);
                break;
        }

        return $result;
    }

    private function _getWsdl($name)
    {
        $wsdl = '';
        $wsdlPath = Mage::getModuleDir('etc', 'Shopgo_AramexShipping') . DS . 'wsdl';

        switch ($name) {
            case 'rates_calculator':
                $wsdl = $wsdlPath . DS . 'aramex_rates_calculator_service.wsdl';
                break;
            case 'shipping_service':
                $wsdl = $wsdlPath . DS . 'aramex_shipping_service.wsdl';
                break;
            case 'pickup_create_service':
                $wsdl = $wsdlPath . DS . 'aramex_shipping_service.wsdl';
                break;
            case 'pickup_cancel_service':
                $wsdl = $wsdlPath . DS . 'aramex_shipping_service.wsdl';
                break;
            case 'tracking_service':
                $wsdl = $wsdlPath . DS . 'aramex_shipments_tracking_service.wsdl';
                break;
        }

        return $wsdl;
    }

    public function getServiceErrorMessages($messages)
    {
        $message = '';

        if (gettype($messages) == 'array') {
            foreach ($messages as $msg) {
                $message .= $msg->Message . "\n";
            }
        } else {
            $message = $messages->Message;
        }

        return trim($message);
    }

    public function isPositiveInteger($var)
    {
        return is_numeric($var) && (int)$var == $var && (int)$var > 0;
    }

    public function sendLogEmail($params = array())
    {
        if (!empty($params)) {
            if (!isset($params['subject']) || !isset($params['content'])) {
                return;
            }

            $to = array();

            if (gettype($params['content']) == 'array') {
                $params['content'] = print_r($params['content'], true);
            }

            $params['website_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

            $params['content'] = '<pre>' . $params['content'] . '</pre>';

            if ($this->getConfigData('author_reports', 'carriers_aramex')) {
                $to[] = self::AUTHOR_EMAIL;
            }

            if ($this->getConfigData('admin_reports', 'carriers_aramex')) {
                $reportsEmails = array_map('trim', explode(',', $this->getConfigData('reports_emails', 'carriers_aramex')));
                if (empty($reportsEmails)) {
                    $reportsEmails = array(Mage::getStoreConfig(self::GENERAL_CONTACT_EMAIL));
                }
                $to = array_unique(array_merge($to, $reportsEmails));
            }

            foreach ($to as $r) {
                $this->sendEmail($r, self::LOG_EMAIL_TEMPLATE, $params);
            }
        }
    }

    public function notifySupplier($to, $params = array())
    {
        if ($this->getConfigData('alert_suppliers', 'carriers_aramex')) {
            $this->sendEmail($to, self::SUPPLIER_NOTIFICATION_EMAIL_TEMPLATE, $params, 'sales');
        }
    }

    public function debug($params, $file = '')
    {
        if ($this->getConfigData('debug', 'carriers_aramex')) {
            $this->log($params, '', $file);
        }
    }

    public function hideLogPrivacies($data, $mass = false)
    {
        $mask = '******';

        try {
            if ($mass) {
                foreach ($data as $key => $val) {
                    if (isset($data[$key]['ClientInfo'])) {
                        $data[$key]['ClientInfo']['UserName'] = $mask;
                        $data[$key]['ClientInfo']['Password'] = $mask;
                        $data[$key]['ClientInfo']['AccountPin'] = $mask;
                    }
                    if (isset($data[$key]['supplier'])) {
                        $data[$key]['supplier']['username'] = $mask;
                        $data[$key]['supplier']['password'] = $mask;
                        $data[$key]['supplier']['account_pin'] = $mask;
                    }
                }
            } else {
                $data['ClientInfo']['UserName'] = $mask;
                $data['ClientInfo']['Password'] = $mask;
                $data['ClientInfo']['AccountPin'] = $mask;
            }
        } catch (Exception $e) {
            $this->log($e, 'exception');
        }

        return $data;
    }

    public function getTimePartsOptions($timePart, $clockSystem = 12)
    {
        $options = array(array('value' => '', 'label' => ''));

        switch ($timePart) {
            case 'hour':
                $options = array(array('value' => '', 'label' => 'hour'));
                $i     = 1;
                $hours = 12;

                if ($clockSystem == 24) {
                    $i     = 0;
                    $hours = 23;
                }

                for (; $i <= $hours; $i++) {
                    $leadingZero = '';
                    if ($i < 10) {
                        $leadingZero = '0';
                    }
                    $options[] = array(
                        'value' => $leadingZero . $i,
                        'label' => $leadingZero . $i
                    );
                }
                break;
            case 'minute':
                $options = array(array('value' => '', 'label' => 'minute'));
                $i     = 0;
                $minutes = 60;

                for (; $i < $minutes; $i++) {
                    $leadingZero = '';
                    if ($i < 10) {
                        $leadingZero = '0';
                    }
                    $options[] = array(
                        'value' => $leadingZero . $i,
                        'label' => $leadingZero . $i
                    );
                }
                break;
            case 'second':
                $options = array(array('value' => '', 'label' => 'second'));
                $i       = 0;
                $seconds = 60;

                for (; $i < $seconds; $i++) {
                    $leadingZero = '';
                    if ($i < 10) {
                        $leadingZero = '0';
                    }
                    $options[] = array(
                        'value' => $leadingZero . $i,
                        'label' => $leadingZero . $i
                    );
                }
                break;
            case 'meridian':
                $options = array(
                    array(
                        'value' => 'am',
                        'label' => $this->__('AM')
                    ),
                    array(
                        'value' => 'pm',
                        'label' => $this->__('PM')
                    )
                );
                break;
        }

        return $options;
    }

    public function combineTimeParts($parts)
    {
        $time = $parts['hour'] . ':' . $parts['minute'] . ':' . $parts['second'];

        if (isset($parts['meridian'])) {
            $time .= ' ' . $parts['meridian'];
        }

        return $time;
    }

    public function _getAdminhtmlShipmentForms($block)
    {
        $html = $block->getChildHtml('aramex_shipment') . "\n"
              . $block->getChildHtml('aramex_shipment_pickup');

        return $html;
    }

    public function isAdvIfconfigEnabled()
    {
        return Mage::helper('core')->isModuleEnabled('Shopgo_AdvIfconfig');
    }
}
