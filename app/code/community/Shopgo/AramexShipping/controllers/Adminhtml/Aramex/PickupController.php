<?php

class Shopgo_AramexShipping_Adminhtml_Aramex_PickupController
    extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('sales/aramexshipping_pickup')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Pickups Manager'), Mage::helper('adminhtml')->__('Pickup Manager'));

        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('Pickups'))
            ->_title($this->__('Manage Pickups'));
        $this->_initAction()
            ->renderLayout();
    }

    public function cancelAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('aramexshipping/pickup');

                $cancelPickup = $model->cancelPickup($this->getRequest()->getParam('id'));

                if ($cancelPickup) {
                    $model->setId($this->getRequest()->getParam('id'))
                        ->delete();

                    Mage::getSingleton('adminhtml/session')
                        ->addSuccess(Mage::helper('adminhtml')->__('Pickup was successfully cancelled'));
                } else {
                    Mage::getSingleton('adminhtml/session')
                        ->addError(Mage::helper('adminhtml')->__('Could not cancel pickup %d', $pickupId));
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('aramexshipping/pickup');

                $model->setId($this->getRequest()->getParam('id'))
                    ->delete();

                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('adminhtml')->__('Pickup was successfully deleted'));

                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    public function massCancelAction()
    {
        $pickupsIds = $this->getRequest()->getParam('pickups_ids');

        if(!is_array($pickupsIds)) {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('adminhtml')->__('Please select pickup(s)'));
        } else {
            try {
                foreach ($pickupsIds as $pickupId) {
                    $pickup = Mage::getModel('aramexshipping/pickup')->load($pickupId);

                    $cancelPickup = $pickup->cancelPickup($pickupId);

                    if ($cancelPickup) {
                        $pickup->delete();
                    } else {
                        Mage::getSingleton('adminhtml/session')
                            ->addError(Mage::helper('adminhtml')->__('Could not cancel pickup %d', $pickupId));
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d pickup(s) were successfully cancelled', count($pickupsIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massDeleteAction()
    {
        $pickupsIds = $this->getRequest()->getParam('pickups_ids');

        if(!is_array($pickupsIds)) {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('adminhtml')->__('Please select pickup(s)'));
        } else {
            try {
                foreach ($pickupsIds as $pickupId) {
                    $pickup = Mage::getModel('aramexshipping/pickup')->load($pickupId);
                    $pickup->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d pickup(s) were successfully deleted', count($pickupsIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function exportCsvAction()
    {
        $fileName = 'aramex_shipping_pickups.csv';
        $content  = $this->getLayout()->createBlock('aramexshipping/adminhtml_pickup_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName = 'aramex_shipping_pickups.xml';
        $content  = $this->getLayout()->createBlock('aramexshipping/adminhtml_pickup_grid')->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType = 'application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK', '');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename='. $fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
}
