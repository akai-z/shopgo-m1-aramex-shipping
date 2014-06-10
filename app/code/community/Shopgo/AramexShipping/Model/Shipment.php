<?php

class Shopgo_AramexShipping_Model_Shipment
    extends Mage_Core_Model_Abstract
{
    const PRIORITY_DOCUMENT_EXPRESS = 'PDX';
    const PRIORITY_PARCEL_EXPRESS   = 'PPX';
    const PRIORITY_LETTER_EXPRESS   = 'PLX';
    const DEFERRED_DOCUMENT_EXPRESS = 'DDX';
    const DEFERRED_PARCEL_EXPRESS   = 'DPX';
    const GROUND_DOCUMENT_EXPRESS   = 'GDX';
    const GROUND_PARCEL_EXPRESS     = 'GPX';
    const ECONOMY_DOCUMENT_EXPRESS  = 'EDX';
    const ECONOMY_PARCEL_EXPRESS    = 'EPX';
    const EXPRESS                   = 'EXP';
    const DOMESTIC                  = 'DOM';
    const CREDIT_CARDS_DELIVERY     = 'CDS';
    const SS_CASH_ON_DELIVERY       = 'CODS';
    const PAYMENT_TYPE_PREPAID      = 'P';
    const PAYMENT_TYPE_COLLECT      = 'C';
    const PAYMENT_TYPE_THIRD_PARTY  = '3';
    const POUNDS                    = 'LB';
    const KILOGRAMS                 = 'KG';
    const CUBIC_CENTIMETER          = 'cm3';
    const CUBIC_INCH                = 'inch3';

    public function getCode($type, $code = '')
    {
        $codes = array(
            'unit_of_measure' => array(
                self::POUNDS    => Mage::helper('aramexshipping')->__('Pounds'),
                self::KILOGRAMS => Mage::helper('aramexshipping')->__('Kilograms')
            ),
            'unit_of_volume' => array(
                self::CUBIC_CENTIMETER => Mage::helper('aramexshipping')->__(self::CUBIC_CENTIMETER),
                self::CUBIC_INCH       => Mage::helper('aramexshipping')->__(self::CUBIC_INCH)
            )
        );

        if (!isset($codes[$type])) {
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }

        $code = strtoupper($code);
        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }

    public function isEnabled($checkDepends = true)
    {
        $helper = Mage::helper('aramexshipping');

        if ($checkDepends) {
            return $helper->getStoreConfigWithDependsFlag(
                Shopgo_AramexShipping_Helper_Data::CARRIERS_ARAMEX_SYSTEM_PATH . 'shipping_service',
                Shopgo_AramexShipping_Helper_Data::CARRIERS_ARAMEX_SYSTEM_PATH . 'active',
                1
            );
        }

        return $helper->getConfigData('shipping_service', 'carriers_aramex');
    }

    public function checkAccount($clientInfoSource)
    {
        $helper = Mage::helper('aramexshipping');

        $originData = $helper->getOriginSupplier('general_info');

        $params = array(
            'ClientInfo' => $helper->getClientInfo($clientInfoSource),
            'Transaction' => array(
                'Reference1' => '001'
            ),
            'OriginAddress' => array(
                'City'        => ucwords(strtolower($originData['city'])),
                'CountryCode' => $originData['country_code'],
                'PostCode'    => $originData['post_code']
            ),
            'DestinationAddress' => array(
                'City'        => 'New York',
                'CountryCode' => 'US',
                'PostCode'    => '10001'
            ),
            'ShipmentDetails' => array(
                'PaymentType'      => self::PAYMENT_TYPE_PREPAID,
                'ProductGroup'     => self::EXPRESS,
                'ProductType'      => self::PRIORITY_PARCEL_EXPRESS,
                'ActualWeight'     => array('Value' => 1, 'Unit' => self::POUNDS),
                'ChargeableWeight' => array('Value' => 1, 'Unit' => self::POUNDS),
                'NumberOfPieces'   => 1
            )
        );

        $soapResult = $helper->soapClient('rates_calculator', $params, array('trace' => 1));

        $result = array('valid' => 1, 'message' => 'Valid account information');

        if ($soapResult == '[SoapFault]') {
            $result = array('valid' => 0,
                'message' => 'Could not call service provider properly. If the issue presists, please report it to the extension author');
        } elseif ($soapResult->HasErrors) {
            $_message = $helper->getServiceErrorMessages($soapResult->Notifications->Notification);
            if (empty($_message)) {
                $_message = 'Uknown error has occured. If the issue persists, please report it to the extension author';
            }
            $result = array('valid' => 0, 'message' => $_message);
        }

        return $result;
    }

    public function calculateRate($requestData, $clientInfoSource = array())
    {
        $helper = Mage::helper('aramexshipping');

        if (!$helper->getConfigData('active', 'carriers_aramex')) {
            return false;
        }

        $productGroup = $requestData['countryId'] == $requestData['destCountryId'] ?
            self::DOMESTIC : self::EXPRESS;
        $productType = $productGroup == self::DOMESTIC ?
            self::CREDIT_CARDS_DELIVERY : $helper->getConfigData('product_type', 'carriers_aramex');

        $params = array(
            'ClientInfo' => $helper->getClientInfo($clientInfoSource),
            'Transaction' => array(
                'Reference1' => '001'
            ),
            'OriginAddress' => array(
                'City'        => ucwords(strtolower($requestData['city'])),
                'CountryCode' => strtoupper($requestData['countryId']),
                'PostCode'    => $requestData['postcode']
            ),
            'DestinationAddress' => array(
                'City'        => ucwords(strtolower($requestData['destCity'])),
                'CountryCode' => strtoupper($requestData['destCountryId']),
                'PostCode'    => $requestData['destPostcode']
            ),
            'ShipmentDetails' => array(
                'PaymentType'      => self::PAYMENT_TYPE_PREPAID,
                'ProductGroup'     => $productGroup,
                'ProductType'      => $productType,
                'ActualWeight'     => array('Value' => $requestData['packageWeight'], 'Unit' => $helper->getConfigData('unit_of_measure', 'carriers_aramex')),
                'ChargeableWeight' => array('Value' => $requestData['packageWeight'], 'Unit' => $helper->getConfigData('unit_of_measure', 'carriers_aramex')),
                'NumberOfPieces'   => $requestData['packageQty']
            )
        );

        $result = array(
            'error'    => false,
            'price'    => 0,
            'currency' => Mage::app()->getStore()->getBaseCurrencyCode()
        );

        $soapResult = $helper->soapClient('rates_calculator', $params, array('trace' => 1));

        $debugLog = array(
            array('message' => $helper->hideLogPrivacies($params)),
            array('message' => $soapResult)
        );
        $helper->debug($debugLog, 'aramex_calculate_rate');
        $helper->sendLogEmail(array('subject' => 'Calculate Rate Debug Log', 'content' => $debugLog));

        if ($soapResult != '[SoapFault]') {
            $result['error'] = $soapResult->HasErrors;
            $result['error_msg'] = $helper->getServiceErrorMessages($soapResult->Notifications->Notification);
            $result['price'] = $soapResult->TotalAmount->Value;
            $result['currency'] = $soapResult->TotalAmount->CurrencyCode;

            if (!$result['error']) {
                $conversion = $helper->convertRateCurrency($result['price'], $result['currency']);
                $result['price'] = $conversion['price'];
                $result['currency'] = $conversion['currency'];
            }
        } else {
            $result['error'] = true;
        }

        return $result;
    }

    public function getRatesAndPackages($object, $returnRates = true, $destinationData = array())
    {
        $helper = Mage::helper('aramexshipping');
        $quoteItems = null;
        $localItems = array();
        $packages = array('origin' => array());
        $suppliers = $helper->getSuppliersCollection();
        $ratesTotal = 0;

        switch (get_class($object)) {
            case 'Mage_Sales_Model_Quote':
                $quoteType = 'quote';
                $quoteItems = $object->getAllVisibleItems();
                break;
            case 'Mage_Sales_Model_Order':
                $quoteType = 'order';
                $quoteItems = $object->getAllItems();
                break;
            case 'Mage_Sales_Model_Order_Shipment':
                $quoteType = 'shipment';
                $quoteItems = $object->getAllItems();
                break;
            default:
                return;
        }

        if (!$destinationData) {
            $destinationData = $object->getShippingAddress()->getData();
        }

        foreach ($quoteItems as $item) {
            $localItems[] = $item;
            $itemSupplier = Mage::getModel('catalog/product')->load($item->getProductId())->getAramexSupplier();

            foreach ($suppliers as $supplier) {
                if ($itemSupplier == $supplier->getId()) {
                    $_qty = $quoteType == 'order' ? $item->getQtyOrdered() : $item->getQty();
                    if (($_qty - $item->getQtyShipped()) == 0) {
                        continue;
                    }

                    if (!isset($packages[$itemSupplier])) {
                        $packages[$itemSupplier] = array();
                    }
                    if (!isset($packages[$itemSupplier]['supplier'])) {
                        $packages[$itemSupplier]['supplier'] = $supplier->getData();
                    }
                    if (!isset($packages[$itemSupplier]['price'])) {
                        $packages[$itemSupplier]['price'] = 0;
                    }
                    if (!isset($packages[$itemSupplier]['real_price'])) {
                        $packages[$itemSupplier]['real_price'] = 0;
                    }
                    if (!isset($packages[$itemSupplier]['weight'])) {
                        $packages[$itemSupplier]['weight'] = 0;
                    }
                    if (!isset($packages[$itemSupplier]['qty'])) {
                        $packages[$itemSupplier]['qty'] = 0;
                    }

                    $qty = $quoteType == 'order' ? $item->getQtyOrdered() : $item->getQty();

                    $packages[$itemSupplier]['price']      += $item->getBasePrice();
                    $packages[$itemSupplier]['real_price'] += $item->getBasePrice();
                    $packages[$itemSupplier]['weight']     += $item->getWeight() * $qty;
                    $packages[$itemSupplier]['qty']        += $qty;

                    if ($quoteType == 'order') {
                        $packages[$itemSupplier]['qty_invoiced'] += $item->getQtyInvoiced();
                    }

                    if ($item->getQtyShipped()) {
                        $packages[$itemSupplier]['qty_shipped'] += $item->getQtyShipped();
                    }

                    if ($quoteType == 'order' || $item->getParentItemId()) {
                        $packages[$itemSupplier]['items'][$item->getParentItemId()]['qty'] = $item->getQtyOrdered();
                        $packages[$itemSupplier]['items'][$item->getParentItemId()]['qty_invoiced'] = $item->getQtyInvoiced();
                        if ($item->getQtyShipped()) {
                            $packages[$itemSupplier]['items'][$item->getParentItemId()]['qty_shipped'] = $item->getQtyShipped();
                        }
                    } else {
                        $_itemId = $quoteType == 'shipment' ? $item->getOrderItemId() : $item->getId();
                        $packages[$itemSupplier]['items'][$_itemId]['qty'] =
                            $quoteType == 'order' ? $item->getQtyOrdered() : $item->getQty();
                        if ($quoteType == 'order') {
                            $packages[$itemSupplier]['items'][$_itemId]['qty_invoiced'] = $item->getQtyInvoiced();
                        }
                        if ($item->getQtyShipped()) {
                            $packages[$itemSupplier]['items'][$_itemId]['qty_shipped'] = $item->getQtyShipped();
                        }
                    }

                    if (count($localItems) <= 1) {
                        $localItems = array();
                    } else {
                        array_pop($localItems);
                    }

                    break;
                }
            }
        }

        foreach ($localItems as $item) {
            $_qty = $quoteType == 'order' ? $item->getQtyOrdered() : $item->getQty();
            if (($_qty - $item->getQtyShipped()) == 0) {
                continue;
            }

            if (!isset($packages['origin']['supplier'])) {
                $packages['origin']['supplier'] = $helper->getOriginSupplier();
            }
            if (!isset($packages['origin']['price'])) {
                $packages['origin']['price'] = 0;
            }
            if (!isset($packages['origin']['real_price'])) {
                $packages['origin']['real_price'] = 0;
            }
            if (!isset($packages['origin']['weight'])) {
                $packages['origin']['weight'] = 0;
            }
            if (!isset($packages['origin']['qty'])) {
                $packages['origin']['qty'] = 0;
            }

            $qty = $quoteType == 'order' ? $item->getQtyOrdered() : $item->getQty();

            $packages['origin']['price']      += $item->getBasePrice();
            $packages['origin']['real_price'] += $item->getBasePrice();
            $packages['origin']['weight']     += $item->getWeight() * $qty;
            $packages['origin']['qty']        += $qty;

            if ($quoteType == 'order') {
                $packages['origin']['qty_invoiced'] += $item->getQtyInvoiced();
            }

            if ($item->getQtyShipped()) {
                $packages['origin']['qty_shipped'] += $item->getQtyShipped();
            }

            if ($quoteType == 'order' || $item->getParentItemId()) {
                $packages['origin']['items'][$item->getParentItemId()]['qty'] = $item->getQtyOrdered();
                $packages['origin']['items'][$item->getParentItemId()]['qty_invoiced'] = $item->getQtyInvoiced();
                if ($item->getQtyShipped()) {
                    $packages['origin']['items'][$item->getParentItemId()]['qty_shipped'] = $item->getQtyShipped();
                }
            } else {
                $_itemId = $quoteType == 'shipment' ? $item->getOrderItemId() : $item->getId();
                $packages['origin']['items'][$_itemId]['qty'] =
                    $quoteType == 'order' ? $item->getQtyOrdered() : $item->getQty();
                if ($quoteType == 'order') {
                    $packages['origin']['items'][$_itemId]['qty_invoiced'] = $item->getQtyInvoiced();
                }
                if ($item->getQtyShipped()) {
                    $packages['origin']['items'][$_itemId]['qty_shipped'] = $item->getQtyShipped();
                }
            }
        }

        if (empty($packages['origin'])) {
            unset($packages['origin']);
        }

        foreach ($packages as $k => $v) {
            $ratePrice = 0;

            $requestData = array(
                'city'          => $v['supplier']['city'],
                'countryId'     => $v['supplier']['country_code'],
                'postcode'      => $v['supplier']['post_code'],
                'destCity'      => ucwords(strtolower($destinationData['city'])),
                'destCountryId' => strtoupper($destinationData['country_id']),
                'destPostcode'  => $destinationData['postcode'],
                'packageWeight' => $v['weight'],
                'packageQty'    => $v['qty']
            );

            if ($k == 'origin') {
                $rateRequest = $this->calculateRate($requestData, $v['supplier']);
                if ($rateRequest['error']) {
                    return $rateRequest;
                }
            } else {
                $rateRequest = $this->calculateRate($requestData, $v['supplier']);
                if ($rateRequest['error']) {
                    return $rateRequest;
                }
            }

            $ratesTotal += $rateRequest['price'];
            $packages[$k]['shipmentFees'] = array(
                'value'    => $rateRequest['price'],
                'currency' => $rateRequest['currency']
            );
            $packages[$k]['price'] += $rateRequest['price'];
        }

        $debugLog = array(
            array('log' => $helper->hideLogPrivacies($packages, true)),
            array('quote_type' => $quoteType)
        );
        $helper->debug($debugLog, 'aramex_shipment_packages');
        $helper->sendLogEmail(array('subject' => 'Shipment Packages Debug Log', 'content' => $debugLog));

        Mage::getSingleton('checkout/session')->setShipmentsPackages($packages);
        Mage::getSingleton('checkout/session')->setQuoteType($quoteType);

        if ($returnRates) {
            return array('error' => false, 'price' => $ratesTotal);
        }
    }

    public function prepareShipment($object, $aramexData = array())
    {
        $helper = Mage::helper('aramexshipping');

        $shipment = null;
        $invoice = null;
        $order = null;
        $result = false;

        switch (get_class($object)) {
            case 'Mage_Sales_Model_Order':
                $order = $object;
                break;
            case 'Mage_Sales_Model_Order_Shipment':
                $shipment = $object;
                $order = Mage::getModel('sales/order')->load($shipment->getOrder()->getId());
                break;
            case 'Mage_Sales_Model_Order_Invoice':
                $invoice = $object;
                $order = $invoice->getOrder();
                break;
            default:
                return false;
        }

        if (!$helper->getConfigData('active', 'carriers_aramex')
            || !$helper->getConfigData('shipping_service', 'carriers_aramex')
            || $order->getShippingCarrier()->getCarrierCode() != 'aramex'
            || !$order->canShip()) {
            return;
        }

        if (Mage::registry('skip_shipment_save_after')) {
            Mage::unregister('skip_shipment_save_after');
            return;
        }

        if (isset($aramexData['shipment'])) {
            if (Mage::registry('ship_form_aramex_shipment_data')) {
                Mage::unregister('ship_form_aramex_shipment_data');
            }
            Mage::register('ship_form_aramex_shipment_data', $aramexData['shipment']);
        }

        if (isset($aramexData['pickup'])) {
            if (Mage::registry('ship_form_aramex_pickup_data')) {
                Mage::unregister('ship_form_aramex_pickup_data');
            }
            Mage::register('ship_form_aramex_pickup_data', $aramexData['pickup']);
        }

        $this->getRatesAndPackages($shipment, false);

        $packages = Mage::getSingleton('checkout/session')->getShipmentsPackages();
        $quoteType = Mage::getSingleton('checkout/session')->getQuoteType();

        if (empty($quoteType)) {
            $quoteType = 'order';
        }

        if (empty($packages)) {
            return false;
        }

        $destinationData = $order->getShippingAddress()->getData();

        foreach ($packages as $package) {
            $result = $this->_createShipment($object, $package, $destinationData, $quoteType);
            if (!$result) {
                return $result;
            }
        }

        $this->_sendShipmentEmail();

        Mage::getSingleton('checkout/session')->unsShipmentsPackages();
        Mage::getSingleton('checkout/session')->unsQuoteType();
        if (Mage::registry('ship_form_aramex_pickup_data')) {
            Mage::unregister('ship_form_aramex_pickup_data');
        }

        return $result;
    }

    public function getShipmentData($supplierData, $price, $qty, $weight, $destinationData, $order, $isPickup = false)
    {
        $helper = Mage::helper('aramexshipping');

        $clientInfo = $helper->getClientInfo($supplierData);

        $services = '';

        if ($helper->getConfigData('cod', 'carriers_aramex')) {
            $codMethods = $helper->getConfigData('cod_methods', 'carriers_aramex');

            if (gettype($codMethods) == 'string') {
                $codMethods = array($codMethods);
            }

            if (in_array($order->getPayment()->getMethodInstance()->getCode(), $codMethods)) {
                $services = self::SS_CASH_ON_DELIVERY;
            }
        }

        $productGroup = $supplierData['country_code'] == $destinationData['country_id'] ?
            self::DOMESTIC : self::EXPRESS;
        $productType = $productGroup == self::DOMESTIC ?
            self::CREDIT_CARDS_DELIVERY : $helper->getConfigData('product_type', 'carriers_aramex');

        $shipFormData = array();

        if (Mage::registry('ship_form_aramex_shipment_data')) {
            $shipFormData = Mage::registry('ship_form_aramex_shipment_data');
            Mage::unregister('ship_form_aramex_shipment_data');
        }

        $shippingDateTime = date('c', time());
        $dueDate = '';

        if (isset($shipFormData['shipping_date'])) {
            $shippingDateTime = date('c', strtotime($shipFormData['shipping_date']));
        }

        if (isset($shipFormData['due_date'])) {
            $dueDate = date('c', strtotime($shipFormData['due_date']));
        }

        $params = array(
            'Shipments' => array(
                'Shipment' => array(
                    'Shipper' => array(
                        'AccountNumber' => $clientInfo['AccountNumber'],
                        'PartyAddress' => array(
                            'Line1'               => $supplierData['address_line1'],
                            'Line2'               => $supplierData['address_line2'],
                            'Line3'               => $supplierData['address_line3'],
                            'City'                => ucwords(strtolower($supplierData['city'])),
                            'StateOrProvinceCode' => $supplierData['state_or_province_code'],
                            'PostCode'            => $supplierData['post_code'],
                            'CountryCode'         => strtoupper($supplierData['country_code']),
                        ),
                        'Contact' => array(
                            'Department'      => $supplierData['department'],
                            'PersonName'      => $supplierData['person_name'],
                            'Title'           => $supplierData['person_title'],
                            'CompanyName'     => $supplierData['company_name'],
                            'PhoneNumber1'    => $supplierData['phone_number1'],
                            'PhoneNumber1Ext' => $supplierData['phone_number1_ext'],
                            'PhoneNumber2'    => $supplierData['phone_number2'],
                            'PhoneNumber2Ext' => $supplierData['phone_number2_ext'],
                            'FaxNumber'       => $supplierData['fax_number'],
                            'CellPhone'       => $supplierData['cellphone'],
                            'EmailAddress'    => $supplierData['email'],
                            'Type'            => $supplierData['type']
                        ),
                    ),
                    'Consignee' => array(
                        'AccountNumber' => '',
                        'PartyAddress'  => array(
                            'Line1'               => preg_replace("/\r\n|\n|\r/", ' ', $destinationData['street']),
                            'Line2'               => '',
                            'Line3'               => '',
                            'City'                => ucwords(strtolower($destinationData['city'])),
                            'StateOrProvinceCode' => $destinationData['region'],
                            'PostCode'            => $destinationData['postcode'],
                            'CountryCode'         => strtoupper($destinationData['country_id'])
                        ),
                        'Contact' => array(
                            'Department'      => '',
                            'PersonName'      => $destinationData['firstname'] . ' ' . $destinationData['lastname'],
                            'Title'           => $destinationData['title'],
                            'CompanyName'     => $destinationData['firstname'] . ' ' . $destinationData['lastname'],
                            'PhoneNumber1'    => $destinationData['telephone'],
                            'PhoneNumber1Ext' => '',
                            'PhoneNumber2'    => '',
                            'PhoneNumber2Ext' => '',
                            'FaxNumber'       => $destinationData['fax'],
                            'CellPhone'       => $destinationData['telephone'],
                            'EmailAddress'    => $destinationData['email'],
                            'Type'            => ''
                        ),
                    ),
                    'TransportType'          => 0,
                    'ShippingDateTime'       => $shippingDateTime,
                    'DueTime'                => $dueDate,
                    'PickupLocation'         => '',
                    'PickupGUID'             => '',
                    'Comments'               => '',
                    'AccountingInstrcutions' => '',
                    'OperationsInstructions' => '',
                    'Details' => array(
                        'ActualWeight' => array(
                            'Value' => $weight,
                            'Unit'  => $helper->getConfigData('unit_of_measure', 'carriers_aramex')
                        ),
                        'ProductGroup'       => $productGroup,
                        'ProductType'        => $productType,
                        'PaymentType'        => self::PAYMENT_TYPE_PREPAID,
                        'PaymentOptions'     => '',
                        'Services'           => $services,
                        'NumberOfPieces'     => $qty,
                        'DescriptionOfGoods' => $shipFormData['goods_description'],
                        'GoodsOriginCountry' => strtoupper($supplierData['country_code']),
                        'Items' => array()
                    ),
                ),
            )
        );

        if ($productGroup == self::EXPRESS) {
            $params['Shipments']['Shipment']['Details']['CustomsValueAmount'] = array(
                'Value' => $helper->currencyConvert(
                    $price, $baseCurrencyCode,
                    'USD', 'price', 2
                ),
                'CurrencyCode' => 'USD'
            );
        }

        if (!$isPickup) {
            $params['ClientInfo']  = $clientInfo;
            $params['Transaction'] = array(
                'Reference1' => '001',
                'Reference2' => '',
                'Reference3' => '',
                'Reference4' => '',
                'Reference5' => ''
            );
            $params['LabelInfo']  = array(
                'ReportID'   => 9201,
                'ReportType' => 'URL',
            );
        }

        if ($services == 'CODS') {
            $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
            $codPrice = $price;
            if ($baseCurrencyCode != 'USD') {
                $codPrice = $helper->currencyConvert(
                    $price, $baseCurrencyCode,
                    'USD', 'price', 2
                );
            }
            $params['Shipments']['Shipment']['Details']['CashOnDeliveryAmount'] = array(
                'Value' => $codPrice,
                'CurrencyCode' => 'USD'
            );
        }

        $params['Shipments']['Shipment']['Details']['Items'][] = array(
            'PackageType' => 'Box',
            'Quantity'    => $qty,
            'Weight' => array(
                'Value' => $weight,
                'Unit'  => $helper->getConfigData('unit_of_measure', 'carriers_aramex')
            ),
            'Comments'  => '-',
            'Reference' => ''
        );

        return $params;
    }

    private function _createShipment($object, $package, $destinationData, $quoteType = 'order')
    {
        $helper = Mage::helper('aramexshipping');

        $shipment = null;
        $order = null;
        $result = false;
        $pickupModel = Mage::getModel('aramexshipping/pickup');
        $service = 'shipping_service';

        switch (get_class($object)) {
            case 'Mage_Sales_Model_Order':
                $order = $object;
                break;
            case 'Mage_Sales_Model_Order_Shipment':
                $shipment = $object;
                $order = $shipment->getOrder();
                break;
            default:
                return false;
        }

        $supplierData = $package['supplier'];
        $serviceData  = array();
        $pickupUsed   = false;
        $packagePrice = $package['price'];

        if ($supplierData['country_code'] != $destinationData['country_id']) {
            $packagePrice = $package['real_price'];
        }

        if ($pickupModel->isEnabled()) {
            if (!Mage::registry('ship_form_aramex_pickup_data')) {
                return;
            }

            $pickupData = Mage::registry('ship_form_aramex_pickup_data');
            $pickupUsed = $pickupData['enabled'];

            if ($pickupUsed) {
                $service = 'pickup_create_service';
                $shipmentData = $this->getShipmentData(
                    $supplierData, $packagePrice,
                    $package['qty'], $package['weight'],
                    $destinationData, $order, true
                );
                $serviceData = $pickupModel->getPickupData(
                    Mage::registry('ship_form_aramex_pickup_data'),
                    $shipmentData['Shipments'],
                    $package['supplier']
                );
            } else {
                $serviceData = $this->getShipmentData(
                    $supplierData, $packagePrice,
                    $package['qty'], $package['weight'],
                    $destinationData, $order
                );
            }

            Mage::unregister('ship_form_aramex_pickup_data');
        } else {
            $serviceData = $this->getShipmentData(
                $supplierData, $packagePrice,
                $package['qty'], $package['weight'],
                $destinationData, $order
            );
        }

        $serviceResult = $helper->soapClient(
            $service,
            $serviceData
        );

        $debugLog = array(
            array('message' => $helper->hideLogPrivacies($serviceData)),
            array('message' => $serviceResult)
        );
        $helper->debug($debugLog, 'aramex_create_shipment');
        $helper->sendLogEmail(array('subject' => 'Create Shipment Debug Log', 'content' => $debugLog));

        $helper->notifySupplier(
            $supplierData['email'],
            array('supplier' => $supplierData['person_name'], 'order_no' => $order->getIncrementId())
        );

        if ($serviceResult != '[SoapFault]') {
            $processedShipment = null;
            $notifications = null;

            if ($pickupModel->isEnabled() && $pickupUsed) {
                $processedPickup = $serviceResult->ProcessedPickup;
                $processedShipment = $processedPickup->ProcessedShipments->ProcessedShipment;
                $notifications = $serviceResult->Notifications->Notification;
                if (Mage::registry('aramex_shipment_processed_pickup_data')) {
                    Mage::unregister('aramex_shipment_processed_pickup_data');
                }
                Mage::register('aramex_shipment_processed_pickup_data', array(
                    'pickup' => $processedPickup,
                    'supplier_id' => isset($package['supplier']['id']) ? $package['supplier']['id'] : 0
                ));
            } else {
                $processedShipment = $serviceResult->Shipments->ProcessedShipment;
                $notifications = $processedShipment->Notifications->Notification;
            }

            $error = $serviceResult->HasErrors;
            $errorMsg = $helper->getServiceErrorMessages($notifications);

            if (!$error) {
                $trackingNo = null;

                if (Mage::getModel('aramexshipping/carrier_aramex')->isTrackingAvailable()
                    && $helper->getConfigData('auto_tracking_no', 'carriers_aramex')) {
                    $trackingNo = $processedShipment->ID;
                }

                $shipmentInfo = array(
                    'items'         => $package['items'],
                    'shipmentFees'  => $package['shipmentFees'],
                    'shipmentLabel' => $processedShipment->ShipmentLabel->LabelURL,
                    'supplier'      => array(
                        'id'    => isset($supplierData['id']) ? $supplierData['id'] : 0,
                        'name'  => $supplierData['person_name'],
                        'email' => $supplierData['email']
                    )
                );

                if (isset($supplierData['identifier'])) {
                    $shipmentInfo['supplier']['identifier'] = $supplierData['identifier'];
                }

                if (isset($codPrice)) {
                    $shipmentInfo['cod'] = $codPrice;
                }

                $result = $this->_saveShipment($object, $shipmentInfo, $trackingNo, $quoteType);
            } else {
                $serviceName = $pickupModel->isEnabled() ? 'Pickup' : 'Shipment';
                $helper->log($errorMsg, '', 'aramex_create_shipment');
                $userMsg = $helper->__($serviceName . ' could not be created, please contact us to know more about this issue.<br/>Error:&nbsp;%s', $errorMsg);
                $helper->userMessage($userMsg, 'error');
                $helper->sendLogEmail(array('subject' => 'Create ' . $serviceName . ' Error Log', 'content' => $errorMsg));
            }
        }

        return $result;
    }

    public function getTracking($trackingNbs)
    {
        $helper = Mage::helper('aramexshipping');

        $params = array(
            'ClientInfo' => $helper->getClientInfo(),
            'Transaction' => array(
                'Reference1' => '001'
            ),
            'Shipments' => $trackingNbs
        );

        $result = $helper->soapClient('tracking_service', $params);

        $debugLog = array(
            array('message' => $helper->hideLogPrivacies($params)),
            array('message' => $result)
        );
        $helper->debug($debugLog, 'aramex_tracking');
        $helper->sendLogEmail(array('subject' => 'Tracking Debug Log', 'content' => $debugLog));

        return $result;
    }

    private function _saveShipment($object, $shipmentInfo, $trackingNo = null, $quoteType = 'order')
    {
        $result = false;
        $helper = Mage::helper('aramexshipping');
        $objectType = null;
        $shipment = null;
        $order = null;

        switch (get_class($object)) {
            case 'Mage_Sales_Model_Order':
                $objectType = 'order';
                $order = $object;
                break;
            case 'Mage_Sales_Model_Order_Shipment':
                $objectType = 'shipment';
                $shipment = $object;
                break;
            default:
                return false;
        }

        try {
            $qty = array();

            if ($objectType == 'order') {
                if ($quoteType == 'quote') {
                    $orderItems = $order->getItemsCollection();
                    foreach ($orderItems as $oi) {
                        foreach ($shipmentInfo['items'] as $itemId => $itemQty) {
                            if ($itemId == $oi->getQuoteItemId()) {
                                $qty[$oi->getId()] = $itemQty;
                                break;
                            }
                        }
                    }
                } else {
                    foreach ($shipmentInfo['items'] as $itemId => $itemQty) {
                        $qty[$itemId] = $itemQty;
                    }
                }

                $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($qty);
            } else {
                $order = $shipment->getOrder();
            }

            if ($trackingNo != null) {
                $trackingData = array(
                    'carrier_code' => $order->getShippingCarrier()->getCarrierCode(),
                    'title'        => $order->getShippingCarrier()->getConfigData('title'),
                    'number'       => $trackingNo
                );
                $track = Mage::getModel('sales/order_shipment_track')->addData($trackingData);
                $shipment->addTrack($track);
            }

            $shipment->setAramexShipmentData(serialize(array(
                'is_shipped'  => true,
                'supplier_id' => $shipmentInfo['supplier']['id']
            )));

            if ($objectType == 'order') {
                $shipment->register();
            }

            $baseCurrencySymbol = Mage::app()->getLocale()->currency(
                Mage::app()->getStore()->getBaseCurrencyCode())->getSymbol();
            $currentCurrencySymbol = Mage::app()->getLocale()->currency(
                Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();

            $shipmentFees = array(
                'base' => array(
                    'value' => $helper->currencyConvert(
                        $shipmentInfo['shipmentFees']['value'],
                        $shipmentInfo['shipmentFees']['currency'],
                        '_BASE_', 'price', 2
                     ),
                    'currency' => $baseCurrencySymbol
                )
            );

            $displayPrice = '';
            if ($baseCurrencySymbol != $currentCurrencySymbol) {
                $shipmentFees['current'] = array(
                    'value' => $helper->currencyConvert(
                        $shipmentInfo['shipmentFees']['value'],
                        $shipmentInfo['shipmentFees']['currency'],
                        '_CURRENT_', 'price', 2
                    ),
                    'currency' => $currentCurrencySymbol
                );

                $displayPrice = '&nbsp;[' . $shipmentFees['current']['currency'] . $shipmentFees['current']['value'] . ']';
            }

            $comments = array(
                'supplier' =>
                    sprintf(
                        '<strong>' . $helper->__('This shipment is supplied by') . ':</strong>&nbsp;%s&nbsp;(%s)',
                        $shipmentInfo['supplier']['name'], $shipmentInfo['supplier']['email']
                    ),
                'shipmentFees' =>
                    sprintf(
                        '<strong>' . $helper->__('Shipment Fees') . ':</strong>&nbsp;%s%g%s',
                        $shipmentFees['base']['currency'], $shipmentFees['base']['value'],
                        $displayPrice
                    ),
                'cod' => '',
                'shipmentLabel' =>
                    '<strong>' . $helper->__('Shipment Label') . ':</strong>&nbsp;' . $shipmentInfo['shipmentLabel']
            );

            if (isset($shipmentInfo['cod'])) {
                $cod = array(
                    'base' => array(
                        'value' => $helper->currencyConvert(
                            $shipmentInfo['cod'],
                            'USD', '_BASE_', 'price', 2
                        ),
                        'currency' => $baseCurrencySymbol
                    )
                );

                $displayPrice = '';
                if ($baseCurrencySymbol != $currentCurrencySymbol) {
                    $cod['current'] = array(
                        'value' => $helper->currencyConvert(
                            $shipmentInfo['cod'],
                            'USD', '_CURRENT_', 'price', 2
                        ),
                        'currency' => $currentCurrencySymbol
                    );

                    $displayPrice = '&nbsp;[' . $cod['current']['currency'] . $cod['current']['value'] . ']';
                }

                $comments['cod'] = sprintf(
                    '<strong>' . $helper->__('Cash on Delivery applied') . ':</strong>&nbsp;%s%g%s',
                    $cod['base']['currency'], $cod['base']['value'], $displayPrice
                );
            } else {
                unset($comments['cod']);
            }

            foreach ($comments as $comment) {
                $shipment->addComment($comment, false, false);
            }

            Mage::register('skip_shipment_save_after', true);

            if ($objectType == 'order') {
                $shipment->getOrder()->setIsInProcess(true);
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($order)
                    ->save();
            } else {
                //$shipment->save();
                $shipment->getOrder()->setIsInProcess(true);
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();
            }

            $processedPickup = Mage::registry('aramex_shipment_processed_pickup_data');

            if (Mage::getModel('aramexshipping/pickup')->isEnabled()
                && !empty($processedPickup['pickup'])) {
                Mage::getModel('aramexshipping/pickup')
                    ->setGuid($processedPickup['pickup']->GUID)
                    ->setShipmentIncrementIds(serialize(array($shipment->getIncrementId())))
                    ->setSupplierId($processedPickup['supplier_id'])
                    ->save();

                Mage::unregister('aramex_shipment_processed_pickup_data');
            }

            if (!Mage::registry('aramex_shipment')) {
                Mage::register('aramex_shipment', $shipment);
            }

            $comments = '<br/><br/>' . implode('<br/>', $comments);

            if ($shipmentEmailComments = Mage::registry('aramex_shipments_comments')) {
                Mage::unregister('aramex_shipments_comments');
                Mage::register('aramex_shipments_comments', $shipmentEmailComments . $comments);
            } else {
                Mage::register('aramex_shipments_comments', $comments);
            }

            $result = true;
        } catch (Exception $e) {
            $result = false;
            $helper->log($e, 'exception');
            $helper->log(
                array('message' => $helper->__('Shipment could not be created (Saved), check exception log.')),
                '', 'aramex_create_shipment'
            );
            $helper->userMessage(
                $helper->__('Shipment could not be created, please contact us to look into the issue.'), 'error'
            );
            $helper->sendLogEmail(array('subject' => 'Save Shipment Error Log', 'content' => $e->getMessage()));
        }

        return $result;
    }

    private function _sendShipmentEmail()
    {
        $helper = Mage::helper('aramexshipping');

        try {
            if ($shipment = Mage::registry('aramex_shipment')) {
                if ($shipment->getOrder()->getCustomerEmail() && !$shipment->getEmailSent()) {
                    if ($comments = Mage::registry('aramex_shipments_comments')) {
                        $comments = '<br/><strong>' . $helper->__('Comments') . ':</strong>'
                                  . $comments;
                        $shipment->sendEmail(true, $comments);
                        $shipment->setEmailSent(true);
                        Mage::unregister('aramex_shipments_comments');
                    }

                    Mage::unregister('aramex_shipment');

                    return true;
                }
                Mage::unregister('aramex_shipment');
            }
        } catch (Exception $e) {
            $helper->log($e, 'exception');
            $helper->log(
                array('message' => $helper->__('Shipment email could not be sent, check exception log.')),
                '', 'aramex_create_shipment'
            );
            $helper->userMessage(
                $helper->__('Shipment email could not be sent, please contact us to look into the issue.'), 'error'
            );
            $helper->sendLogEmail(array('subject' => 'Save Shipment Error Log', 'content' => $e->getMessage()));
        }

        return false;
    }
}
