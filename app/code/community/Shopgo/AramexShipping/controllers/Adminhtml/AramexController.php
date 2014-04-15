<?php

class Shopgo_AramexShipping_Adminhtml_AramexController
    extends Mage_Adminhtml_Controller_Action
{
    public function checkAccountAction()
    {
        $params = $this->getRequest()->getPost();
        $helper = Mage::helper('aramexshipping');

        $validValues = array(
            'sender' => array('system', 'supplier'),
            'pass_changed' => array(0, 1),
            'pin_changed' => array(0, 1)
        );

        if (!isset($params['sender']) || !isset($params['pass_changed']) || !isset($params['pin_changed'])) {
            Mage::app()->getResponse()->setBody('Bad check request');
            return;
        } elseif (!in_array($params['sender'], $validValues['sender'])
                  || !in_array($params['pass_changed'], $validValues['pass_changed'])
                  || !in_array($params['pin_changed'], $validValues['pin_changed'])) {
            Mage::app()->getResponse()->setBody('Bad check request');
            return;
        }

        if ($params['sender'] == 'support' && isset($params['id']) && !$helper->isPositiveInteger($params['id'])) {
            Mage::app()->getResponse()->setBody('Bad check request');
            return;
        }

        if (!isset($params['username']) || !isset($params['password'])) {
            Mage::app()->getResponse()->setBody('Username and Password are necessary to do the check');
            return;
        } elseif (empty($params['username']) || empty($params['password'])) {
            Mage::app()->getResponse()->setBody('Username and Password are necessary to do the check');
            return;
        }

        $accountInfo = 4;

        if (!isset($params['account_country_code'])) {
            $accountInfo--;
        } elseif (empty($params['account_country_code'])) {
            $accountInfo--;
        }
        if (!isset($params['account_entity'])) {
            $accountInfo--;
        } elseif (empty($params['account_entity'])) {
            $accountInfo--;
        }
        if (!isset($params['account_number'])) {
            $accountInfo--;
        } elseif (empty($params['account_number'])) {
            $accountInfo--;
        }
        if (!isset($params['account_pin'])) {
            $accountInfo--;
        } elseif (empty($params['account_pin'])) {
            $accountInfo--;
        }

        if ($accountInfo > 0 && $accountInfo < 4) {
            Mage::app()->getResponse()->setBody('Please, fill the rest of account information fields');
            return;
        }

        $supplier = $params['sender'] == 'supplier' ? $helper->getSuppliersCollection($params['id']) : null;

        if (!$params['pass_changed'] && $params['password'] == '******') {
            if ($params['sender'] == 'system') {
                $params['password'] = $helper->getConfigData('password', 'carriers_aramex');
                $params['password'] = Mage::helper('core')->decrypt($params['password']);
            } else {
                $params['password'] = $supplier ? $supplier->getPassword() : '';
            }
        }

        if (!$params['pin_changed'] && $params['account_pin'] == '******') {
            if ($params['sender'] == 'system') {
                $params['account_pin'] = $helper->getConfigData('account_pin', 'carriers_aramex');
                $params['account_pin'] = Mage::helper('core')->decrypt($params['account_pin']);
            } else {
                $params['account_pin'] = $supplier ? $supplier->getAccountPin() : '';
            }
        }

        $result = Mage::getModel('aramexshipping/shipment')->checkAccount($params);

        Mage::app()->getResponse()->setBody($result['message']);
    }
}
