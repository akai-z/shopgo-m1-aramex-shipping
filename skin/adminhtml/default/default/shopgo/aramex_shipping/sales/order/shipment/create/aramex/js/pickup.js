document.observe("dom:loaded", function() {
    Calendar.setup({
        inputField : 'shopgo_aramex_pickup_date',
        ifFormat : '%m/%d/%Y %I:%M:%S %P',
        showsTime: true,
        button : 'shopgo_aramex_pickup_date_trig',
        align : 'Bl',
        singleClick : true
    });

    Validation.add('sga-pickup-required-entry', Translator.translate('This is a required field.'), function (v) {
        if ($('shopgo_aramex_shipment_pickup_enabled').value == 0) {
            return true;
        }
        return !Validation.get('IsEmpty').test(v);
    });

    shopgoAramexPickupFormSwitch(
        $('shopgo_aramex_shipment_pickup_enabled').value
    );

    $('shopgo_aramex_shipment_pickup_open').observe('click', function(event) {
        shopgoAramexPickupFormSwitch(1);
    });

    $('shopgo_aramex_shipment_pickup_close').observe('click', function(event) {
        event.preventDefault();
        shopgoAramexPickupFormSwitch(0);
    });
});

function shopgoAramexPickupFormSwitch(visibility) {
    if (!parseInt(visibility)) {
        $('shopgo_aramex_shipment_pickup_enabled').value = 0;
        $('shopgo_aramex_shipment_pickup_container').hide();
        $('shopgo_aramex_shipment_pickup_trigger_container').show();
    } else {
        $('shopgo_aramex_shipment_pickup_enabled').value = 1;
        $('shopgo_aramex_shipment_pickup_trigger_container').hide();
        $('shopgo_aramex_shipment_pickup_container').show();
    }
}
