<?php

class Shopgo_AramexShipping_Model_Carrier_Aramex
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code   = 'aramex';
    protected $_result = null;

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!Mage::getStoreConfig('carriers/' . $this->_code . '/active')) {
            return false;
        }

        $destinationData = array(
            'city'       => ucwords(strtolower($request->getDestCity())),
            'country_id' => $request->getDestCountryId(),
            'postcode'   => $request->getDestPostcode()
        );

        $session = Mage::app()->getStore()->isAdmin()
            ? Mage::getSingleton('adminhtml/session_quote')
            : Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();

        $price = 0;
        $error = false;
        $methodTitle = '';

        if (!$request->getFreeShipping()) {
            $result = Mage::getModel('aramexshipping/shipment')
                ->getRatesAndPackages($quote, true, $destinationData);

            $error = $result['error'];
            $error_msg = isset($result['error_msg'])
                ? 'Aramex Error: ' . $result['error_msg'] : '';
            $price = $result['price'];
        } else {
            $methodTitle = Mage::helper('aramexshipping')->__('Free shipping applied');
        }

        $handling = Mage::getStoreConfig('carriers/' . $this->_code . '/handling');
        $result = Mage::getModel('shipping/rate_result');

        if ((!$error && $price > 0) || (!$error && $request->getFreeShipping())) {
            $method = Mage::getModel('shipping/rate_result_method');
            $method->setCarrier($this->_code);
            $method->setMethod($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethodTitle($methodTitle);
            $method->setPrice($price);
            $method->setCost($price);
            $result->append($method);
        } else {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($error_msg ? $error_msg : $this->getConfigData('specificerrmsg'));
            $result->append($error);

            if ($error_msg) {
                Mage::helper('aramexshipping')->log($error_msg, '', 'aramex_collect_rates');
                Mage::helper('aramexshipping')->sendLogEmail(
                    array('subject' => 'Collect Rates Error Log', 'content' => $error_msg)
                );
            }
        }

        return $result;
    }

    public function getAllowedMethods()
    {
        return array('aramex' => $this->getConfigData('name'));
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
}
