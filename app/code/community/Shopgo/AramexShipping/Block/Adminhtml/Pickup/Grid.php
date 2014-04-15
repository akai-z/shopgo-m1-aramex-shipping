<?php

class Shopgo_AramexShipping_Block_Adminhtml_Pickup_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('aramexPickupsGrid');
        $this->setDefaultSort('asp_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('aramexshipping/pickup')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('asp_id', array(
            'header' => Mage::helper('aramexshipping')->__('ID'),
            'width'  => '100px',
            'type'  => 'number',
            'index'  => 'asp_id'
        ));

        $this->addColumn('guid', array(
            'header' => Mage::helper('aramexshipping')->__('GUID'),
            'index'  => 'guid'
        ));

        $this->addColumn('shipment_increment_ids', array(
            'header'   => Mage::helper('aramexshipping')->__('Shipment Number'),
            'index'    => 'shipment_increment_ids',
            'renderer' => 'Shopgo_AramexShipping_Block_Adminhtml_Pickup_Renderer_IncrementId'
        ));

        $this->addColumn('action',
            array(
                'header'  =>  Mage::helper('aramexshipping')->__('Action'),
                'width'   => '100px',
                'type'    => 'action',
                'getter'  => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('aramexshipping')->__('Cancel'),
                        'url'     => array('base' => '*/*/cancel'),
                        'field'   => 'id'
                    ),
                    array(
                        'caption' => Mage::helper('aramexshipping')->__('Delete'),
                        'url'     => array('base' => '*/*/delete'),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('aramexshipping')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('aramexshipping')->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('asp_id');
        $this->getMassactionBlock()->setFormFieldName('pickups_ids');

        $this->getMassactionBlock()->addItem('cancel', array(
            'label'   => Mage::helper('aramexshipping')->__('Cancel'),
            'url'     => $this->getUrl('*/*/massCancel'),
            'confirm' => Mage::helper('aramexshipping')->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => Mage::helper('aramexshipping')->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('aramexshipping')->__('Are you sure?')
        ));

        return $this;
    }

    //public function getRowUrl($row)
    //{
        //return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    //}
}
