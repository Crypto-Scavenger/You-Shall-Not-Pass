/**
 * Admin scripts for Add Some Solt
 *
 * @package AddSomeSolt
 * @since   1.0.0
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		var toggleButton = $('#ass-toggle-keys');
		var keysDisplay = $('#ass-keys-display');
		var frequencySelect = $('#schedule_frequency');
		var dayRow = $('#schedule_day_row');
		var dayDescription = $('#day_description');

		toggleButton.on('click', function() {
			if (keysDisplay.is(':visible')) {
				keysDisplay.slideUp();
				toggleButton.text('Show Keys');
			} else {
				keysDisplay.slideDown();
				toggleButton.text('Hide Keys');
			}
		});

		function updateDayFieldDescription() {
			var frequency = frequencySelect.val();
			var dayField = $('#schedule_day');
			
			dayRow.show();
			
			switch(frequency) {
				case 'daily':
					dayRow.hide();
					break;
				case 'weekly':
					dayField.empty();
					var days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
					for (var i = 1; i <= 7; i++) {
						dayField.append($('<option></option>').attr('value', i).text(days[i-1]));
					}
					dayDescription.text('Day of the week to change keys');
					break;
				case 'monthly':
					dayField.empty();
					for (var i = 1; i <= 31; i++) {
						dayField.append($('<option></option>').attr('value', i).text(i));
					}
					dayDescription.text('Day of the month to change keys');
					break;
				case 'quarterly':
					dayField.empty();
					for (var i = 1; i <= 31; i++) {
						dayField.append($('<option></option>').attr('value', i).text(i));
					}
					dayDescription.text('Day of the month (January, April, July, October)');
					break;
				case 'biannually':
					dayField.empty();
					for (var i = 1; i <= 31; i++) {
						dayField.append($('<option></option>').attr('value', i).text(i));
					}
					dayDescription.text('Day of the month (January and July)');
					break;
			}
		}

		frequencySelect.on('change', updateDayFieldDescription);
		updateDayFieldDescription();
	});

})(jQuery);