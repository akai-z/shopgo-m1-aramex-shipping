<?php

class Shopgo_AramexShipping_Model_Shipping_Info
    extends Mage_Shipping_Model_Info
{
    public function getTrackingInfoByOrder()
    {
        $shipTrack = array();
        $order = $this->_initOrder();
        if ($order) {
            $shipments = $order->getShipmentsCollection();
            foreach ($shipments as $shipment){
                $increment_id = $shipment->getIncrementId();
                $tracks = $shipment->getTracksCollection();

                $trackingInfos=array();
                foreach ($tracks as $track){
                    $trackingInfos[] = $track->getNumberDetail();
                }

                $_trackingInfos = array();
                if ($order->getShippingCarrier()->getCarrierCode() == 'aramex') {
                    if (count($trackingInfos) == 1) {
                        $_trackingInfos = $trackingInfos[0];
                    } else {
                        foreach ($trackingInfos as $i => $ti) {
                            $gti = array();
                            if (isset($trackingInfos[$i + 1])) {
                                $gti = array_merge($ti, $trackingInfos[$i + 1]);
                            }
                            if (!empty($gti)) {
                                $_trackingInfos = array_merge($_trackingInfos, $gti);
                            }
                        }
                    }
                } else {
                    $_trackingInfos = $trackingInfos;
                }

                $shipTrack[$increment_id] = $_trackingInfos;
            }
        }
        $this->_trackingInfo = $shipTrack;
        return $this->_trackingInfo;
    }

    public function getTrackingInfoByShip()
    {
        $shipTrack = array();
        $shipment = $this->_initShipment();
        if ($shipment) {
            $increment_id = $shipment->getIncrementId();
            $tracks = $shipment->getTracksCollection();

            $trackingInfos=array();
            foreach ($tracks as $track){
                $trackingInfos[] = $track->getNumberDetail();
            }

            $_trackingInfos = array();
            if ($shipment->getOrder()->getShippingCarrier()->getCarrierCode() == 'aramex') {
                if (count($trackingInfos) == 1) {
                    $_trackingInfos = $trackingInfos[0];
                } else {
                    foreach ($trackingInfos as $i => $ti) {
                        $gti = array();
                        if (isset($trackingInfos[$i + 1])) {
                            $gti = array_merge($ti, $trackingInfos[$i + 1]);
                        }
                        if (!empty($gti)) {
                            $_trackingInfos = array_merge($_trackingInfos, $gti);
                        }
                    }
                }
            } else {
                $_trackingInfos = $trackingInfos;
            }

            $shipTrack[$increment_id] = $_trackingInfos;

        }
        $this->_trackingInfo = $shipTrack;
        return $this->_trackingInfo;
    }

    public function getTrackingInfoByTrackId()
    {
        $track = Mage::getModel('sales/order_shipment_track')->load($this->getTrackId());
        if ($track->getId() && $this->getProtectCode() == $track->getProtectCode()) {
            $this->_trackingInfo = $track->getCarrierCode() == 'aramex' ?
                array($track->getNumberDetail()) : array(array($track->getNumberDetail()));
        }
        return $this->_trackingInfo;
    }
}
