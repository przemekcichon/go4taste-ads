(function( $ ) {
	'use strict';

	// Wait for the DOM to be ready
	$(function() {

		$( document ).on( 'click', '#g4t-test-api-permissions', function () {
			const button = $( this );
			const permissionsResultWrap = $( '#g4t-api-permissions-result' );
			const ajaxUrl = ( 'undefined' !== typeof g4tAdsAdmin && g4tAdsAdmin.ajaxUrl ) ? g4tAdsAdmin.ajaxUrl : window.ajaxurl;
			const permissionsAction = ( 'undefined' !== typeof g4tAdsAdmin && g4tAdsAdmin.permissionsTestAction ) ? g4tAdsAdmin.permissionsTestAction : 'g4t_test_prestashop_permissions';
			const permissionsNonce = ( 'undefined' !== typeof g4tAdsAdmin && g4tAdsAdmin.permissionsNonce ) ? g4tAdsAdmin.permissionsNonce : '';

			if ( ! permissionsResultWrap.length ) {
				return;
			}

			if ( ! ajaxUrl ) {
				permissionsResultWrap.html( '<p class="description" style="color:#a00;">Brak konfiguracji AJAX URL.</p>' );
				return;
			}

			button.prop( 'disabled', true ).text( 'Testing...' );

			$.post( ajaxUrl, {
				action: permissionsAction,
				nonce: permissionsNonce,
			} )
				.done( function ( response ) {
					if ( response && response.success && response.data && response.data.html ) {
						permissionsResultWrap.html( response.data.html );
						return;
					}

					permissionsResultWrap.html( '<p class="description" style="color:#a00;">Blad podczas testu uprawnien.</p>' );
				} )
				.fail( function ( xhr ) {
					let message = 'Nie udalo sie wykonac testu uprawnien.';
					if ( xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ) {
						message = String( xhr.responseJSON.data.message );
					}

					permissionsResultWrap.html( '<p class="description" style="color:#a00;">' + message + '</p>' );
				} )
				.always( function () {
					button.prop( 'disabled', false ).text( 'Test API Permissions' );
				} );
		} );

		if ( 'undefined' === typeof g4tAdsAdmin ) {
			return;
		}

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
