<?php

class Shopgo_AramexShipping_Model_Pickup
    extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('aramexshipping/pickup');
    }

    public function isEnabled($checkDepends = true)
    {
        $helper = Mage::helper('aramexshipping');

        if ($checkDepends && $helper->isAdvIfconfigEnabled()) {
            return Mage::helper('advifconfig')->getStoreConfigWithDependsFlag(
                Shopgo_AramexShipping_Helper_Data::CARRIERS_ARAMEX_SYSTEM_PATH . 'pickup_service',
                Shopgo_AramexShipping_Helper_Data::CARRIERS_ARAMEX_SYSTEM_PATH . 'active',
                1
            );
        }

        return $helper->getConfigData('pickup_service', 'carriers_aramex');
    }

    public function getPickupData($pickupData, $shipmentsData, $supplierData = array(), $clientInfo = array())
    {
        $helper = Mage::helper('aramexshipping');

        if (!$clientInfo) {
            $clientInfo = $helper->getClientInfo($supplierData);
        }

        $pickupDate = strtotime($pickupData['date']);
        $pickupTimesDate = date('m/d/Y', strtotime($shipmentsData['Shipment']['ShippingDateTime']));

        $params = array(
            'Pickup' => array(
                'PickupAddress' => array(
                    'Line1'               => $pickupData['address']['line1'],
                    'Line2'               => $pickupData['address']['line2'],
                    'Line3'               => $pickupData['address']['line3'],
                    'City'                => ucwords(strtolower($pickupData['address']['city'])),
                    'StateOrProvinceCode' => $pickupData['address']['state_or_province_code'],
                    'PostCode'            => $pickupData['address']['post_code'],
                    'CountryCode'         => strtoupper($pickupData['address']['country_code']),
                ),
                'PickupContact' => array(
                    'Department'      => $pickupData['contact']['department'],
                    'PersonName'      => $pickupData['contact']['person_name'],
                    'Title'           => $pickupData['contact']['title'],
                    'CompanyName'     => $pickupData['contact']['company_name'],
                    'PhoneNumber1'    => $pickupData['contact']['phone_number1'],
                    'PhoneNumber1Ext' => $pickupData['contact']['phone_number1_ext'],
                    'PhoneNumber2'    => $pickupData['contact']['phone_number2'],
                    'PhoneNumber2Ext' => $pickupData['contact']['phone_number2_ext'],
                    'FaxNumber'       => $pickupData['contact']['fax_number'],
                    'CellPhone'       => $pickupData['contact']['cellphone'],
                    'EmailAddress'    => $pickupData['contact']['email'],
                    'Type'            => $pickupData['contact']['type']
                ),
                'PickupLocation' => $pickupData['location'],
                'PickupDate'     => date('c', $pickupDate),
                'ReadyTime'      => date('c', strtotime($pickupTimesDate . ' ' . $helper->combineTimeParts($pickupData['ready_time']))),
                'LastPickupTime' => date('c', strtotime($pickupTimesDate . ' ' . $helper->combineTimeParts($pickupData['last_time']))),
                'ClosingTime'    => date('c', strtotime($pickupTimesDate . ' ' . $helper->combineTimeParts($pickupData['closing_time']))),
                'Comments'       => $pickupData['comment'],
                'Reference1'     => $pickupData['reference1'],
                'Reference2'     => $pickupData['reference2'],
                'Vehicle'        => $pickupData['vehicle'],
                'Shipments'      => $shipmentsData,
                'PickupItems'    => array(
                    'PickupItemDetail' => array(
                        'ProductGroup'      => $shipmentsData['Shipment']['Details']['ProductGroup'],
                        'ProductType'       => $shipmentsData['Shipment']['Details']['ProductType'],
                        'NumberOfShipments' => 1,
                        'PackageType'       => $pickupData['items']['package_type'],
                        'Payment'           => $shipmentsData['Shipment']['Details']['PaymentType'],
                        'ShipmentWeight'    => array(
                            'Value' => $shipmentsData['Shipment']['Details']['ActualWeight']['Value'],
                            'Unit'  => $shipmentsData['Shipment']['Details']['ActualWeight']['Unit']
                        ),
                        'ShipmentVolume'    => array(
                            'Value' => $pickupData['items']['shipment_volume_value'],
                            'Unit'  => $pickupData['items']['shipment_volume_unit']
                        ),
                        'NumberOfPieces'    => $shipmentsData['Shipment']['Details']['NumberOfPieces'],
                        //'CashAmount'        => array(),
                        //'ExtraCharges'      => array(),
                        //'ShipmentDimesion'  => array(),
                        'Comments'          => $pickupData['items']['comment']
                    )
                ),
                'Status' => $pickupData['status']
            ),
            'ClientInfo' => $clientInfo,
            'Transaction' => array(
                'Reference1' => '001',
                'Reference2' => '',
                'Reference3' => '',
                'Reference4' => '',
                'Reference5' => ''
            ),
            'LabelInfo' => array(
                'ReportID'   => 9201,
                'ReportType' => 'URL',
            )
        );

        return $params;
    }

    public function cancelPickup($pickupId)
    {
        $helper = Mage::helper('aramexshipping');
        $pickup = Mage::getModel('aramexshipping/pickup')->load($pickupId);
        $clientInfo = array();

        if ($pickup->getSupplierId() > 0) {
            $clientInfo = $helper->getClientInfo(
                Mage::getModel('aramexshipping/supplier')->load($pickup->getSupplierId())->getData()
            );
        } else {
            $clientInfo = $helper->getClientInfo();
        }

        $params = array(
            'PickupGUID' => $pickup->getGuid(),
            'ClientInfo' => $clientInfo,
            'Transaction' => array(
                'Reference1' => '001',
                'Reference2' => '',
                'Reference3' => '',
                'Reference4' => '',
                'Reference5' => ''
            ),
            'Comments' => ''
        );

        $result = $helper->soapClient(
            'pickup_cancel_service',
            $params
        );

        if ($result != '[SoapFault]') {
            $error = $result->HasErrors;
            $errorMsg = $helper->getServiceErrorMessages($result->Notifications->Notification);
            if (!$error) {
                return 1;
            } else {
                $helper->log($errorMsg, '', 'aramex_create_shipment');
                $userMsg = $helper->__('Pickup could not be cancelled, please contact us to know more about this issue.<br/>Error:&nbsp;%s', $errorMsg);
                $helper->userMessage($userMsg, 'error');
                $helper->sendLogEmail(array('subject' => 'Cancel Pickup Error Log', 'content' => $errorMsg));

                return 0;
            }
        }
    }
}
