jQuery(document).ready(function($) {
    'use strict';

    // If the simplified booking form is present, do not bind legacy handlers
    if ($('.trm-booking-wrapper-simple').length) {
        return;
    }
    
    let selectedDate = '';
    let selectedTime = '';
    let currentStep = 1;
    let isMember = null;
    let donationAmount = 0;
    
    // Date picker change
    $('#booking_date').on('change', function() {
        selectedDate = $(this).val();
        loadAvailableSlots(selectedDate);
    });
    
    // Load available time slots
    function loadAvailableSlots(date) {
        $('#time-slots-container').html('<p class="trm-loading-text">Loading available slots...</p>');
        
        $.ajax({
            url: trmCounseling.ajax_url,
            type: 'POST',
            data: {
                action: 'trm_get_available_slots',
                nonce: trmCounseling.nonce,
                date: date
            },
            success: function(response) {
                if (response.success) {
                    displayTimeSlots(response.data.slots);
                } else {
                    $('#time-slots-container').html('<p class="trm-error">Failed to load time slots.</p>');
                }
            },
            error: function() {
                $('#time-slots-container').html('<p class="trm-error">An error occurred. Please try again.</p>');
            }
        });
    }
    
    // Display time slots
    function displayTimeSlots(slots) {
        if (slots.length === 0) {
            $('#time-slots-container').html('<p class="trm-note">No available slots for this date.</p>');
            $('#step1-next').prop('disabled', true);
            return;
        }
        
        let html = '<div class="time-slots-grid">';
        slots.forEach(function(slot) {
            html += '<button type="button" class="time-slot-btn" data-time="' + slot + '">' + formatTime(slot) + '</button>';
        });
        html += '</div>';
        
        $('#time-slots-container').html(html);
        
        // Time slot selection
        $('.time-slot-btn').on('click', function() {
            $('.time-slot-btn').removeClass('selected');
            $(this).addClass('selected');
            selectedTime = $(this).data('time');
            $('#step1-next').prop('disabled', false);
        });
    }
    
    // Format time (24h to 12h)
    function formatTime(time) {
        const [hours, minutes] = time.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return displayHour + ':' + minutes + ' ' + ampm;
    }
    
    // Navigation
    $('#step1-next').on('click', function() {
        goToStep(2);
    });
    
    $('#step2-next').on('click', function() {
        if (validateStep2()) {
            goToStep(3);
        }
    });
    
    // Membership selection
    $('input[name="is_member"]').on('change', function() {
        isMember = $(this).val() === '1';
        if (isMember) {
            goToStep('4-member');
        } else {
            goToStep('4-nonmember');
        }
    });
    
    // Donation buttons - Member
    $('.trm-donation-btn').on('click', function() {
        $('.trm-donation-btn').removeClass('selected');
        $(this).addClass('selected');
        
        const amount = $(this).data('amount');
        if (amount === 'custom') {
            $('.trm-custom-amount-wrapper').show();
            $('#continue-donation-member').prop('disabled', true);
        } else {
            $('.trm-custom-amount-wrapper').hide();
            donationAmount = amount;
            $('#donation_amount').val(amount);
            $('#continue-donation-member').prop('disabled', false);
        }
    });
    
    $('#custom_donation').on('input', function() {
        const amount = parseFloat($(this).val());
        if (amount > 0) {
            donationAmount = amount;
            $('#donation_amount').val(amount);
            $('#continue-donation-member').prop('disabled', false);
        } else {
            $('#continue-donation-member').prop('disabled', true);
        }
    });
    
    $('#continue-donation-member').on('click', function() {
        goToStep('final');
    });
    
    $('#skip-donation-member').on('click', function() {
        donationAmount = 0;
        $('#donation_amount').val(0);
        goToStep('final');
    });
    
    // Donation buttons - Non-member
    $('.trm-donation-btn-nm').on('click', function() {
        $('.trm-donation-btn-nm').removeClass('selected');
        $(this).addClass('selected');
        
        const amount = $(this).data('amount');
        if (amount === 'custom') {
            $('.trm-custom-amount-wrapper-nm').show();
            $('#continue-donation-nonmember').prop('disabled', true);
        } else {
            $('.trm-custom-amount-wrapper-nm').hide();
            donationAmount = amount;
            $('#donation_amount').val(amount);
            $('#continue-donation-nonmember').prop('disabled', false);
        }
    });
    
    $('#custom_donation_nm').on('input', function() {
        const amount = parseFloat($(this).val());
        if (amount > 0) {
            donationAmount = amount;
            $('#donation_amount').val(amount);
            $('#continue-donation-nonmember').prop('disabled', false);
        } else {
            $('#continue-donation-nonmember').prop('disabled', true);
        }
    });
    
    $('#continue-donation-nonmember').on('click', function() {
        goToStep('final');
    });
    
    $('#skip-donation-nonmember').on('click', function() {
        goToStep('5-limited');
    });
    
    $('#add-seed-later').on('click', function() {
        goToStep('4-nonmember');
    });
    
    $('#skip-seed-final').on('click', function() {
        donationAmount = 0;
        $('#donation_amount').val(0);
        goToStep('final');
    });
    
    // Back buttons
    $('.trm-btn-back').on('click', function() {
        const current = $(this).closest('.trm-step');
        if (current.hasClass('trm-step-2')) {
            goToStep(1);
        } else if (current.hasClass('trm-step-final')) {
            goToStep(3);
        }
    });
    
    // Go to step
    function goToStep(step) {
        $('.trm-step').removeClass('active');
        
        if (typeof step === 'number') {
            $('.trm-step-' + step).addClass('active');
            currentStep = step;
        } else {
            $('.trm-step-' + step).addClass('active');
            currentStep = step;
        }
        
        // Update summary if going to final step
        if (step === 'final') {
            updateBookingSummary();
        }
        
        // Scroll to top
        $('#trm-booking-wrapper').get(0).scrollIntoView({ behavior: 'smooth' });
    }
    
    // Validate step 2
    function validateStep2() {
        const name = $('#full_name').val().trim();
        const phone = $('#phone').val().trim();
        
        if (name === '' || phone === '') {
            alert('Please fill in all required fields.');
            return false;
        }
        
        return true;
    }
    
    // Update booking summary
    function updateBookingSummary() {
        const name = $('#full_name').val();
        const phone = $('#phone').val();
        const email = $('#email').val();
        const memberText = isMember ? 'Yes' : 'No';
        const duration = (isMember || donationAmount > 0) ? 30 : 15;
        
        let html = '<div class="summary-item"><strong>Name:</strong> ' + name + '</div>';
        html += '<div class="summary-item"><strong>Phone:</strong> ' + phone + '</div>';
        if (email) {
            html += '<div class="summary-item"><strong>Email:</strong> ' + email + '</div>';
        }
        html += '<div class="summary-item"><strong>Date:</strong> ' + formatDate(selectedDate) + '</div>';
        html += '<div class="summary-item"><strong>Time:</strong> ' + formatTime(selectedTime) + '</div>';
        html += '<div class="summary-item"><strong>Member:</strong> ' + memberText + '</div>';
        html += '<div class="summary-item"><strong>Session Duration:</strong> ' + duration + ' minutes</div>';
        if (donationAmount > 0) {
            html += '<div class="summary-item"><strong>Donation:</strong> $' + donationAmount.toFixed(2) + '</div>';
        }
        
        $('#booking-summary').html(html);
    }
    
    // Format date
    function formatDate(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }
    
    // Form submission
    $('#trm-booking-form').on('submit', function(e) {
        e.preventDefault();
        
        $('#trm-loading').show();
        $('#confirm-booking').prop('disabled', true);
        
        const formData = {
            action: 'trm_create_booking',
            nonce: trmCounseling.nonce,
            full_name: $('#full_name').val(),
            phone: $('#phone').val(),
            email: $('#email').val(),
            booking_date: selectedDate,
            booking_time: selectedTime,
            is_member: isMember ? 1 : 0,
            donation_amount: donationAmount
        };
        
        $.ajax({
            url: trmCounseling.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#trm-loading').hide();
                
                if (response.success) {
                    showSuccessMessage(response.data);
                } else {
                    alert('Error: ' + response.data.message);
                    $('#confirm-booking').prop('disabled', false);
                }
            },
            error: function() {
                $('#trm-loading').hide();
                alert('An error occurred. Please try again.');
                $('#confirm-booking').prop('disabled', false);
            }
        });
    });
    
    // Show success message
    function showSuccessMessage(data) {
        const duration = data.session_duration;
        
        let html = '<p><strong>Booking ID:</strong> ' + data.booking_id + '</p>';
        html += '<p><strong>Date:</strong> ' + formatDate(selectedDate) + '</p>';
        html += '<p><strong>Time:</strong> ' + formatTime(selectedTime) + '</p>';
        html += '<p><strong>Duration:</strong> ' + duration + ' minutes</p>';
        if (donationAmount > 0) {
            html += '<p><strong>Donation:</strong> $' + donationAmount.toFixed(2) + '</p>';
        }
        
        $('#confirmation-details').html(html);
        goToStep('success');
    }
});
