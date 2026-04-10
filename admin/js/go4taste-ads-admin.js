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
		let lastKnownOffset = 0;
		let consecutiveStalledFailures = 0;

		function getJobOffset( job ) {
			if ( ! job ) {
				return 0;
			}

			return Number( job.offset || job.posts_scanned || 0 );
		}

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
			lastKnownOffset = getJobOffset( job );

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

					consecutiveStalledFailures = 0;

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

		function unlockStalledJob() {
			appendLog( 'Brak postepu migracji. Zdejmuje lock i odblokowuje przycisk...', true );
			$.post( g4tAdsAdmin.ajaxUrl, {
				action: g4tAdsAdmin.migrationUnlockAction,
				nonce: g4tAdsAdmin.migrationNonce,
			} )
				.done( function () {
					appendLog( 'Lock migracji zostal wyczyszczony. Mozesz uruchomic ponownie.' );
				} )
				.fail( function ( xhr ) {
					const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
						? xhr.responseJSON.data.message
						: 'Nie udalo sie wyczyscic locka migracji.';
					appendLog( message, true );
				} )
				.always( function () {
					running = false;
					consecutiveStalledFailures = 0;
					lastKnownOffset = 0;
					migrationStartButton.prop( 'disabled', false ).text( 'Run Migration' );
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

					const previousOffset = lastKnownOffset;
					renderStats( response.data.job );
					if ( 'running' === response.data.job.status ) {
						const statusOffset = getJobOffset( response.data.job );
						if ( statusOffset <= previousOffset ) {
							consecutiveStalledFailures++;
						} else {
							consecutiveStalledFailures = 0;
						}

						if ( consecutiveStalledFailures >= 3 ) {
							unlockStalledJob();
							return;
						}

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

		const syncForm = $( '#g4t-sync-form' );
		if ( ! syncForm.length ) {
			return;
		}

		const syncStartButton = $( '#g4t-sync-start' );
		const syncProgressWrap = $( '#g4t-sync-progress' );
		const syncProgressBar = $( '#g4t-sync-progress-bar' );
		const syncProgressText = $( '#g4t-sync-progress-text' );
		const syncProgressStats = $( '#g4t-sync-progress-stats' );
		const syncLog = $( '#g4t-sync-log' );

		let syncRunning = false;
		let lastKnownSyncOffset = 0;
		let consecutiveStalledSyncFailures = 0;

		function getSyncOffset( job ) {
			if ( ! job ) {
				return 0;
			}

			return Number( job.offset || 0 );
		}

		function appendSyncLog( message, isError ) {
			const safeMessage = String( message || '' );
			const row = $( '<div />' )
				.addClass( isError ? 'g4t-log-row g4t-log-row--error' : 'g4t-log-row' )
				.text( safeMessage );
			syncLog.prepend( row );
		}

		function renderSyncStats( job ) {
			const total = Number( job.total || 0 );
			const offset = Number( job.offset || 0 );
			const percent = total > 0 ? Math.min( 100, Math.round( ( offset / total ) * 100 ) ) : 0;
			lastKnownSyncOffset = getSyncOffset( job );

			syncProgressBar.css( 'width', percent + '%' );
			syncProgressText.text( `Sync: ${percent}% (${offset}/${total})` );
			syncProgressStats.html(
				[
					`<li>Synced: <strong>${ Number( job.synced || 0 ) }</strong></li>`,
					`<li>Errors: <strong>${ Number( job.errors || 0 ) }</strong></li>`,
				].join( '' )
			);
		}

		function unlockStalledSyncJob() {
			appendSyncLog( 'Brak postepu sync. Zdejmuje lock i odblokowuje przycisk...', true );
			$.post( g4tAdsAdmin.ajaxUrl, {
				action: g4tAdsAdmin.syncUnlockAction,
				nonce: g4tAdsAdmin.syncNonce,
			} )
				.done( function () {
					appendSyncLog( 'Lock sync zostal wyczyszczony. Mozesz uruchomic ponownie.' );
				} )
				.fail( function ( xhr ) {
					const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
						? xhr.responseJSON.data.message
						: 'Nie udalo sie wyczyscic locka sync.';
					appendSyncLog( message, true );
				} )
				.always( function () {
					syncRunning = false;
					consecutiveStalledSyncFailures = 0;
					lastKnownSyncOffset = 0;
					syncStartButton.prop( 'disabled', false ).text( 'Sync Products' );
				} );
		}

		function resumeExistingSyncJob( contextMessage ) {
			if ( contextMessage ) {
				appendSyncLog( contextMessage );
			}

			$.post( g4tAdsAdmin.ajaxUrl, {
				action: g4tAdsAdmin.syncStatusAction,
				nonce: g4tAdsAdmin.syncNonce,
			} )
				.done( function ( response ) {
					if ( ! response || ! response.success || ! response.data || ! response.data.job ) {
						syncRunning = false;
						syncStartButton.prop( 'disabled', false ).text( 'Sync Products' );
						appendSyncLog( 'Nie udalo sie odczytac statusu sync.', true );
						return;
					}

					const previousOffset = lastKnownSyncOffset;
					renderSyncStats( response.data.job );
					if ( 'running' === response.data.job.status ) {
						const syncOffset = getSyncOffset( response.data.job );
						if ( syncOffset <= previousOffset ) {
							consecutiveStalledSyncFailures++;
						} else {
							consecutiveStalledSyncFailures = 0;
						}

						if ( consecutiveStalledSyncFailures >= 3 ) {
							unlockStalledSyncJob();
							return;
						}

						syncRunning = true;
						syncStartButton.prop( 'disabled', true ).text( 'Sync Running...' );
						appendSyncLog( 'Wznowiono aktywny sync.' );
						setTimeout( runSyncBatch, 500 );
						return;
					}

					syncRunning = false;
					syncStartButton.prop( 'disabled', false ).text( 'Sync Products' );
					appendSyncLog( 'Brak aktywnego sync joba. Mozesz uruchomic ponownie.' );
				} )
				.fail( function () {
					syncRunning = false;
					syncStartButton.prop( 'disabled', false ).text( 'Sync Products' );
					appendSyncLog( 'Nie udalo sie wznowic sync po bledzie.', true );
				} );
		}

		function runSyncBatch() {
			if ( ! syncRunning ) {
				return;
			}

			$.post( g4tAdsAdmin.ajaxUrl, {
				action: g4tAdsAdmin.syncBatchAction,
				nonce: g4tAdsAdmin.syncNonce,
			} )
				.done( function ( response ) {
					if ( ! response || ! response.success || ! response.data || ! response.data.job ) {
						syncRunning = false;
						syncStartButton.prop( 'disabled', false ).text( 'Sync Products' );
						appendSyncLog( 'Sync batch response error.', true );
						return;
					}

					renderSyncStats( response.data.job );
					if ( response.data.done ) {
						syncRunning = false;
						syncStartButton.prop( 'disabled', false ).text( 'Sync Products' );
						appendSyncLog( 'Sync zakonczony.' );
						return;
					}

					setTimeout( runSyncBatch, 150 );
				} )
				.fail( function ( xhr ) {
					const statusCode = xhr && xhr.status ? Number( xhr.status ) : 0;
					if ( 409 === statusCode ) {
						appendSyncLog( 'Wykryto aktywny job sync. Proba wznowienia...' );
						resumeExistingSyncJob();
						return;
					}

					syncRunning = false;
					syncStartButton.prop( 'disabled', false ).text( 'Sync Products' );
					const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
						? xhr.responseJSON.data.message
						: 'Sync batch request failed.';
					appendSyncLog( message, true );
					resumeExistingSyncJob( 'Po bledzie batch probuje wznowic sync...' );
				} );
		}

		syncStartButton.on( 'click', function () {
			if ( syncRunning ) {
				return;
			}

			syncRunning = true;
			syncStartButton.prop( 'disabled', true ).text( 'Sync Running...' );
			syncProgressWrap.show();
			syncProgressBar.css( 'width', '0%' );
			syncProgressText.text( 'Starting sync...' );
			syncProgressStats.empty();
			syncLog.empty();

			const batchSize = Number( $( '#g4t-sync-batch-size' ).val() || 20 );
			$.post( g4tAdsAdmin.ajaxUrl, {
				action: g4tAdsAdmin.syncStartAction,
				nonce: g4tAdsAdmin.syncNonce,
				batch_size: batchSize,
			} )
				.done( function ( response ) {
					if ( ! response || ! response.success || ! response.data || ! response.data.job ) {
						syncRunning = false;
						syncStartButton.prop( 'disabled', false ).text( 'Sync Products' );
						appendSyncLog( 'Sync start response error.', true );
						return;
					}

					renderSyncStats( response.data.job );
					appendSyncLog( 'Sync uruchomiony.' );
					runSyncBatch();
				} )
				.fail( function ( xhr ) {
					const statusCode = xhr && xhr.status ? Number( xhr.status ) : 0;
					if ( 409 === statusCode ) {
						appendSyncLog( 'Wykryto aktywny job sync. Proba wznowienia...' );
						resumeExistingSyncJob();
						return;
					}

					syncRunning = false;
					syncStartButton.prop( 'disabled', false ).text( 'Sync Products' );
					const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
						? xhr.responseJSON.data.message
						: 'Sync start request failed.';
					appendSyncLog( message, true );
				} );
		} );

	});

})( jQuery );
