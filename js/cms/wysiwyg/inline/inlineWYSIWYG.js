function inlineWYSIWYG(node) {
	var self = this;
	self.targetNode = node;
	self.toolboxNode = null;

	if (typeof selections == 'undefined') {
		jQuery('\n\
			<script src="/js/cms/wysiwyg/inline/selections.js" type="text/javascript" charset="utf-8"></script>\n\
			<script src="/js/cms/wysiwyg/inline/std_buttons.js" type="text/javascript" charset="utf-8"></script>\n\
		').appendTo('head');
	}

	(function init() {
		self.toolboxNode = document.createElement('div');
		jQuery(self.toolboxNode).attr('class', 'eip-wysiwyg-toolbox eip-ui-element eip-wysiwyg_buttons');
		document.body.appendChild(self.toolboxNode);
		uAdmin.eip.placeWith(self.targetNode, self.toolboxNode, 'top', 'left');
		jQuery(self.toolboxNode).draggable();

		for (var i in self.buttons) {
			self.callButton(i, 'init', {
				'toolbox' : self.toolboxNode,
				'editor'  : self
			});
		}

		self.sels = new selections(window, self.targetNode);

		jQuery(self.targetNode).bind('click', function (event) {
			var e = event || window.event;
			inlineWYSIWYG.select = {node:(e.target || e.srcElement),params:{}};
			self.checkButtons();
		});

		jQuery(self.targetNode).bind('keyup', function () {
			self.checkButtons();
		});

		if(!self.targetNode.firstChild || self.targetNode.firstChild.tagName != 'P') {
			var html = jQuery.trim(jQuery(self.targetNode).html());
			jQuery(self.targetNode).html('<p>' + html + '</p>');
		}
	})();
};

inlineWYSIWYG.prototype.getToolBox = function () {
	return this.toolboxNode;
};

inlineWYSIWYG.prototype.checkButtons = function () {
	var self = this, i, selectedNode = self.sels.getNode();

	var checkedTags = new Array();
	for(i in self.buttons) {
		var status = self.callButton(i, 'status');
		var node = jQuery('.eip-wysiwyg_button_' + i);
		if(node.size()) {
			if(status) node.addClass('act'); else node.removeClass('act');
		}

		var requireTag = self.buttons[i].requireTag;
		if(requireTag) {
			var n, isOk = false;

			if(selectedNode) {
				for(n in requireTag) {
					if (typeof checkedTags[requireTag[n]] != 'undefined') {
						isOk = checkedTags[requireTag[n]];
						break;
					}

					if (self.seekTag(selectedNode, requireTag[n])) {
						isOk = true;
						checkedTags[requireTag[n]] = true;
						break;
					}
					else checkedTags[requireTag[n]] = false;
				}
			}

			node.css('display', isOk ? '' : 'none');
		}
	}
};

inlineWYSIWYG.prototype.callButton = function (name, funcName, params) {
	var self = this;

	if (self.buttons[name]) {
		var func = self.buttons[name][funcName];

		if(typeof func == 'function') {
			var result = func(params, this.targetNode, this.sels);
			if(funcName == 'execute') {
				setTimeout(function () {
					self.checkButtons();
				}, 150);
			}
			return result;
		}
	}
	else alert("Button \"" + name + "\" not found");
	return false;
};


inlineWYSIWYG.prototype.refocus = function () {
	this.targetNode.focus();
};


inlineWYSIWYG.prototype.destroy = function () {
	jQuery(this.toolboxNode).remove();
	jQuery(this.targetNode).unbind('click').unbind('keyup').unbind('mouseover').unbind('mouseout');
};

inlineWYSIWYG.prototype.seekTag = function (node, tagName) {
	do {
		if(!node) return false;
		if(node.nodeType != 1) continue;
		if(node.tagName == tagName) return node;
		if(jQuery(node).hasClass('u-eip-editing')) return false;

	} while(node = node.parentNode);
	return false;
};

inlineWYSIWYG.prototype.toolboxNode = null;
inlineWYSIWYG.prototype.targetNode = null;

inlineWYSIWYG.prototype.buttons = [];

inlineWYSIWYG.button = function (name, params) {
	inlineWYSIWYG.prototype.buttons[name] = params;
};

inlineWYSIWYG.createSimple = function (inName, inParams) {
	var self = this;
	self.button(inName, {
		init: function (params) {
			var button = self.createSimpleButton(params['editor'], inName, inName);
			jQuery(button).attr({
				'value':		inParams['button-label'],
				'title':		inParams['button-title']
			});
			if(inParams['letter']) {
				jQuery(button).html(inParams['letter']);
			}
		},

		execute: function (params, targetNode, sels) {
			var selectedNode = sels.getNode();

			if(jQuery(selectedNode).attr('umi:field-name') || selectedNode == targetNode) {
				return false;
			}

			var align, aligns = {JustifyLeft: 'left', JustifyCenter: 'center', JustifyRight: 'right'};
			if(align = aligns[inName]) {
				if(targetNode.firstChild == selectedNode && selectedNode.tagName == 'P') {
					jQuery(selectedNode).css('text-align', align);
					return true;
				}
			}

			setTimeout(function () {
				try {
					return document.execCommand(inName, false, null);
				} catch (exception) {
					return false;
				}
			}, 110);
		},

		status: function () {
			try {
				return document.queryCommandState(inName);
			} catch (exception) {
				return false;
			}
		},

		params: inParams
	});
};

inlineWYSIWYG.createSimpleButton = function (editor, name, prefix, noSelReset) {
	var node = document.createElement('a');
	jQuery(node).attr({
		'href':			'#',
		'class':	'eip-wysiwyg_button_' + prefix,
		'type':			'button'
	});
	editor.getToolBox().appendChild(node);

	noSelReset = true;	//Fixed lost selection bug, check if no more problems earned.
	var _editor = editor, _name = name;
	jQuery(node).bind('click', function () {
		_editor.callButton(_name, 'execute');
		return false;
	});

	return node;
};
