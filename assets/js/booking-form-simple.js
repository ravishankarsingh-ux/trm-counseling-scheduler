/**
 * TRM Simplified Booking Form - Single Page
 */

(function($) {
    'use strict';

    var TRMBookingSimple = {
        nonce: trmCounseling.nonce || '',
        ajaxUrl: trmCounseling.ajax_url || '',
        events: [],
        currentEvent: null,
        donationOptions: [],
        paymentMethods: [],

        /**
         * Initialize
         */
        init: function() {
            // Clear time slots on page load
            $('#time-slots-container').html('<p class="trm-note">Please select an event and date to view available time slots</p>');
            // Hide donation section on page load
            $('#donation-section').hide();
            $('#payment-section').hide();
            this.loadEvents();
            this.loadDonationOptions();
            this.loadPaymentMethods();
            this.bindEvents();
        },

        /**
         * Load all events
         */
        loadEvents: function() {
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'trm_get_events',
                    nonce: this.nonce
                },
                success: function(response) {
                    if (response.success) {
                        TRMBookingSimple.events = response.data.events;
                        TRMBookingSimple.populateEventDropdown();
                    }
                }
            });
        },

        /**
         * Load donation options
         */
        loadDonationOptions: function() {
            var self = this;
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'trm_get_donation_options',
                    nonce: this.nonce
                },
                success: function(response) {
                    if (response.success && response.data.options) {
                        self.donationOptions = response.data.options;
                    }
                }
            });
        },

        /**
         * Load payment methods
         */
        loadPaymentMethods: function() {
            var self = this;
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'trm_get_payment_methods',
                    nonce: this.nonce
                },
                success: function(response) {
                    if (response.success && response.data.methods) {
                        self.paymentMethods = response.data.methods;
                        console.log('Payment methods loaded:', self.paymentMethods);
                    }
                }
            });
        },

        /**
         * Populate event dropdown
         */
        populateEventDropdown: function() {
            var $select = $('#event_id');
            
            // If AJAX returned events, populate them; otherwise keep any server-rendered options
            if (this.events && this.events.length > 0) {
                // Clear existing options except the first one
                $select.find('option:not(:first)').remove();
                var html = '';
                $.each(this.events, function(i, event) {
                    html += '<option value="' + event.id + '">' + event.title + '</option>';
                });
                $select.append(html);
                console.log('Events loaded via AJAX:', TRMBookingSimple.events.length);
            } else {
                console.log('No events from AJAX. Preserving server-rendered options.');
            }
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Event selection change
            $(document).on('change', '#event_id', function() {
                var eventId = $(this).val();
                $('#booking_time').val('');
                $('#time-slots-container').html('<p class="trm-note">Please select an event and date to view available time slots</p>');
                if (eventId) {
                    var event = self.findEvent(eventId);
                    if (!event) {
                        // Fallback: build a minimal event object so datepicker and slot loading still work
                        event = {
                            id: parseInt(eventId, 10),
                            title: $('#event_id option:selected').text(),
                            type: 'event',
                            capacity: 1,
                            session_duration: 30,
                            booking_fee: 0,
                            schedule_type: 'single',
                            event_date: null,
                            recurring_days: []
                        };
                    }
                    self.currentEvent = event;
                    self.updateEventDetails(event);
                    self.setupDatePicker(event);
                } else {
                    self.currentEvent = null;
                    $('#event-details').hide();
                    $('#booking_date').val('');
                    $('#time-slots-container').html('<p class="trm-note">Please select an event and date to view available time slots</p>');
                }
            });

            // Date selection change
            $(document).on('change', '#booking_date', function() {
                var date = $(this).val();
                if (date && self.currentEvent) {
                    self.loadAvailableSlots(date);
                }
            });

            // Time slot selection
            $(document).on('click', '.trm-time-slot-simple', function() {
                $('.trm-time-slot-simple').removeClass('selected');
                $(this).addClass('selected');
                $('#booking_time').val($(this).data('slot'));
            });

            // Membership selection
            $(document).on('change', 'input[name="is_member"]', function() {
                var isMember = $(this).val();
                if (isMember === '1') {
                    self.showDonationSection(true);
                } else {
                    self.showDonationSection(false);
                }
            });

            // Donation button clicks
            $(document).on('click', '.trm-donation-btn-simple', function() {
                var amount = $(this).data('amount');
                $('.trm-donation-btn-simple').removeClass('selected');
                $(this).addClass('selected');
                
                if (amount === 'custom') {
                    $('#custom-amount-wrapper').show();
                    $('#donation_amount').val(0); // Reset to 0 while custom input is shown
                } else {
                    $('#custom-amount-wrapper').hide();
                    $('#donation_amount').val(amount || 0);
                    // Update payment section when donation amount changes
                    self.showPaymentSection();
                }
            });

            // Custom donation input
            $(document).on('input', '#custom_donation', function() {
                var amount = $(this).val();
                $('#donation_amount').val(amount || 0);
                // Update payment section when custom amount changes
                self.showPaymentSection();
            });

            // Form submission
            $(document).on('submit', '#trm-booking-form', function(e) {
                e.preventDefault();
                self.submitBooking();
            });
        },

        /**
         * Find event by ID
         */
        findEvent: function(eventId) {
            for (var i = 0; i < this.events.length; i++) {
                if (this.events[i].id == eventId) {
                    return this.events[i];
                }
            }
            return null;
        },

        /**
         * Update event details display
         */
        updateEventDetails: function(event) {
            var html = '<strong>Type:</strong> ' + event.type + '<br>' +
                '<strong>Capacity:</strong> ' + event.capacity + '<br>' +
                '<strong>Duration:</strong> ' + event.session_duration + ' minutes<br>' +
                '<strong>Fee:</strong> $' + (event.booking_fee || 0);
            
            $('#event-description').html(html);
            $('#event-details').show();
        },

        /**
         * Setup date picker for event
         */
        setupDatePicker: function(event) {
            var $datePicker = $('#booking_date');
            var minDate = new Date();
            minDate.setDate(minDate.getDate());
            
            var maxDate = new Date();
            
            // Set max date based on event schedule
            if (event.schedule_type === 'single') {
                if (event.event_date) {
                    var eventDate = new Date(event.event_date + 'T00:00:00');
                    maxDate = eventDate;
                } else {
                    // If no event_date is set, allow selection up to 90 days from today
                    var tmp = new Date();
                    tmp.setDate(tmp.getDate() + 90);
                    maxDate = tmp;
                }
            } else {
                // Recurring - set max to recurring_end_date
                var recurringEnd = event.recurring_end_date ? new Date(event.recurring_end_date + 'T00:00:00') : new Date(2099, 11, 31);
                maxDate = recurringEnd;
            }
            
            // Set input constraints
            $datePicker.attr('min', this.formatDate(minDate));
            $datePicker.attr('max', this.formatDate(maxDate));
            $datePicker.val('');
            
            // Store recurring days in data attribute for validation
            if (event.recurring_days && event.recurring_days.length > 0) {
                $datePicker.data('recurring-days', event.recurring_days);
                $datePicker.data('schedule-type', 'recurring');
            } else {
                $datePicker.data('schedule-type', 'single');
            }
            
            // Add change listener to validate selected date is in allowed days
            $datePicker.off('change.dateValidation').on('change.dateValidation', function() {
                var selectedDate = $(this).val();
                if (selectedDate && event.recurring_days) {
                    var date = new Date(selectedDate + 'T00:00:00');
                    var dayIndex = date.getDay();
                    // Convert day index (0-6, where 0 is Sunday) to day name
                    var dayMap = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                    var dayName = dayMap[dayIndex];
                    
                    if (event.recurring_days.indexOf(dayName) === -1) {
                        alert('This date is not available for this event. Please select a date on: ' + event.recurring_days.join(', '));
                        $(this).val('');
                        $('#time-slots-container').html('<p class="trm-note">Please select a valid date for this event</p>');
                    }
                }
            });
        },

        /**
         * Format date for input
         */
        formatDate: function(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        },

        /**
         * Load available slots
         */
        loadAvailableSlots: function(date) {
            var self = this;
            
            console.log('Loading slots for:', {
                date: date,
                event_id: self.currentEvent ? self.currentEvent.id : 'NO EVENT',
                currentEvent: self.currentEvent
            });
            
            $('#time-slots-container').html('<p class="trm-note">Loading slots...</p>');
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'trm_get_available_slots',
                    date: date,
                    event_id: self.currentEvent.id,
                    nonce: self.nonce
                },
                success: function(response) {
                    console.log('Slots AJAX response:', response);
                    if (response.success) {
                        console.log('Available slots:', response.data.slots);
                        self.displayTimeSlots(response.data.slots);
                    } else {
                        console.log('Slots error:', response.data);
                        $('#time-slots-container').html('<p class="trm-error">' + (response.data.message || 'Unknown error') + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Slots AJAX error:', {xhr: xhr, status: status, error: error});
                    $('#time-slots-container').html('<p class="trm-error">Error loading time slots: ' + error + '</p>');
                }
            });
        },

        /**
         * Display time slots
         */
        displayTimeSlots: function(slots) {
            var html = '';
            
            if (!slots || slots.length === 0) {
                $('#time-slots-container').html('<p class="trm-note">No available slots for this date</p>');
                return;
            }

            $.each(slots, function(i, slot) {
                html += '<button type="button" class="trm-time-slot-simple" data-slot="' + slot + '">' + slot + '</button>';
            });

            $('#time-slots-container').html(html);
        },

        /**
         * Show payment methods section
         */
        showPaymentSection: function() {
            var self = this;
            var bookingFee = self.currentEvent ? parseFloat(self.currentEvent.booking_fee) || 0 : 0;
            var donationAmount = parseFloat($('#donation_amount').val()) || 0;
            var totalAmount = bookingFee + donationAmount;
            
            // Only show payment section if there's an amount due
            if (totalAmount > 0) {
                $('#total-amount').text(totalAmount.toFixed(2));
                $('#payment-total').show();
                
                // Populate payment methods
                var html = '';
                if (self.paymentMethods.length === 0) {
                    // Default payment methods
                    self.paymentMethods = [
                        {id: 'offline', title: 'Bank Transfer / Cash'},
                        {id: 'stripe', title: 'Credit/Debit Card (Stripe)'},
                        {id: 'paypal', title: 'PayPal'}
                    ];
                }
                
                $.each(self.paymentMethods, function(i, method) {
                    html += '<label class="trm-radio-label">' +
                        '<input type="radio" name="payment_method" value="' + method.id + '" ' + (i === 0 ? 'checked' : '') + '> ' + method.title +
                        '</label>';
                });
                
                $('#payment-methods').html(html);
                $('#payment-section').show();
                
                // Scroll to payment section
                $('html, body').animate({scrollTop: $('#payment-section').offset().top - 100}, 500);
            } else {
                $('#payment-section').hide();
                // Set default payment method
                $('input[name="payment_method"]').val('offline').prop('checked', true);
            }
        },

        /**
         * Show/hide donation section
         */
        showDonationSection: function(showForMember) {
            var self = this;
            
            var populateDonationOptions = function() {
                if (self.donationOptions.length === 0) {
                    // Default donation amounts if not loaded
                    self.donationOptions = [5, 10, 25, 50, 100];
                }
                
                var html = '';
                if (showForMember) {
                    // Members see standard donation options
                    $.each(self.donationOptions, function(i, amount) {
                        html += '<button type="button" class="trm-donation-btn-simple" data-amount="' + amount + '">$' + amount + '</button>';
                    });
                } else {
                    // Non-members see "No Donation" option first, then amounts
                    html += '<button type="button" class="trm-donation-btn-simple" data-amount="0">No Donation (15 min)</button>';
                    $.each(self.donationOptions, function(i, amount) {
                        html += '<button type="button" class="trm-donation-btn-simple" data-amount="' + amount + '">$' + amount + ' (30 min)</button>';
                    });
                }
                html += '<button type="button" class="trm-donation-btn-simple" data-amount="custom">Custom Amount</button>';
                
                $('#donation-options').html(html);
                
                if (showForMember) {
                    $('#donation-title').text('5. Support Our Ministry');
                    $('#donation-message').text('Trinity Revival Ministry is in need of financial support. Please consider making a small donation to help the ministry.');
                } else {
                    var sessionMinutes = self.currentEvent ? self.currentEvent.session_duration : 30;
                    $('#donation-title').text('5. Plant a Seed');
                    if (sessionMinutes > 15) {
                        $('#donation-message').text('As a non-member, your session will be 15 minutes. To extend to ' + sessionMinutes + ' minutes, please plant a seed to assist our ministry\'s growth. This is optional - you can book for 15 minutes without donation.');
                    } else {
                        $('#donation-message').text('We are pleased to offer you our prophetic consultative services. Please plant a seed to assist our ministry\'s growth.');
                    }
                }
                
                $('#donation-section').show();
                // After donation section is shown, update payment section
                setTimeout(function() {
                    self.showPaymentSection();
                }, 100);
                // Scroll to donation section
                $('html, body').animate({scrollTop: $('#donation-section').offset().top - 100}, 500);
            };
            
            populateDonationOptions();
        },

        /**
         * Submit booking
         */
        submitBooking: function() {
            var self = this;
            
            // Get form values
            var eventId = $('#event_id').val();
            var fullName = $('#full_name').val();
            var phone = $('#phone').val();
            var bookingDate = $('#booking_date').val();
            var bookingTime = $('#booking_time').val();
            var isMember = $('input[name="is_member"]:checked').val();
            var donationAmount = $('#donation_amount').val() || 0;
            var email = $('#email').val() || '';
            var paymentMethod = $('input[name="payment_method"]:checked').val() || 'offline';
            
            // Debug logging
            console.log('Form submission values:', {
                eventId: eventId,
                fullName: fullName,
                phone: phone,
                bookingDate: bookingDate,
                bookingTime: bookingTime,
                isMember: isMember,
                donationAmount: donationAmount,
                email: email
            });
            
            // No client-side validation - let server handle it
            // Just proceed with submission
            
            // Show loading
            $('#trm-loading').show();
            $('#submit-booking').prop('disabled', true);

            $.ajax({
                url: self.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'trm_create_booking',
                    event_id: eventId,
                    full_name: fullName,
                    phone: phone,
                    email: email,
                    booking_date: bookingDate,
                    booking_time: bookingTime,
                    is_member: isMember,
                    donation_amount: donationAmount,
                    payment_method: paymentMethod,
                    nonce: self.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var bookingData = response.data;
                        var paymentMethod = $('input[name="payment_method"]:checked').val() || 'offline';
                        
                        // If payment is required and not offline, process payment
                        if (bookingData.require_payment && paymentMethod !== 'offline') {
                            self.processPayment(bookingData.booking_id, bookingData.total_amount, paymentMethod);
                        } else {
                            $('#trm-loading').hide();
                            $('#submit-booking').prop('disabled', false);
                            self.showSuccessMessage(bookingData);
                        }
                    } else {
                        $('#trm-loading').hide();
                        $('#submit-booking').prop('disabled', false);
                        alert('Booking Error: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#trm-loading').hide();
                    $('#submit-booking').prop('disabled', false);
                    console.log('AJAX Error:', status, error);
                    alert('Error creating booking. Please try again.');
                }
            });
        },

        /**
         * Process payment for non-offline methods
         */
        processPayment: function(bookingId, amount, paymentMethod) {
            var self = this;
            
            if (paymentMethod === 'stripe') {
                self.processStripePayment(bookingId, amount);
            } else if (paymentMethod === 'paypal') {
                self.processPayPalPayment(bookingId, amount);
            } else {
                // Fallback - treat as offline
                $('#trm-loading').hide();
                $('#submit-booking').prop('disabled', false);
                self.showSuccessMessage({booking_id: bookingId, total_amount: amount, require_payment: true});
            }
        },

        /**
         * Process Stripe payment (placeholder)
         */
        processStripePayment: function(bookingId, amount) {
            var self = this;
            
            // Create Stripe Checkout session
            $.ajax({
                url: self.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'trm_create_stripe_checkout',
                    nonce: self.nonce,
                    booking_id: bookingId,
                    amount: amount
                },
                success: function(response) {
                    if (response.success && response.data.checkout_url) {
                        // Redirect to Stripe Checkout
                        window.location.href = response.data.checkout_url;
                    } else if (response.success) {
                        // Fallback: payment processed server-side
                        $('#trm-loading').hide();
                        $('#submit-booking').prop('disabled', false);
                        self.showSuccessMessage({booking_id: bookingId, total_amount: amount, require_payment: false});
                    } else {
                        $('#trm-loading').hide();
                        $('#submit-booking').prop('disabled', false);
                        alert('Stripe Error: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    $('#trm-loading').hide();
                    $('#submit-booking').prop('disabled', false);
                    alert('Stripe Error: ' + error);
                }
            });
        },

        /**
         * Process PayPal payment (placeholder)
         */
        processPayPalPayment: function(bookingId, amount) {
            var self = this;
            $.ajax({
                url: self.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'trm_process_payment',
                    nonce: self.nonce,
                    booking_id: bookingId,
                    amount: amount,
                    payment_method: 'paypal'
                },
                success: function(response) {
                    $('#trm-loading').hide();
                    $('#submit-booking').prop('disabled', false);
                    if (response.success) {
                        self.showSuccessMessage({booking_id: bookingId, total_amount: amount, require_payment: false});
                    } else {
                        alert('PayPal Payment Error: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#trm-loading').hide();
                    $('#submit-booking').prop('disabled', false);
                    alert('PayPal Payment Error: ' + error);
                }
            });
        },

        /**
         * Show success message
         */
        showSuccessMessage: function(booking) {
            $('#trm-booking-form').hide();
            
            var duration = booking.session_duration || (this.currentEvent ? this.currentEvent.session_duration : 30);
            var bookedDate = $('#booking_date').val();
            var bookedTime = $('#booking_time').val();
            var eventName = this.currentEvent ? this.currentEvent.title : 'Event';
            
            // Format date for display
            var dateObj = new Date(bookedDate + 'T00:00:00');
            var options = { year: 'numeric', month: 'long', day: 'numeric' };
            var formattedDate = dateObj.toLocaleDateString('en-US', options);
            
            var html = '<div class="trm-confirmation-details-box">' +
                '<h4 style="margin-top: 0; color: #2d5016;">Booking Details</h4>' +
                '<p><strong>Booking ID:</strong> #' + booking.booking_id + '</p>' +
                '<p><strong>Event:</strong> ' + eventName + '</p>' +
                '<p><strong>Booked Date:</strong> ' + formattedDate + '</p>' +
                '<p><strong>Booked Time:</strong> ' + bookedTime + '</p>' +
                '<p><strong>Session Duration:</strong> ' + duration + ' minutes</p>';
            
            if (booking.total_amount > 0) {
                html += '<p><strong>Amount Due:</strong> $' + booking.total_amount.toFixed(2) + '</p>';
                var paymentMethod = $('input[name="payment_method"]:checked').val() || 'offline';
                var methodLabel = paymentMethod === 'offline' ? 'Bank Transfer / Cash' : 
                                   paymentMethod === 'stripe' ? 'Credit/Debit Card (Stripe)' :
                                   paymentMethod === 'paypal' ? 'PayPal' : paymentMethod;
                html += '<p><strong>Payment Method:</strong> ' + methodLabel + '</p>';
            }
            
            html += '<p style="margin-bottom: 0;"><strong>Status:</strong> <span style="color: #28a745;">Confirmed</span></p>' +
                '</div>';

            $('#confirmation-details').html(html);
            $('#success-message').show();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        TRMBookingSimple.init();
    });

})(jQuery);
