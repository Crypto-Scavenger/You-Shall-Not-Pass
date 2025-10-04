/**
 * Admin JavaScript for You Shall Not Pass
 *
 * @package YouShallNotPass
 */

(function($) {
	'use strict';
	
	$(document).ready(function() {
		$('.ysnp-color-picker').wpColorPicker();
		
		$('.nav-tab').on('click', function(e) {
			e.preventDefault();
			
			var targetTab = $(this).data('tab');
			
			$('.nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');
			
			$('.ysnp-tab-content').removeClass('ysnp-tab-active');
			$('#' + targetTab).addClass('ysnp-tab-active');
		});
	});
	
})(jQuery);
