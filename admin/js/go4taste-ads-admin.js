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

		const migrationForm = $( '#g4t-migration-form' );
		if ( ! migrationForm.length || 'undefined' === typeof g4tAdsAdmin ) {
			return;
		}

		const migrationStartButton = $( '#g4t-migration-start' );
		const progressWrap = $( '#g4t-migration-progress' );
		const progressBar = $( '#g4t-migration-progress-bar' );
		const progressText = $( '#g4t-migration-progress-text' );
		const progressStats = $( '#g4t-migration-progress-stats' );
		const migrationLog = $( '#g4t-migration-log' );

		let running = false;

		function appendLog( message, isError ) {
			const safeMessage = String( message || '' );
			const row = $( '<div />' )
				.addClass( isError ? 'g4t-log-row g4t-log-row--error' : 'g4t-log-row' )
				.text( safeMessage );
			migrationLog.prepend( row );
		}

		function renderStats( job ) {
			const total = Number( job.total || 0 );
			const scanned = Number( job.posts_scanned || 0 );
			const percent = total > 0 ? Math.min( 100, Math.round( ( scanned / total ) * 100 ) ) : 0;

			progressBar.css( 'width', percent + '%' );
			progressText.text(
				`Postep: ${percent}% (${scanned}/${total}) | Tryb: ${job.dry_run ? 'dry run' : 'write'}`
			);

			progressStats.html(
				[
					`<li>Matched: <strong>${ Number( job.posts_matched || 0 ) }</strong></li>`,
					`<li>Changed: <strong>${ Number( job.posts_changed || 0 ) }</strong></li>`,
					`<li>Converted groups: <strong>${ Number( job.groups_converted || 0 ) }</strong></li>`,
					`<li>Inline banners: <strong>${ Number( job.inline_banners_created || 0 ) }</strong></li>`,
					`<li>Skipped candidates: <strong>${ Number( job.posts_skipped_candidates || 0 ) }</strong></li>`,
					`<li>Errors: <strong>${ Number( job.errors || 0 ) }</strong></li>`,
				].join( '' )
			);
		}

		function collectPayload() {
			const postTypes = [];
			migrationForm.find( 'input[name="go4taste_migrate_post_types[]"]:checked' ).each( function () {
				postTypes.push( $( this ).val() );
			} );

			const postStatuses = [];
			migrationForm.find( 'input[name="go4taste_migrate_post_statuses[]"]:checked' ).each( function () {
				postStatuses.push( $( this ).val() );
			} );

			return {
				action: g4tAdsAdmin.migrationStartAction,
				nonce: g4tAdsAdmin.migrationNonce,
				dry_run: migrationForm.find( 'input[name="go4taste_migrate_dry_run"]' ).is( ':checked' ) ? 1 : 0,
				limit: migrationForm.find( 'input[name="go4taste_migrate_limit"]' ).val() || 0,
				target_post: migrationForm.find( 'input[name="go4taste_migrate_target_post"]' ).val() || '',
				batch_size: migrationForm.find( 'input[name="go4taste_migrate_batch_size"]' ).val() || 20,
				post_types: postTypes,
				post_statuses: postStatuses,
			};
		}

		function runBatch() {
			if ( ! running ) {
				return;
			}

			$.post( g4tAdsAdmin.ajaxUrl, {
				action: g4tAdsAdmin.migrationBatchAction,
				nonce: g4tAdsAdmin.migrationNonce,
			} )
				.done( function ( response ) {
					if ( ! response || ! response.success || ! response.data || ! response.data.job ) {
						running = false;
						migrationStartButton.prop( 'disabled', false ).text( 'Run Migration' );
						appendLog( 'Batch response error.', true );
						return;
					}

					renderStats( response.data.job );
					if ( response.data.done ) {
						running = false;
						migrationStartButton.prop( 'disabled', false ).text( 'Run Migration' );
						appendLog( 'Migracja zakonczona.' );
						return;
					}

					setTimeout( runBatch, 250 );
				} )
				.fail( function ( xhr ) {
					running = false;
					const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
						? xhr.responseJSON.data.message
						: 'Batch request failed.';
					appendLog( message, true );
					resumeExistingJob( 'Po bledzie batch probuje wznowic...' );
				} );
		}

		function resumeExistingJob( contextMessage ) {
			if ( contextMessage ) {
				appendLog( contextMessage );
			}

			$.post( g4tAdsAdmin.ajaxUrl, {
				action: g4tAdsAdmin.migrationStatusAction,
				nonce: g4tAdsAdmin.migrationNonce,
			} )
				.done( function ( response ) {
					if ( ! response || ! response.success || ! response.data || ! response.data.job ) {
						running = false;
						migrationStartButton.prop( 'disabled', false ).text( 'Run Migration' );
						appendLog( 'Nie udalo sie odczytac statusu migracji.', true );
						return;
					}

					renderStats( response.data.job );
					if ( 'running' === response.data.job.status ) {
						running = true;
						migrationStartButton.prop( 'disabled', true ).text( 'Migration Running...' );
						appendLog( 'Wznowiono aktywna migracje.' );
						setTimeout( runBatch, 500 );
						return;
					}

					running = false;
					migrationStartButton.prop( 'disabled', false ).text( 'Run Migration' );
					appendLog( 'Brak aktywnego joba. Mozesz uruchomic migracje ponownie.' );
				} )
				.fail( function () {
					running = false;
					migrationStartButton.prop( 'disabled', false ).text( 'Run Migration' );
					appendLog( 'Nie udalo sie wznowic migracji po bledzie.', true );
				} );
		}

		migrationStartButton.on( 'click', function () {
			if ( running ) {
				return;
			}

			running = true;
			migrationStartButton.prop( 'disabled', true ).text( 'Migration Running...' );
			progressWrap.show();
			progressBar.css( 'width', '0%' );
			progressText.text( 'Starting migration...' );
			progressStats.empty();
			migrationLog.empty();

			$.post( g4tAdsAdmin.ajaxUrl, collectPayload() )
				.done( function ( response ) {
					if ( ! response || ! response.success || ! response.data || ! response.data.job ) {
						running = false;
						migrationStartButton.prop( 'disabled', false ).text( 'Run Migration' );
						appendLog( 'Start response error.', true );
						return;
					}

					renderStats( response.data.job );
					appendLog( 'Migracja uruchomiona.' );
					runBatch();
				} )
				.fail( function ( xhr ) {
					const statusCode = xhr && xhr.status ? Number( xhr.status ) : 0;
					if ( 409 === statusCode ) {
						appendLog( 'Wykryto aktywny job migracji. Proba wznowienia...' );
						resumeExistingJob();
						return;
					}

					running = false;
					migrationStartButton.prop( 'disabled', false ).text( 'Run Migration' );
					const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
						? xhr.responseJSON.data.message
						: 'Start request failed.';
					appendLog( message, true );
				} );
		});

	});

})( jQuery );
