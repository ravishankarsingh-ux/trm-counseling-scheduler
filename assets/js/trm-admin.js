jQuery(document).ready(function($) {
    'use strict';
    
    // Auto-submit filter form on change
    $('.trm-filters select, .trm-filters input[type="date"]').on('change', function() {
        $(this).closest('form').submit();
    });
    
    // Confirm before status update
    $('button[name="update_status"]').on('click', function(e) {
        if (!confirm('Are you sure you want to update this booking status?')) {
            e.preventDefault();
        }
    });
    
    // Settings validation
    $('input[name="trm_save_settings"]').on('click', function(e) {
        const startTime = $('#working_hours_start').val();
        const endTime = $('#working_hours_end').val();
        
        if (startTime >= endTime) {
            e.preventDefault();
            alert('End time must be after start time.');
            return false;
        }
    });
});
