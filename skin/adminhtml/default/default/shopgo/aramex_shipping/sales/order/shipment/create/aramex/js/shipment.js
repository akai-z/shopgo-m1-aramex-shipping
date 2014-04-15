document.observe("dom:loaded", function() {
    Calendar.setup({
        inputField : 'shopgo_aramex_shipment_shipping_date',
        ifFormat : '%m/%d/%Y %I:%M:%S %P',
        showsTime: true,
        button : 'shopgo_aramex_shipment_shipping_date_trig',
        align : 'Bl',
        singleClick : true
    });

    Calendar.setup({
        inputField : 'shopgo_aramex_shipment_due_date',
        ifFormat : '%m/%d/%Y %I:%M:%S %P',
        showsTime: true,
        button : 'shopgo_aramex_shipment_due_date_trig',
        align : 'Bl',
        singleClick : true
    });
});
