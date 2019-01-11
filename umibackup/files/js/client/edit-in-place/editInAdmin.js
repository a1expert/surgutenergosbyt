var editInAdmin = function() {
	var self = this;

	self.enable = function() {
		jQuery(document).bind('keypress', self.bindEvents);
	}

	self.disable = function() {
		jQuery(document).unbind('keypress', self.bindEvents);
	}

	self.bindEvents = function(e) {
		var code = e.charCode || e.keyCode;
		// 68 - D, 100 - d, 1042 - russian "В", 1074 - russian "в"
		if(e.shiftKey && (code == 68 || code == 100 || code == 1042 || code == 1074)) {
			jQuery('#u-quickpanel #edit_menu').each(function (i, node) {
				changeClassName(node);
			});
		}
	}
}

window.editInAdmin = new editInAdmin();