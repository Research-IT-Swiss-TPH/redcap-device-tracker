/**
 * Device Tracker - a REDCap External Module
 * Author: Ekin Tertemiz
*/

var STPH_deviceTracker = STPH_deviceTracker || {};


// Initialization
STPH_deviceTracker.init = function() {
    
    var trackings = STPH_deviceTracker.trackings;

    trackings.forEach(function(field_name) {

        var field = $('input[name="'+field_name+'"]');
        field.prop('disabled', true);
        console.log(field_name + ": " + field.val());

        //  Render HTML markup depending on field state
        /**
         * 
         * state defs:
         * empty: tracking_field is empty => not releated to device yet
         * unavailable: device has been assigned, getting device_state for device_id and record_id will return "unavailable"
         * maintained:  device has been assigned, getting device_state for device_id and record_id will return "maintained"
         * 
         * 
         * effects on html:
         * empty: enable button_assign, disable others
         * unavailable: enable button_return, disable others
         * maintained: enable button_reset,hide button_group, show button_tracking_log
         * 
         * 
         */
        var headline = '<small class="device-tracker-interface-headline">Device Tracker Interface</small>';
        var button_assign = '<button type="button" class="btn btn-sm btn-primaryrc" ><i class="fas fa-plus-circle"></i> Assign</button>';
        var button_return = '<button type="button" class="btn btn-sm btn-primaryrc" disabled><i class="fas fa-history"></i> Return</button>';
        var button_reset  = '<button type="button" class="btn btn-sm btn-primaryrc" disabled><i class="fas fa-power-off"></i> Reset</button>';
        var button_group_markdown = headline + '<div style="margin-bottom:15px;margin-top:10px;" class="btn-group d-flex" role="group" aria-label="Device Tracker Interface">' + button_assign + button_return + button_reset + '</div>';        
    
        var device_tracker_interface = '<div style="max-width:90%;" id="device_tracker_interface_'+field_name+'">' + button_group_markdown + '</div>';


        var target = $('tr#'+field_name+'-tr').find('input');
        target.parent().prepend(device_tracker_interface);

        //  Get state if value != NULL

    });

}