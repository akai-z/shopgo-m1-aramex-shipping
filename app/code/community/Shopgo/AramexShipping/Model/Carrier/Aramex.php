<?php

class Shopgo_AramexShipping_Model_Carrier_Aramex
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code         = 'aramex';
    protected $_standardCode = 'standard';
    protected $_codCode      = 'cod';
    protected $_aramexFreeShippingCode = 'aramex_free';
    private $_isAramexFreeShipping = false;

    protected $_result = null;


    public function getMethodsCodes($code = '')
    {
        $helper = Mage::helper('aramexshipping');
        $result = '';

        $codes = array(
            $this->_standardCode => $helper->__('Standard'),
            $this->_codCode      => $helper->__('Cash on Delivery'),
            $this->_aramexFreeShippingCode => $helper->__('Free Shipping via Aramex')
        );

        switch (true) {
            case !isset($codes[$code]) && !empty($code):
                break;
            case isset($codes[$code]):
                $result = $codes[$code];
                break;
            default:
                $result = $codes;
        }

        return $result;
    }

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!Mage::getStoreConfig('carriers/' . $this->_code . '/active')) {
            return false;
        }

        $helper = Mage::helper('aramexshipping');
        $result = Mage::getModel('shipping/rate_result');

        if (
            $this->getConfigData('aramex_free_shipping')
            && $this->getConfigData('aramex_free_shipping_sallowspecific')
        ) {
            $aramexFreeShippingSpecificCountries = explode(',', $this->getConfigData('aramex_free_shipping_specificcountry'));
            $this->_isAramexFreeShipping = in_array($request->getDestCountryId(), $aramexFreeShippingSpecificCountries)
                ? true: false;
        } else {
            $this->_isAramexFreeShipping = false;
        }

        $this->_updateFreeMethodQuote($request);

        if ($request->getFreeShipping()
            || ($this->getConfigData('free_shipping_enable')
                && $request->getBaseSubtotalInclTax() >=
                $this->getConfigData('free_shipping_subtotal'))
        ) {
            if ($this->_isAramexFreeShipping) {
                $result->append(
                    $this->_getAramexFreeShippingResult()
                );

                return $result;
            } else {
                return false;
            }
        }

        $destinationData = array(
            'city'       => ucwords(strtolower($request->getDestCity())),
            'country_id' => $request->getDestCountryId(),
            'street'     => $request->getDestStreet(),
            'postcode'   => $request->getDestPostcode()
        );

        $session = Mage::app()->getStore()->isAdmin()
            ? Mage::getSingleton('adminhtml/session_quote')
            : Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();

        $result->append(
            $this->_getStandardRateResult($quote, $destinationData)
        );

        if ($helper->getConfigData('cod', 'carriers_aramex')
            && (
                $helper->isCodAccountSet()
                || Mage::getModel('aramexshipping/supplier')->isSupplierCodAccountSet($quote)
            )) {
            $result->append(
                $this->_getCodRateResult($quote, $destinationData)
            );
        }

        return $result;
    }

    private function _getStandardRateResult($quote, $destinationData)
    {
        $rateResult = $this->_getRatesAndPackages($quote, $destinationData);
        $method     = $this->getMethodsCodes($this->_standardCode);

        $result = $this->_getRateResult(
            $rateResult['price'],
            $this->_standardCode,
            $method,
            array(
                'status'  => $rateResult['error'],
                'message' => $rateResult['error_msg'],
                'aramex_message' => $rateResult['aramex_error_msg']
            )
        );

        return $result;
    }

    private function _getCodRateResult($quote, $destinationData)
    {
        $rateResult = $this->_getRatesAndPackages($quote, $destinationData, $this->_codCode);
        $method     = $this->getMethodsCodes($this->_codCode);

        $result = $this->_getRateResult(
            $rateResult['price'],
            $this->_codCode,
            $method,
            array(
                'status'  => $rateResult['error'],
                'message' => $rateResult['error_msg'],
                'aramex_message' => $rateResult['aramex_error_msg']
            )
        );

        return $result;
    }

    private function _getAramexFreeShippingResult()
    {
        $method = $this->getMethodsCodes($this->_aramexFreeShippingCode);
        $result = $this->_getRateResult(
            0,
            $this->_aramexFreeShippingCode,
            $method,
            array(
                'status' => false,
            )
        );

        return $result;
    }

    private function _getRatesAndPackages($quote, $destinationData, $method = '')
    {
        $helper = Mage::helper('aramexshipping');

        $result = Mage::getModel('aramexshipping/shipment')
            ->getRatesAndPackages($quote, true, $destinationData, $method);

        if (isset($result['error_msg'])) {
            $result['error_msg'] = $result['aramex_error_msg'] = 'Aramex Error: ' . $result['error_msg'];

            if (!$helper->getConfigData('aramex_error', 'carriers_aramex')) {
                $result['error_msg'] = $this->getConfigData('specificerrmsg');
            }
        } else {
            $result['error_msg'] = $this->getConfigData('specificerrmsg');
        }

        return $result;
    }

    private function _getRateResult($price, $methodCode, $methodTitle, $_error)
    {
        $helper = Mage::helper('aramexshipping');
        $result = null;

        if ((isset($_error['status']) && !$_error['status'])
            && ($price > 0 || $this->_isAramexFreeShipping)) {
            $method = Mage::getModel('shipping/rate_result_method');

            $method->setCarrier($this->_code);
            $method->setMethod($methodCode);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethodTitle($methodTitle);
            $method->setPrice($price);
            $method->setCost($price);

            $result = $method;
        } else {
            $error = Mage::getModel('shipping/rate_result_error');

            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($_error['message']);

            $result = $error;

            if ($_error['aramex_message']) {
                $helper->log($_error['aramex_message'], '', 'aramex_collect_rates');
                $helper->sendLogEmail(
                    array('subject' => 'Collect Rates Error Log', 'content' => $_error['aramex_message'])
                );
            }
        }

        return $result;
    }

    public function getAllowedMethods()
    {
        $helper = Mage::helper('aramexshipping');

        return array(
            $this->_standardCode => $helper->__('Standard'),
            $this->_codCode      => $helper->__('Cash on Delivery'),
            $this->_aramexFreeShippingCode => $helper->__('Free Shipping via Aramex')
        );
    }

    public function isTrackingAvailable()
    {
        return $this->getConfigData('tracking_service');
    }

    public function isShippingLabelsAvailable()
    {
        return false;
    }

    public function isCityRequired()
    {
        return true;
    }

    public function isZipCodeRequired($countryId = null)
    {
        if ($countryId != null) {
            return !Mage::helper('directory')->isZipCodeOptional($countryId);
        }
        return true;
    }

    public function isGirthAllowed($countyDest = null) {
        return false;
    }

    public function getTrackingInfo($tracking)
    {
        if (!$this->isTrackingAvailable()) {
            return false;
        }

        $info = array();

        $result = $this->getTracking($tracking);

        if($result instanceof Mage_Shipping_Model_Tracking_Result){
            if ($trackings = $result->getAllTrackings()) {
                return $trackings;
            }
        } elseif (is_string($result) && !empty($result)) {
            return $result;
        }

        return false;
    }

    public function getTracking($trackings)
    {
        if (!is_array($trackings)) {
            $trackings = array($trackings);
        }

        $response = Mage::getModel('aramexshipping/shipment')->getTracking($trackings);

        if ($response != '[SoapFault]') {
            $this->_parseTrackingResponse($trackings, $response);
        } else {
            Mage::helper('aramexshipping')->log(
                array('message' => Mage::helper('aramexshipping')->__('Could not get tracking.')),
                '' , 'aramex_tracking'
            );
            Mage::helper('aramexshipping')->sendLogEmail(
                array('subject' => 'Get Tracking Error Log', 'content' => 'Could not get tracking.')
            );
        }

        return $this->_result;
    }

    private function _parseTrackingResponse($trackings, $response)
    {
        $helper = Mage::helper('aramexshipping');
        $errorTitle = $helper->__('Unable to retrieve tracking');
        $trackingResultsValue = $response->TrackingResults->KeyValueOfstringArrayOfTrackingResultmFAkxlpY->Value;
        $resultArr = array();
        $errorArr = array();

        if ($response->HasErrors) {
            $code = $response->Notifications->Notification->Code;
            $message = $response->Notifications->Notification->Message;
            $errorTitle = $helper->__('%s : %s', $code, $message);
        } elseif (empty($trackingResultsValue)) {
            $errorTitle = $helper->__('Unable to retrieve tracking');
        } else {
            $trackingResults = $trackingResultsValue->TrackingResult;
            foreach ($trackingResults as $tr) {
                $rArr = array();
                $updateCode = $tr->UpdateCode;
                $tracknum = $tr->WaybillNumber;
                $rArr['delivery_location'] = $tr->UpdateLocation;
                $time = strtotime($tr->UpdateDateTime);
                $rArr['deliverydate'] = (string)date('Y-m-d', $time);
                $rArr['deliverytime'] = (string)date('H:i', $time) . ':00';
                $rArr['status'] = $tr->UpdateDescription;
                $rArr['comment'] = $tr->Comments;
                $resultArr[$tracknum . ' - ' . $updateCode] = $rArr;
                if ($tr->ProblemCode) {
                    $errorArr[$tracknum . ' - ' . $updateCode] = 'Problem Code: ' . $tr->ProblemCode;
                }
            }
        }

        $result = Mage::getModel('shipping/tracking_result');

        if ($errorArr || $resultArr) {
            foreach ($errorArr as $t => $r) {
                $error = Mage::getModel('shipping/tracking_result_error');
                $error->setCarrier('aramex');
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setTracking($t);
                $error->setErrorMessage($r);
                $result->append($error);

                $errorLog = array(
                    array('message' => $t),
                    array('message' => $r)
                );
                $helper->log($errorLog, '', 'aramex_tracking');
                $helper->sendLogEmail(
                    array('subject' => 'Tracking Response Parser Error Log', 'content' => $errorLog)
                );
            }

            foreach ($resultArr as $t => $data) {
                $tracking = Mage::getModel('shipping/tracking_result_status');
                $tracking->setCarrier('aramex');
                $tracking->setCarrierTitle($this->getConfigData('title'));
                $tracking->setTracking($t);
                $tracking->addData($data);
                $result->append($tracking);
            }
        } else {
            foreach ($trackings as $t) {
                $error = Mage::getModel('shipping/tracking_result_error');
                $error->setCarrier('aramex');
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setTracking($t);
                $error->setErrorMessage($errorTitle);
                $result->append($error);

                $errorLog = array(
                    array('message' => $t),
                    array('message' => $errorTitle)
                );
                $helper->log($errorLog, '', 'aramex_tracking');
                $helper->sendLogEmail(
                    array('subject' => 'Tracking Response Parser Error Log', 'content' => $errorLog)
                );
            }
        }

        $this->_result = $result;
    }

    protected function _updateFreeMethodQuote($request)
    {
        $freeShipping = false;
        $items = $request->getAllItems();
        $c = count($items);
        for ($i = 0; $i < $c; $i++) {
            if ($items[$i]->getProduct() instanceof Mage_Catalog_Model_Product) {
                if ($items[$i]->getFreeShipping()) {
                    $freeShipping = true;
                } else {
                    return;
                }
            }
        }
        if ($freeShipping) {
            $request->setFreeShipping(true);
        }
    }
}
