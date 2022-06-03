;(function($, window, CCM) {
	'use strict';

	if (!CCM) {
		return;
	}

	try {
		if (!('_shopwareQueriedCookies' in CCM)) {
			CCM._shopwareQueriedCookies = {};
		}
	} finally {
		// ignore
	}

	var parent = ('getCookiePreference' in $ && typeof $.getCookiePreference == 'function') ? $.getCookiePreference : null;

	$.getCookiePreference = function CCM19_getCookiePreference(cookieName) {
		var result = parent ? parent(cookieName) : false;

		if (CCM.acceptedCookies.includes(cookieName)) {
			result = true;
		}

		if (CCM.acceptedEmbeddings) {
			$.each(CCM.acceptedEmbeddings, function (_, embedding) {
				if (embedding.name == cookieName) {
					result = true;
				}
			});
		}

		if ('_shopwareQueriedCookies' in CCM) {
			CCM._shopwareQueriedCookies[cookieName] = result;
		}

		if (!result) {
			if (cookieName === 'paypal-cookies') {
				return CCM19_getCookiePreference('__payPalInstallmentsBannerJS_storage__'); // Compatibility with PayPal plugin
			}else if (cookieName == 'dtgsAllowGtmTracking') {
				return CCM19_getCookiePreference('_gcl_au'); // Compatibility with codiverse Google Tag Manager plugin
			}
			else if (cookieName == 'mnd_facebook_pixel') {
				return CCM19_getCookiePreference('_fbp'); // Compatibility with MND Facebook Pixel plugin
			}
			else if (cookieName == 'Tawk') {
				return CCM19_getCookiePreference('__tawkuuid'); // Compatibility with Tawk.to
			}

		}

		return result;
	};

	// Generate swCookieConsentManager events on closing the CCM19 widget
	if ('addEventListener' in window) {
		window.addEventListener('ccm19WidgetClosed', function () {
			if ('swCookieConsentManager' in $.fn) {
				var swCMObject = $.fn.swCookieConsentManager();
				var uniqueNames = [];
				var preferences = { groups: {CCM19: {
					name: 'CCM19 Compatibility Layer',
					cookies: {}
				} }, hash: '' };

				var embeddings = ('acceptedEmbeddings' in CCM) ? CCM.acceptedEmbeddings : [];
				var cookies = CCM.acceptedCookies;
				for (var i = 0; i < embeddings.length; ++i) {
					uniqueNames.push(embeddings[i].name);
					preferences.groups.CCM19.cookies[embeddings[i].name] = {name: embeddings[i].name, active: true};
				}
				for (var i = 0; i < cookies.length; ++i) {
					uniqueNames.push(cookies[i]);
					preferences.groups.CCM19.cookies[cookies[i]] = {name: cookies[i], active: true};
				}

				uniqueNames.sort();
				preferences.hash = window.btoa(JSON.stringify(uniqueNames));

				$.publish('plugin/swCookieConsentManager/onBuildCookiePreferences', [swCMObject, preferences]);
				$.publish('plugin/swCookieConsentManager/onSave', [swCMObject]);
			}
		});
	}

	// This is neccessary for some plugins that check for the existence of the Shopware cookie manager opener
	$('<a href="#" aria-hidden="true" class="is--hidden" style="display: none" data-openconsentmanager="true"></a>').appendTo($('.ccm-root .ccm-settings-summoner'));

})(jQuery, window, window.CCM);
