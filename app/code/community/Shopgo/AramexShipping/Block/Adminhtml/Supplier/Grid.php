<?php

class Shopgo_AramexShipping_Block_Adminhtml_Supplier_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('aramexSuppliersGrid');
        $this->setDefaultSort('asv_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('aramexshipping/supplier')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('asv_id', array(
            'header' => Mage::helper('aramexshipping')->__('ID'),
            'width'  => '100px',
            'type'  => 'number',
            'index'  => 'asv_id'
        ));

        $this->addColumn('account_number', array(
            'header' => Mage::helper('aramexshipping')->__('Aramex Account Number'),
            'index'  => 'account_number'
        ));

        $this->addColumn('person_name', array(
            'header' => Mage::helper('aramexshipping')->__('Name'),
            'index'  => 'person_name'
        ));

        $this->addColumn('email', array(
            'header' => Mage::helper('aramexshipping')->__('Email'),
            'index'  => 'email'
        ));

        $this->addColumn('action',
            array(
                'header'  =>  Mage::helper('aramexshipping')->__('Action'),
                'width'   => '100px',
                'type'    => 'action',
                'getter'  => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('aramexshipping')->__('Edit'),
                        'url'     => array('base' => '*/*/edit'),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('aramexshipping')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('aramexshipping')->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('asv_id');
        $this->getMassactionBlock()->setFormFieldName('suppliers_ids');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => Mage::helper('aramexshipping')->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('aramexshipping')->__('Are you sure?')
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}
