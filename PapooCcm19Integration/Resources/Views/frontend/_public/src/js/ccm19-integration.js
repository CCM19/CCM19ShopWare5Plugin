;(function($, window, CCM) {
	'use strict';

	if (!CCM) {
		return;
	}

	var parent = ('getCookiePreference' in $ && typeof $.getCookiePreference == 'function') ? $.getCookiePreference : null;

	$.getCookiePreference = function CCM19_getCookiePreference(cookieName) {
		var result = parent ? parent(cookieName) : false;

		if (CCM.acceptedCookies.includes('cookieName')) {
			result = true;
		}

		if (CCM.acceptedEmbeddings) {
			$.each(CCM.acceptedEmbeddings, function (_, embedding) {
				if (embedding.name == cookieName) {
					result = true;
				}
			});
		}

		if (!result && cookieName === 'paypal-cookies') {
			return CCM19_getCookiePreference('__payPalInstallmentsBannerJS_storage__');
		}

		return result;
	}

})(jQuery, window, CCM);
