(function( $ ) {
	'use strict';

	/**
	 * This script handles the dynamic display of the PrestaShop API password field
	 * based on the selection of the API type from the settings page.
	 * 
	 * When 'PrestaShop' is selected from the API type dropdown, the password field
	 * will be shown. Otherwise, it will remain hidden.
	 */

	// Wait for the DOM to be ready
	$(function() {

		// Get the API type dropdown (select element)
		const apiTypeSelector = $('#api_type');

		// Get the row containing the password field for PrestaShop API
		const passwordField = $('#p_parameter_value').closest('tr');

		// Function to toggle the visibility of the password field based on the selected API
		function togglePasswordField() {
			// Check if the selected API type is PrestaShop (either version 1.4 or 1.5)
			if (apiTypeSelector.val() === 'prestashop_1_4' || apiTypeSelector.val() === 'prestashop_1_5') {
				passwordField.show(); // Show the password field if PrestaShop is selected
			} else {
				// If not PrestaShop, hide the password field
				passwordField.hide(); // Hide the password field
			}
		}

		// Call the function initially to set the correct visibility on page load
		togglePasswordField();

		// Add an event listener that watches for changes in the API type dropdown
		// Calls togglePasswordField() every time the selected API type changes
		apiTypeSelector.on('change', togglePasswordField);

	});

})( jQuery );
