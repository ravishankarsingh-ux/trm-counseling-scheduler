/**
 * TRM Booking Form - Event Selection & Booking Logic
 */

(function($) {
    'use strict';

    var TRMBooking = {
        nonce: trmCounseling.nonce || '',
        ajaxUrl: trmCounseling.ajax_url || '',
        events: [],
        currentEvent: null,
        currentBooking: null,

        /**
         * Initialize booking form
         */
        init: function() {
            this.loadEvents();
            this.bindEvents();
        },

        /**
         * Load events via AJAX
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
                        TRMBooking.events = response.data.events;
                        TRMBooking.populateEventDropdown();
                    }
                },
                error: function() {
                    console.error('Failed to load events');
                }
            });
        },

        /**
         * Populate event dropdown
         */
        populateEventDropdown: function() {
            var $select = $('#event_id');
            
            if (this.events.length === 0) {
                $select.append('<option value="">No events available</option>');
                return;
            }

            var html = '';
            $.each(this.events, function(index, event) {
                html += '<option value="' + event.id + '">' + event.title + '</option>';
            });

            $select.append(html);
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            var self = this;

            // Event selection change
            $(document).on('change', '#event_id', function() {
                var eventId = $(this).val();
                
                if (eventId) {
                    var event = self.findEvent(eventId);
                    if (event) {
                        self.currentEvent = event;
                        $('#event-description').html(
                            '<strong>' + event.type + '</strong><br>' +
                            'Capacity: ' + event.capacity + ' | ' +
                            'Duration: ' + event.session_duration + ' min | ' +
                            'Fee: $' + (event.booking_fee || 0)
                        );
                        $('#event-next').prop('disabled', false);
                    }
                } else {
                    self.currentEvent = null;
                    $('#event-description').html('');
                    $('#event-next').prop('disabled', true);
                }
            });

            // Event selection next button
            $(document).on('click', '#event-next', function() {
                self.goToStep(1);
            });

            // Date selection change
            $(document).on('change', '#booking_date', function() {
                var date = $(this).val();
                if (date && self.currentEvent) {
                    self.loadAvailableSlots(date);
                }
            });

            // Step navigation
            $(document).on('click', '.trm-btn-next', function() {
                var step = self.getCurrentStep();
                self.goToStep(step + 1);
            });

            $(document).on('click', '.trm-btn-back', function() {
                var step = self.getCurrentStep();
                self.goToStep(step - 1);
            });

            // Membership selection handling
            $(document).on('change', 'input[name="is_member"]', function() {
                var isMember = $(this).val();
                
                // Show appropriate next step based on membership
                if (isMember === '1') {
                    // Member - show member donation step
                    setTimeout(function() {
                        self.goToMemberDonationStep();
                    }, 500);
                } else {
                    // Non-member - show non-member donation step
                    setTimeout(function() {
                        self.goToNonMemberDonationStep();
                    }, 500);
                }
            });

            // Time slot selection
            $(document).on('click', '.trm-time-slot', function() {
                $('.trm-time-slot').removeClass('selected');
                $(this).addClass('selected');
                $('#step1-next').prop('disabled', false);
            });

            // Form submission
            $(document).on('submit', '#trm-booking-form', function(e) {
                e.preventDefault();
                self.submitBooking();
            });

            // Booking button
            $(document).on('click', '#confirm-booking', function() {
                self.submitBooking();
            });

            // Donation amount selection (Members)
            $(document).on('click', '.trm-donation-btn', function() {
                var amount = $(this).data('amount');
                $('.trm-donation-btn').removeClass('selected');
                $(this).addClass('selected');
                
                if (amount === 'custom') {
                    $('.trm-custom-amount-wrapper').show();
                } else {
                    $('.trm-custom-amount-wrapper').hide();
                    $('#donation_amount').val(amount);
                    $('#continue-donation-member').prop('disabled', false);
                }
            });

            // Custom donation amount (Members)
            $(document).on('input', '#custom_donation', function() {
                var amount = $(this).val();
                if (amount > 0) {
                    $('#donation_amount').val(amount);
                    $('#continue-donation-member').prop('disabled', false);
                }
            });

            // Skip donation (Members)
            $(document).on('click', '#skip-donation-member', function() {
                $('#donation_amount').val(0);
                self.goToStep(6);
            });

            // Continue with donation (Members)
            $(document).on('click', '#continue-donation-member', function() {
                self.goToStep(6);
            });

            // Donation amount selection (Non-Members)
            $(document).on('click', '.trm-donation-btn-nm', function() {
                var amount = $(this).data('amount');
                $('.trm-donation-btn-nm').removeClass('selected');
                $(this).addClass('selected');
                
                if (amount === 'custom') {
                    $('.trm-custom-amount-wrapper-nm').show();
                } else {
                    $('.trm-custom-amount-wrapper-nm').hide();
                    $('#donation_amount').val(amount);
                    $('#continue-donation-nonmember').prop('disabled', false);
                }
            });

            // Custom donation amount (Non-Members)
            $(document).on('input', '#custom_donation_nm', function() {
                var amount = $(this).val();
                if (amount > 0) {
                    $('#donation_amount').val(amount);
                    $('#continue-donation-nonmember').prop('disabled', false);
                }
            });

            // Skip donation (Non-Members)
            $(document).on('click', '#skip-donation-nonmember', function() {
                $('#donation_amount').val(0);
                self.goToWarningStep();
            });

            // Plant a seed (Non-Members)
            $(document).on('click', '#continue-donation-nonmember', function() {
                self.goToFinalStep();
            });

            // Add seed later
            $(document).on('click', '#add-seed-later', function() {
                self.goToNonMemberDonationStep();
            });

            // Skip seed (Non-members)
            $(document).on('click', '#skip-seed-final', function() {
                self.goToFinalStep();
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
         * Get current step
         */
        getCurrentStep: function() {
            var step = 0;
            $('.trm-step').each(function() {
                if ($(this).is(':visible')) {
                    step = parseInt($(this).attr('class').match(/trm-step-(\d+)/)[1]);
                }
            });
            return step;
        },

        /**
         * Go to step
         */
        goToStep: function(stepNum) {
            $('.trm-step').hide();
            var stepClass = '.trm-step-' + stepNum;
            if ($(stepClass).length) {
                $(stepClass).show();
            }
        },

        /**
         * Go to member donation step
         */
        goToMemberDonationStep: function() {
            $('.trm-step').hide();
            $('.trm-step-4-member').show();
        },

        /**
         * Go to non-member donation step
         */
        goToNonMemberDonationStep: function() {
            $('.trm-step').hide();
            $('.trm-step-4-nonmember').show();
        },

        /**
         * Go to warning step (15-minute notice)
         */
        goToWarningStep: function() {
            $('.trm-step').hide();
            $('.trm-step-5-limited').show();
        },

        /**
         * Go to final confirmation step
         */
        goToFinalStep: function() {
            $('.trm-step').hide();
            $('.trm-step-final').show();
        },

        /**
         * Load available time slots
         */
        loadAvailableSlots: function(date) {
            var self = this;
            
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
                    if (response.success) {
                        self.displayTimeSlots(response.data.slots);
                    } else {
                        $('#time-slots-container').html(
                            '<p class="trm-error">Error loading time slots: ' + response.data.message + '</p>'
                        );
                    }
                },
                error: function() {
                    $('#time-slots-container').html(
                        '<p class="trm-error">Error loading time slots</p>'
                    );
                }
            });
        },

        /**
         * Display time slots
         */
        displayTimeSlots: function(slots) {
            var html = '';
            
            if (slots.length === 0) {
                $('#time-slots-container').html(
                    '<p class="trm-note">No available slots for this date</p>'
                );
                return;
            }

            $.each(slots, function(index, slot) {
                html += '<button type="button" class="trm-time-slot" data-slot="' + slot + '">' + slot + '</button>';
            });

            $('#time-slots-container').html(html);
        },

        /**
         * Submit booking
         */
        submitBooking: function() {
            var self = this;
            
            // Validate required fields
            var fullName = $('#full_name').val();
            var phone = $('#phone').val();
            var bookingDate = $('#booking_date').val();
            var $selectedSlot = $('.trm-time-slot.selected');
            var bookingTime = $selectedSlot.data('slot');

            if (!fullName || !phone || !bookingDate || !bookingTime) {
                alert('Please fill in all required fields');
                return;
            }

            var isMember = $('input[name="is_member"]:checked').val() || 0;
            var donationAmount = $('#donation_amount').val() || 0;
            var email = $('#email').val() || '';

            var data = {
                action: 'trm_create_booking',
                event_id: self.currentEvent.id,
                full_name: fullName,
                phone: phone,
                email: email,
                booking_date: bookingDate,
                booking_time: bookingTime,
                is_member: isMember,
                donation_amount: donationAmount,
                payment_method: 'offline',
                nonce: self.nonce
            };

            $('#trm-loading').show();

            $.ajax({
                url: self.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    $('#trm-loading').hide();
                    
                    if (response.success) {
                        self.currentBooking = response.data;
                        self.showSuccessMessage();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    $('#trm-loading').hide();
                    alert('Error creating booking');
                }
            });
        },

        /**
         * Show success message
         */
        showSuccessMessage: function() {
            $('.trm-step').hide();
            $('.trm-step-success').show();
            
            var html = '<p><strong>Booking ID:</strong> #' + this.currentBooking.booking_id + '</p>' +
                '<p><strong>Event:</strong> ' + this.currentEvent.title + '</p>' +
                '<p><strong>Duration:</strong> ' + this.currentBooking.session_duration + ' minutes</p>';
            
            if (this.currentBooking.total_amount > 0) {
                html += '<p><strong>Amount Due:</strong> $' + this.currentBooking.total_amount + '</p>';
            }

            $('#confirmation-details').html(html);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        TRMBooking.init();
    });

})(jQuery);
