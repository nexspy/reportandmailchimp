(function( $ ) {
	'use strict';
	
	$(document).ready(function() {

		var base_url_download = '/wp-json/charity/region/reports';
		var base_url_donar_download = '/wp-json/charity/donar/reports';
		var filters = [];

		var $txt_from = $('#txt-from-date');
		var $txt_to = $('#txt-to-date');
		let $txt_month = $('#txt-month');
		let $txt_month_to = $('#txt-month-to');
		var $sel_region = $('#sel-charity-region');
        let $sel_charity = $('#sel-charity');
		var $btn_export = $('#btn-export');
		let $btn_donar_export = $('#btn-donar-export');
		var $checkbox_mail = $('#chk-send-notify');
		var $btn_settings = $('#submit');
		var token = $('#token').val();
		$('#token').remove();
		
		/**
		 * Get : filters like date, region
		 */
		function get_filters() {
			var regionMachineName = $sel_region.val();
			var region = prepare_region(regionMachineName);
			var from_date = $txt_from.val();
			var to_date = $txt_to.val();
			var send_mail = $checkbox_mail.prop('checked');

			if (from_date.length <= 0 || to_date.length <= 0) {
				return false;
			}

			filters = {
				'region': region,
				'region_machine_name': regionMachineName,
				'from': from_date,
				'to': to_date,
				'send_mail': (send_mail) ? 'yes' : '',
			};

			return true;
		}

		

		/**
		 * Make correct value for region for searching
		 * @param {string} region 
		 * @returns 
		 */
		function prepare_region(region) {
			var temp = region.split('_');
			for (var i=0; i<temp.length; i++) {
				temp[i] = temp[i].charAt(0).toUpperCase() + temp[i].slice(1);
			}
			region = temp.join(' ');
			return region;
		}

		/**
		 * Download : redirect to download path
		 */
		function download() {
			if (get_filters()) {
				var url_download = base_url_download + '?from=' + filters.from + '&to=' + filters.to + '&region=' + filters.region + '&region_machine_name=' + filters.region_machine_name + '&mail=' + filters.send_mail + '&token=' + token;

				window.location.href = url_download;
			} else {
				console.error('no filters found. check : from_date, to_date, region');
			}
		}

		/**
		 * Download : redirect to download path for donar list
		 */
		 function download_donars(date, charity_id, date_to) {
			if (date.length && charity_id && date_to.length) {
				var url_download = base_url_donar_download + '?date=' + date + '&dateto=' + date_to + '&charity=' + charity_id + '&token=' + token;

				window.location.href = url_download;
			} else {
				console.error('no filters found. check : from_date, to_date, region');
			}
		}

		/**
		 * Validate : all emails if not empty
		 */
		function validate_emails() {
			var $emails = $('.txt-email');
			var output = true;

			$emails.each(function(item, key) {
				var email = $(this).val();
				if (email.length > 0) {
					output = validateEmail(email);
				}
			});

			return output;
		}

		/**
		 * Validate : email is in correct format
		 * @param {string} email 
		 * @returns 
		 */
		function validateEmail(email) {
			const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			return re.test(String(email).toLowerCase());
		}

		/**
		 * Event : export orders using the dates
		 */
		$btn_export.click(function(e) {
			e.preventDefault();

			download();
		});

		/**
		 * Event : export donars for given month and charity
		 */
		$btn_donar_export.click(function(e) {
            e.preventDefault();

            const charity_id = $sel_charity.val();
            const date = $txt_month.val();
			const date_to = $txt_month_to.val();

			download_donars(date, charity_id, date_to);
        });

		/**
		 * Event : block settings if emails are incorrect
		 */
		$btn_settings.submit(function(e) {
			if (validate_emails()) {
				return true;
			} else {
				e.preventDefault();

				console.error('emails are in incorrect formats');
			}
		})

	});

})( jQuery );
