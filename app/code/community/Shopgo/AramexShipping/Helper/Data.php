<?php

class Shopgo_AramexShipping_Helper_Data
    extends Mage_Core_Helper_Abstract
{
    const LOG_FILE                             = 'aramex_shipping.log';
    const LOG_EMAIL_TEMPLATE                   = 'aramex_shipping_log_email_template';
    const SUPPLIER_NOTIFICATION_EMAIL_TEMPLATE = 'aramex_shipping_supplier_notification_email_template';
    const GENERAL_CONTACT_EMAIL                = 'trans_email/ident_general/email';
    const CARRIERS_ARAMEX_SYSTEM_PATH          = 'carriers/aramex/';
    const SHIPPING_ORIGIN_SYSTEM_PATH          = 'shipping/origin/';
    const AUTHOR_EMAIL                         = 'mageamex@gmail.com';

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

        $generalInfo = array(
            'country_code'           => strtoupper($this->getConfigData('country_id', 'shipping_origin')),
            'state_or_province_code' => $this->getConfigData('region_id', 'shipping_origin'),
            'post_code'              => $this->getConfigData('postcode', 'shipping_origin'),
            'city'                   => ucwords(strtolower($this->getConfigData('city', 'shipping_origin'))),
            'address_line1'          => $this->getConfigData('street_line1', 'shipping_origin'),
            'address_line2'          => $this->getConfigData('street_line2', 'shipping_origin'),
            'address_line3'          => $this->getConfigData('street_line3', 'shipping_origin'),
            'department'             => $this->getConfigData('department', 'shipping_origin'),
            'person_name'            => $this->getConfigData('person_name', 'shipping_origin'),
            'person_title'           => $this->getConfigData('person_title', 'shipping_origin'),
            'company_name'           => $this->getConfigData('company', 'shipping_origin'),
            'phone_number1'          => $this->getConfigData('phone_number1', 'shipping_origin'),
            'phone_number1_ext'      => $this->getConfigData('phone_number1_ext', 'shipping_origin'),
            'phone_number2'          => $this->getConfigData('phone_number2', 'shipping_origin'),
            'phone_number2_ext'      => $this->getConfigData('phone_number2_ext', 'shipping_origin'),
            'fax_number'             => $this->getConfigData('faxnumber', 'shipping_origin'),
            'cellphone'              => $this->getConfigData('cellphone', 'shipping_origin'),
            'email'                  => $this->getConfigData('email', 'shipping_origin'),
            'type'                   => $this->getConfigData('type', 'shipping_origin')
        );

        $aramexAccount = array(
            'username'             => $this->getConfigData('username', 'carriers_aramex'),
            'password'             => Mage::helper('core')->decrypt($this->getConfigData('password', 'carriers_aramex')),
            'account_country_code' => $this->getConfigData('account_country_code', 'carriers_aramex'),
            'account_entity'       => $this->getConfigData('account_entity', 'carriers_aramex'),
            'account_number'       => $this->getConfigData('account_number', 'carriers_aramex'),
            'account_pin'          => Mage::helper('core')->decrypt($this->getConfigData('account_pin', 'carriers_aramex'))
        );

        if ($section == 'general_info') {
            $data = $generalInfo;
        } elseif ($section == 'aramex_account') {
            $data = $aramexAccount;
        } else {
            $data = array_merge($aramexAccount, $generalInfo);
        }

        return $data;
    }

    public function getClientInfo($source)
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
        if ($source['account_number']) {
            $clientInfo['AccountNumber'] = $source['account_number'];
        }
        if ($source['account_pin']) {
            $clientInfo['AccountPin'] = $source['account_pin'];
        }

        return $clientInfo;
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

    public function currencyConvert($price, $from, $to, $output = '', $round = null)
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

        if ('_BASE_' == $from) {
            $from = $baseCurrencyCode;
        } elseif ('_CURRENT_' == $from) {
            $from = $currentCurrencyCode;
        }

        if ('_BASE_' == $to) {
            $to = $baseCurrencyCode;
        } elseif ('_CURRENT_' == $to) {
            $to = $currentCurrencyCode;
        }

        $output = strtolower($output);

        $error  = false;
        $result = array('price' => $price, 'currency' => $from);

        if ($from != $to) {
            $allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
            $rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCurrencyCode, array_values($allowedCurrencies));

            if (empty($rates) || !isset($rates[$from]) || !isset($rates[$to])) {
                $error = true;
            } elseif (empty($rates[$from]) || empty($rates[$to])) {
                $error = true;
            }

            if ($error) {
                $this->log($this->__('Currency conversion error.'));
                if (isset($result[$output])) {
                    return $result[$output];
                } else {
                    return $result;
                }
            }

            $result = array(
                'price' => ($price * $rates[$to]) / $rates[$from],
                'currency' => $to
            );
        }

        if (is_int($round)) {
            $result['price'] = round($result['price'], $round);
        }

        if (isset($result[$output])) {
            return $result[$output];
        }

        return $result;
    }

    public function convertRateCurrency($price, $priceCurrencyCode)
    {
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        $result = array('price' => $price, 'currency' => $priceCurrencyCode);

        if ($priceCurrencyCode != $baseCurrencyCode) {
            $result = $this->currencyConvert($price, $priceCurrencyCode, $baseCurrencyCode);
        }

        return $result;
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

    public function sendEmail($to, $templateId, $params = array(), $sender = 'general')
    {
        $mailTemplate = Mage::getModel('core/email_template');
        $translate = Mage::getSingleton('core/translate');
        $result = true;

        $mailTemplate->setTemplateSubject($params['subject'])->sendTransactional(
            $templateId,
            $sender,
            $to,
            null,
            $params,
            Mage::app()->getStore()->getId()
        );

        if (!$mailTemplate->getSentSuccess()) {
            $this->log('Could not send log email.');
            $result = false;
        }

        $translate->setTranslateInline(true);

        return $result;
    }

    public function debug($params, $file = '')
    {
        if ($this->getConfigData('debug', 'carriers_aramex')) {
            $this->log($params, '', $file);
        }
    }

    public function userMessage($message, $type, $sessionPath = 'core/session')
    {
        try {
            $session = Mage::getSingleton($sessionPath);

            switch ($type) {
                case 'error':
                    $session->addError($message);
                    break;
                case 'success':
                    $session->addSuccess($message);
                    break;
                case 'notice':
                    $session->addNotice($message);
                    break;
            }
        } catch (Exception $e) {
            $this->log($e, 'exception');
            return false;
        }

        return true;
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

    public function getSystemConfigNodeDepends($sectionName, $groupName = null, $fieldName = null)
    {
        $config = Mage::getSingleton('adminhtml/config');
        $sectionName = trim($sectionName, '/');
        $path = '//sections/' . $sectionName;
        $groupNode = $fieldNode = null;
        $sectionNode = $config->getSections()->xpath($path);
        if (!empty($groupName)) {
            $groupPath = $path .= '/groups/' . trim($groupName, '/');
            $groupNode = $config->getSections()->xpath($path);
        }
        if (!empty($fieldName)) {
            if (!empty($groupName)) {
                $path .= '/fields/' . trim($fieldName, '/');
                $fieldNode = $config->getSections()->xpath($path);
            }
            else {
                Mage::throwException(
                    $this->__('The group node name must be specified with field node name.')
                );
            }
        }
        $path .= '/depends';
        $dependsNode = $config->getSections()->xpath($path);
        foreach ($dependsNode as $node) {
            return $node;
        }
        return null;
    }

    public function checkSystemConfigNodeDepends($section, $group, $field, $result = false)
    {
        $depends = $this->getSystemConfigNodeDepends($section, $group, $field);

        foreach ((array)$depends as $fieldName => $fieldValue) {
            $path = $section . '/' . $group . '/' . $fieldName;
            $dependValid = $fieldValue == Mage::getStoreConfigFlag($path);
            $result = $result && $this->checkSystemConfigNodeDepends(
                $section, $group, $fieldName, $dependValid
            );
        }

        return $result;
    }

    public function getStoreConfigWithDependsFlag($configPath, $requiredDepends = array(), $type = 'tree')
    {
        $ifConfig = Mage::getStoreConfigFlag($configPath);

        if ($ifConfig) {
            if ($type == 1 || $type == 'tree') {
                $configPath = explode('/', $configPath);
                $ifConfig = $ifConfig
                    && $this->checkSystemConfigNodeDepends(
                    $configPath[0], // Section
                    $configPath[1], // Group
                    $configPath[2], // Field
                    $ifConfig
                );
            }

            if ($type == 1 || $type == 'required') {
                if (gettype($requiredDepends) == 'string') {
                    $requiredDepends = array_map('trim', explode(',', $requiredDepends));
                }

                foreach ($requiredDepends as $depend) {
                    $ifConfig = $ifConfig && Mage::getStoreConfigFlag($depend);
                }
            }
        }

        return $ifConfig;
    }

    public function log($params, $type = 'system', $_file = '')
    {
        if (empty($params)) {
            return false;
        }

        if ($type == 'system' || $type == '') {
            if (gettype($params) == 'string') {
                $params = array(array('message' => $params));
            }

            foreach ($params as $param) {
                if (!isset($param['message'])) {
                    continue;
                }
                $message = gettype($param['message']) == 'array' ?
                    print_r($param['message'], true) : $param['message'];
                $level = isset($param['level']) ? $param['level'] : null;
                $file = !empty($_file) ? $_file : self::LOG_FILE;
                if (isset($param['file']) && !empty($param['file'])) {
                    $file = $param['file'];
                }
                if (strpos($file, '.log') === false) {
                    $file .= '.log';
                }
                $forceLog = isset($param['forceLog']) ? $param['forceLog'] : false;

                Mage::log($message, $level, $file, $forceLog);
            }
        } elseif ($type == 'exception') {
            if (get_class($params) != 'Exception') {
                return false;
            }

            Mage::logException($params);
        }

        return true;
    }
}
