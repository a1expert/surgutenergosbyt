uAdmin('.tickets', function (extend) {
	function uTickets() {
		var ticket = function (params) {
			var self = this;
			self.params = params;

			(function init() {
				self.node = jQuery('<div class="u-ticket" />').appendTo('body');
				if (params.message) self.createMessage();
				self.update();
			})();
		};

		ticket.prototype.resetSelection = function () {
			if (document.selection && document.selection.empty) {
				document.selection.empty();
			}
			else if(window.getSelection) {
				var sel = window.getSelection();
				if(sel && sel.removeAllRanges) {
					sel.removeAllRanges();
				}
			}
		};

		ticket.prototype.createMessage = function () {
			var self = this;
			self.messageNode = jQuery('<div class="u-ticket-comment"><div /><textarea /><a /></div>').appendTo('body');

			if (self.params.message) {
				jQuery('div', self.messageNode).html(self.params.message.authorName + ' (' + self.params.message.authorLogin + ')');
				jQuery('textarea', self.messageNode).attr('value', self.params.message.text);
			}

			jQuery('a', self.messageNode).html(getLabel('js-ticket-delete'));

			jQuery('textarea', self.messageNode).bind('change', function () {
				self.save();
			});

			jQuery('a', self.messageNode).bind('click', function () {
				self.del();
			});
		};

		ticket.prototype.del = function () {
			var self = this;
			if (this.node) this.node.remove();
			if (this.messageNode) this.messageNode.remove();
			if (self.params.id) {
				var url = '/content/tickets/delete/' + self.params.id + '/';
				jQuery.get(url);
			}
		};

		ticket.prototype.save = function () {
			var self = this;
			var mode = self.params.id ? 'modify' : 'create';
			var url = '/content/tickets/' + mode + '/' + self.params.id + '/';
			url += '?ts=' + Math.round(Math.random() * 1000);
			jQuery.ajax({
				type: 'POST',
				url: url,
				dataType: 'json',
				data: {
					x: self.params.x,
					y: self.params.y,
					width: self.params.width,
					height: self.params.height,
					message: jQuery('textarea', self.params.messageNode).attr('value'),
					referer: window.location.toString()
				},
				success: function (resp) {
					self.params.id = resp.id;
				}
			});
		};

		ticket.prototype.update = function () {
			var self = this;
			self.node.css({
				top: parseInt(self.params.y), left: parseInt(self.params.x),
				width: self.params.width, height: self.params.height,
				opacity: 0.3
			});

			if (self.messageNode) {
				self.messageNode.css({
					top: parseInt(self.params.y) + parseInt(self.params.height),
					left: parseInt(self.params.x) + parseInt(self.params.width)
				});
			}
			// Отображение заметок выключено, прячем
			if (uAdmin.tickets.disabled) {
				jQuery(self.node).add(self.messageNode).hide();
			}
		};

		ticket.prototype.listen = function () {
			var self = this, xFirst = self.params.x, yFirst = self.params.y, xLast, yLast;

			jQuery(document).bind('mousemove', function (event) {
				self.resetSelection();
				xLast = event.pageX;
				yLast = event.pageY;
				if (xLast > xFirst) {
					self.params.width = xLast - xFirst;
				}
				else {
					xLast = xFirst;
					self.params.x = event.pageX;
					self.params.width = xLast - self.params.x;
				}
				if (yLast > yFirst) {
					self.params.height = yLast - yFirst;
				}
				else {
					yLast = yFirst;
					self.params.y = event.pageY;
					self.params.height = yLast - self.params.y;
				}
				self.update();
			});

			jQuery(document).one('mouseup', function () {
				jQuery(document).unbind('mousemove');
				self.update();
				self.save();
				uAdmin.tickets.isInit = false;
			});
		};
		this.ticket = ticket;
	};

	uTickets.prototype.initNewTicket = function () {
		if(!uAdmin.tickets.created) {
			alert(getLabel('js-panel-note-add'));
			uAdmin.tickets.created = true;
		}

		if (uAdmin.tickets.isInit) return false;

		uAdmin.tickets.isInit = true;
		uAdmin.panel.changeAct(jQuery('#u-quickpanel #note').get(0));
		jQuery(document).one('mousedown', function (event) {
			var newTicket = new uAdmin.tickets.ticket({
				x: event.pageX,
				y: event.pageY,
				width: 0,
				height: 0,
				message: {
					authorName: uAdmin.tickets.user.fname + ' ' + uAdmin.tickets.user.lname,
					authorLogin: uAdmin.tickets.user.login,
					text: getLabel('js-ticket-empty')
				}
			});
			newTicket.listen();
			uAdmin.panel.changeAct(jQuery('#u-quickpanel #note').get(0));
		});
	};

	uTickets.prototype.swapVisible = function() {
		this.disabled ? this.enable() : this.disable();
	};

	uTickets.prototype.disable = function () {
		var self = this;
		jQuery('div.u-ticket, div.u-ticket-comment, #u-quickpanel #note').hide();
		jQuery(document).unbind('keydown', self.bindEvents);
		jQuery('#u-quickpanel #note').unbind('click', self.bindEvents);
		self.disabled = true;
	};

	uTickets.prototype.enable = function () {
		var self = this;
		jQuery('div.u-ticket, div.u-ticket-comment, #u-quickpanel #note').show();
		jQuery(document).bind('keydown', self.bindEvents);
		jQuery('#u-quickpanel #note').bind('click', self.bindEvents);
		self.disabled = false;
	};

	uTickets.prototype.bindEvents = function (event) {
		if ((event.shiftKey && (event.keyCode == 67 || event.keyCode == 99)  && (event.target.nodeName != 'INPUT' && event.target.nodeName != 'TEXTAREA')) || (event.type=='click' && document.getElementById('note').id=='note')) {
			uAdmin.tickets.initNewTicket();
		}
	};

	uTickets.prototype.draw = function(data) {
		uAdmin.tickets.user = data.user;
		var tick;
		for (tick in data.tickets.ticket) {
			tick = data.tickets.ticket[tick];
			var pos = tick.position,
				author = tick.author;

			var t = new this.ticket({
				id: tick.id,
				x: pos.x,
				y: pos.y,
				width: pos.width,
				height: pos.height,
				message: {
					authorName: author.fname + ' ' + author.lname,
					authorLogin: author.login,
					text: tick.message
				}
			});
			t.update();
		}
	};

	uTickets.prototype.disabled = true;

	uTickets.prototype.isInit = false;

	return extend(uTickets, this);
});