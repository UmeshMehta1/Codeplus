/**
 * Url span
 * @author    Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @copyright (c) 17.11.2017, Webcraftic
 * @version 1.0
 */


(function($) {
	'use strict';

	$(function() {
		$(document).on("click", ".wbcr-clearfy-pseudo-link", function() {
			window.open($(this).data("uri"));
		});
	})

})(jQuery);
