uAdmin('.wysiwyg', function (extend) {
	function WYSIWYG() {
		this.settings = jQuery.extend(this[this.type].settings, this.settings);
		this[this.type]();
		this.init = this[this.type].init;
	}

	WYSIWYG.prototype.init = function() {
		return false;
	};

	WYSIWYG.prototype.settings = function() {
		return false;
	};

	WYSIWYG.prototype.inline = function() {
		jQuery('<script src="/js/cms/wysiwyg/inline/inlineWYSIWYG.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');
	};

	WYSIWYG.prototype.inline.init = function(node) {
		return new inlineWYSIWYG(node);
	};

	WYSIWYG.prototype.tinymce = function() {
		window.tinyMCEPreInit = {
			suffix : '',
			base : '/js/cms/wysiwyg/tinymce/jscripts/tiny_mce'
		};

		jQuery('<script src="/js/cms/wysiwyg/tinymce/jscripts/tiny_mce/tiny_mce.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');
		/* adding custom settings */
		jQuery('<script src="/js/cms/wysiwyg/tinymce/jscripts/tiny_mce/tinymce_custom.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');
	};

	WYSIWYG.prototype.tinymce.init = function(node) {
		var editor = {}, selector = "textarea.wysiwyg", settings = {};
		if (uAdmin.eip && uAdmin.eip.editor) {
			settings.toolbar_standart = this.settings.toolbar_standart.replace(/^imagemanager,/, '');
			editor = {
				id : 'mceEditor-' + new Date().getTime(),
				destroy : function() {
					tinyMCE.execCommand('mceToggleEditor', false, editor.id);
				}
			};
			node.id = editor.id;
			selector = '#' + editor.id;
		}

		settings.language = uAdmin.data["interface-lang"] || uAdmin.data["lang"];
		settings = jQuery.extend(this.settings, settings);
		/* custom settings */
		settings = jQuery.extend(settings, window.mceCustomSettings);
		tinyMCE.init(settings);

		jQuery(selector).each(function (i, n) {
			tinyMCE.execCommand('mceToggleEditor', false, n.id);
		});
		return editor;
	};

	WYSIWYG.prototype.tinymce.settings = {
		// General options
		mode : "none",
		theme : "umi",
		width : "100%",
		language : typeof window.interfaceLang == 'string' ? interfaceLang : 'ru',
		plugins : "safari,spellchecker,pagebreak,style,layer,table,save,"
			+"advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,"
			+"preview,media,searchreplace,print,contextmenu,paste,directionality,"
			+"fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,imagemanager",

		inlinepopups_skin : 'butterfly',

		toolbar_standart : "imagemanager,fontsettings,tablesettings,|,"
			+"cut,copy,paste,|,pastetext,pasteword,|,selectall,cleanup,|,"
			+ "undo,redo,|,link,unlink,anchor,image,media,|,charmap,code",

		toolbar_tables : "table,delete_table,|,col_after,col_before,"
			+"row_after,row_before,|,delete_col,delete_row,|,"
			+"split_cells,merge_cells,|,row_props,cell_props",

		toolbar_fonts: "formatselect,fontselect,fontsizeselect,|,"
			+ "bold,italic,underline,|,"
			+ "justifyleft,justifycenter,justifyright,justifyfull,|,"
			+ "bullist,numlist,outdent,indent,|,"
			+ "forecolor,backcolor,|,sub,sup",

		theme_umi_toolbar_location : "top",
		theme_umi_toolbar_align : "left",
		theme_umi_statusbar_location : "bottom",
		theme_umi_resize_horizontal : false,
		theme_umi_resizing : true,

		convert_urls : false,
		relative_urls : false,

		file_browser_callback : function(field_name, url, type, win) {
			if (type == 'file') {
				var sTreeLinkUrl = "/js/cms/wysiwyg/tinymce/jscripts/tiny_mce/themes/umi/treelink.html" + (window.lang_id ? "?lang_id=" + window.lang_id : '');
				tinyMCE.activeEditor.windowManager.open({
					url    : sTreeLinkUrl,
					width  : 525,
					height : 308,
					inline         : true,
					scrollbars	   : false,
					resizable      : false,
					maximizable    : false,
					close_previous : false
				}, {
					window    : win,
					input     : field_name,
					editor_id : tinyMCE.selectedInstance.editorId
				});
				return false;
			}
			else {
				var input = win.document.getElementById(field_name),
					params = {}, qs = [];
				if (!input) return false;
				if (input.value.length) {
					params.folder = input.value.substr(0, input.value.lastIndexOf('/'));
					params.file = input.value;
				}
				qs.push("id=" + field_name);
				switch(type) {
					case "image" : qs.push("image=1"); break;
					case "media" : qs.push("media=1"); break;
				}
				jQuery.ajax({
					url: "/admin/data/get_filemanager_info/",
					data: params,
					dataType: 'json',
					success: function(data){
						if (data.filemanager == 'flash') {
							if (input.value.length) {
								qs.push("folder=." + params.folder);
								qs.push("file=" + input.value);
							}
						}
						else {
							qs.push("folder_hash=" + data.folder_hash);
							qs.push("file_hash=" + data.file_hash);
							qs.push("lang=" + data.lang);
						}

						var fm = {
							flash :  {
								height : 460,
								url    : "/styles/common/other/filebrowser/umifilebrowser.html?" + qs.join("&")
							},
							elfinder : {
								height : 530,
								url    : "/styles/common/other/elfinder/umifilebrowser.html?" + qs.join("&")
							}
						};

						jQuery.openPopupLayer({
							name   : "Filemanager",
							title  : getLabel('js-file-manager'),
							width  : 660,
							height : fm[data.filemanager].height,
							url    : fm[data.filemanager].url
						});

						if (data.filemanager == 'elfinder') {

							var options = '<div id="watermark_wrapper"><label for="add_watermark">';
							options += getLabel('js-water-mark');
							options += '</label><input type="checkbox" name="add_watermark" id="add_watermark"/>';
							options += '<label for="remember_last_folder">';
							options += getLabel('js-remember-last-dir');
							options += '</label><input type="checkbox" name="remember_last_folder" id="remember_last_folder"'
							if (getCookie('remember_last_folder', true)) options += 'checked="checked"';
							options +='/></div>';

							window.parent.jQuery('#popupLayer_Filemanager .popupBody').append(options);
						}
						return false;
					}
				});
			}
			return false;
		},// Callbacks

		extended_valid_elements : "script[type=text/javascript|src|languge|lang],map[*],area[*],umi:*[*],input[*],noindex[*],nofollow[*],iframe[frameborder|src|width|height|name|align]", // extend tags and atributes

		content_css : "/css/cms/style.css" // enable custom CSS
	};

	WYSIWYG.prototype.tinymce_umiru = function() {
		window.tinyMCEPreInit = {
			suffix : '_src',
			base : '/js/cms/wysiwyg/tinymce/jscripts/tiny_mce'
		};

		jQuery('<script src="/js/cms/wysiwyg/tinymce/jscripts/tiny_mce/tiny_mce_src.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');
	};

	WYSIWYG.prototype.tinymce_umiru.init = function(node) {
		var editor, selector = "textarea.wysiwyg", settings = {};
		if (uAdmin.eip && uAdmin.eip.editor) {
			settings.toolbar_standart = this.settings.toolbar_standart.replace(/^imagemanager,/, '');
			editor = {
				id : 'mceEditor-' + new Date().getTime(),
				destroy : function() {
					var oldNode = jQuery('#' + editor.id),
						newNode = jQuery('#' + editor.id + '_parent'),
						frame = jQuery('iframe', newNode)[0],
						content;
					if (jQuery.browser.msie && (typeof document.documentMode == 'undefined' || document.documentMode < 7)) {
						content = window.frames[window.frames.length-1].document.body.innerHTML;
					}
					else content = frame.contentDocument.body.innerHTML;
					oldNode.html(content);
					newNode.remove();
					oldNode.css('display','');
					oldNode[0].id = '';
				}
			};
			node.id = editor.id;
			selector = '#' + editor.id;
		}

		settings.language = uAdmin.data["interface-lang"] || uAdmin.data["lang"];
		settings = jQuery.extend(this.settings, settings);
		tinyMCE.init(settings);

		jQuery(selector).each(function (i, n) {
			tinyMCE.execCommand('mceAddControl', false, n.id);
		});
		return editor;
	};

	WYSIWYG.prototype.tinymce_umiru.settings = {

		// General options
		mode : "none",
		theme : "umiru",
		language : typeof window.interfaceLang == 'string' ? interfaceLang : 'ru',
		width : "100%",
		suffix : "_src",

		body_class : "text",

		theme_umi_resizing_use_cookie : false,
		init_instance_callback : "uAdmin.wysiwyg.initInstance", //trigger event on editor instance creation
		theme_umi_path : false, //dispable path control
		//constrain_menus : true,
		constrain_menus : false,
		extended_valid_elements : "script[src|*],style[*],map[*],area[*],umi:*[*],input[*],noindex[*],nofollow[*],iframe[frameborder|src|width|height|name|align],div[*],span[*],a[*]", // extend tags and atributes
		plugins : "safari,spellchecker,pagebreak,style,layer,table,save,advhr,umiimage,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

		inlinepopups_skin : 'butterfly',

		setup : function(ed) {
			function resize (ed, l) {
				jQuery(ed.getContainer()).children('table.mceLayout').eq(0).css('height', 'auto');
				//select iframe element
				var i = jQuery(ed.getContentAreaContainer()).children('iframe')[0];
				//select body of iframe
				var h = i.contentWindow.document.body;
				//.parent() doesn't work in IE properly
				iHeight = Math.max(jQuery(h).parent().outerHeight(), jQuery(h).outerHeight())
				//set iframe heigth to height of html inside
				i.style.height = iHeight + 'px';

				$('img', ed.getBody()).load(function () {
					resize(ed, l);
				});
			};
			ed.onChange.add(resize);
			ed.onKeyDown.add(resize);
			ed.onLoadContent.add(function(ed, o) {
				if(o.content == "&nbsp;" || o.content == " ") {
					ed.setContent("");
				}
				ed.focus();

				/**
				 * Поиск с возвращением: поиск первой ноды с текстом
				 */
				function backTrackTextnode(node) {
					if(node.nodeType == 3) return node;

					var subnodes = $(node).contents();

					for(var bkt = 0; bkt < subnodes.length; bkt++) {
						var result = backTrackTextnode(subnodes[bkt]);
						if(result) return result;
					}

					return false;
				}

				var nodes_all = ed.dom.select('body');
				var node = backTrackTextnode(nodes_all[0]);
				if(!node) {
					if(ed.dom.select('body *').length > 0) {
						node = ed.dom.select('body *')[0];
					} else {
						node = nodes_all[0];
					}
				} else {
					node = node.parentNode;
				}

				var rng = ed.selection.getRng();
				if(!rng || typeof rng.selectNode == "undefined") return;
				var tn = ed.getDoc().createTextNode(".");
				node.insertBefore(tn, node.firstChild);


				rng.selectNode(tn);
				rng.setStartBefore(tn);
				rng.setStartAfter(tn);

				ed.selection.setRng(rng);

				node.removeChild(tn);

				//Передвигаем панельку с кнопками туда, куда кликнули
				var panel = $('#' + ed.editorContainer + ' .toolbarHolder');
				var panelWidth = 1110;
				var bodyWidth = $('body').width();
				panel.css('position', 'fixed');
				panel.css('top', 40);
				if(bodyWidth > panelWidth) {
					panel.css('left', (bodyWidth - panelWidth)/2);
				}else{
					panel.css('left', (bodyWidth - 800)/2);
				}
			});
		},


		toolbar_standart : "umiimage,tablesettings,|,"
			+ "pastetext,pasteword,|,cleanup,|,"
			+ "link,unlink,|,"
			+ "charmap,code",

		toolbar_tables : "table,delete_table,|,col_after,col_before,row_after,row_before,|,delete_col,delete_row,|,split_cells,merge_cells,|,row_props,cell_props",

		toolbar_fonts: "formatselect,fontselect,fontsizeselect,|,"
			+ "bold,italic,underline,|,"
			+ "justifyleft,justifycenter,justifyright,justifyfull,|,"
			+ "bullist,numlist,outdent,indent,|,"
			+ "forecolor,backcolor,|,"
			+ "sub,sup",


		theme_umi_toolbar_location : "top",
		theme_umi_toolbar_align : "left",
		theme_umi_statusbar_location : "bottom",
		theme_umi_resize_horizontal : false,
		theme_umi_resizing : false,

		convert_urls : false,
		relative_urls : false,

		// Example content CSS (should be your site CSS)
		//content_css : "css/example.css",

		// Callbacks
		file_browser_callback : "uAdmin.wysiwyg.umiFileBrowserCallback",


		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "js/template_list.js",
		external_link_list_url : '',
		external_image_list_url : '',
		media_external_list_url : ''
	};

	WYSIWYG.prototype.tinymce4 = function() {
		window.tinyMCEPreInit = {
			suffix : '.min',
			base : '/js/cms/wysiwyg/tinymce4'
		};

		jQuery('<script src="/js/cms/wysiwyg/tinymce4/tinymce.min.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');
		jQuery('<script src="/js/cms/wysiwyg/tinymce4/plugins/compat3x/plugin.min.js" type="text/javascript" charset="utf-8"></script>').appendTo('head');

		var jqToolbarHolder = jQuery('<div/>')
			.addClass('toolbarHolder')
			.css({
				position: 'fixed',
				top: '40px',
				display: 'none'
			})
			.appendTo("body");

		var repositionToolbarHolder = function (editor) {
			if (!editor || editor instanceof tinymce.Editor === false) editor = tinymce && tinymce.activeEditor;
			if (!editor) return false;
			var iDocWidth = jQuery(document).width(),
				iPanelWidth = Math.min(iDocWidth * 0.9, 1025),
				iLeft = (iDocWidth - iPanelWidth) / 2;
			var jqPanel = jQuery(editor.theme.panel.getEl());
			jqToolbarHolder.width(iPanelWidth).offset({left: iLeft}).draggable().css('cursor', 'move');
			jqPanel.find(".mce-toolbar").css('display', 'inline-block').parent().css('white-space', 'normal');
			if (jQuery.draggable) {
				jqPanel.draggable();
			}
		};

		jQuery(document).add(window).on('resize', function(oEvent){
			if (tinymce && tinymce.activeEditor) {
				repositionToolbarHolder(tinymce.activeEditor);
			}
		});

		tinymce.on('AddEditor', function(oEvent){
			oEvent.editor.on('ShowPanel', function(oEvent){
				repositionToolbarHolder(oEvent.target);
				window.setTimeout(function(){ jQuery(".toolbarHolder").show() }, 0);
			});
		});

	};

	WYSIWYG.prototype.tinymce4.init = function(node) {
		var editor, selector = "textarea.wysiwyg", settings = {};
		if (uAdmin.eip && uAdmin.eip.editor) {
			editor = {
				id : 'mceEditor-' + new Date().getTime(),
				destroy : function() {
					tinymce && tinymce.activeEditor && tinymce.activeEditor.destroy();
				}
			};
			node.id = editor.id;
			selector = '#' + editor.id;
			settings.fixed_toolbar_container = ".toolbarHolder";
			tinymce.on("AddEditor", function(oEvent){
				oEvent.editor
					.on('init', function(oEvent){
						this.fire("focus");
					});
			});
		}

		settings.language = uAdmin.data["interface-lang"] || uAdmin.data["lang"];
		settings = jQuery.extend(this.settings, settings);
		/* custom settings */
		settings = jQuery.extend(settings, window.mceCustomSettings);
		settings.selector = selector;
		tinymce.init(settings);
		return editor;
	};

	WYSIWYG.prototype.tinymce4.settings = {

		// General options
		inline : true,
		theme : "modern",
		skin : 'darkgray',
		language : typeof window.interfaceLang == 'string' ? interfaceLang : 'ru',
		suffix : ".min",
		schema: "html4",
		paste_as_text: true,
		convert_urls: false,
		toolbar_items_size: 'small',

		extended_valid_elements : "script[src|*],style[*],map[*],area[*],umi:*[*],input[*],noindex[*],nofollow[*],iframe[frameborder|src|width|height|name|align],div[*],span[*],a[*]", // extend tags and atributes
		plugins : "umiimage,spellchecker,pagebreak,layer,table,save,hr,image,link,emoticons,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,template,anchor,charmap,code,textcolor",

		inlinepopups_skin : 'butterfly',


		toolbar1 : "image table | paste pastetext | removeformat | link unlink | charmap code",
		toolbar2 : "formatselect fontselect fontsizeselect",
		toolbar3 : "bold italic underline",
		toolbar4 : "alignleft aligncenter alignright alignjustify",
		toolbar5 : "bullist numlist outdent indent",
		toolbar6 : "forecolor backcolor",
		toolbar7 : "subscript superscript",

		block_formats: getLabel("js-wysiwyg-paragraph")+"=p;Address=address;Pre=pre;Header 1=h1;Header 2=h2;Header 3=h3;Header 4=h4;Header 5=h5;Header 6=h6",

		menubar: false,
		statusbar: false,
		resize: false,
		object_resizing : false,

		convert_urls : false,
		relative_urls : false,

		// Callbacks
		file_browser_callback : function(){ uAdmin.wysiwyg.umiFileBrowserCallback.apply(uAdmin.wysiwyg, arguments) },


		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "js/template_list.js",
		external_link_list_url : '',
		external_image_list_url : '',
		media_external_list_url : ''

	};


	WYSIWYG.prototype.initInstance = function (inst) {

		//Auto add styles into iframe document body, inherited from real element
		jQuery('div.toolbarHolder').draggable();
		var el = jQuery(inst.getElement());
		var iframeBody = jQuery(inst.getDoc()).find('body').eq(0);
		var attrArray = ['font-family','font-size','font-weight','font-style','color',
						 'text-transform','text-decoration','letter-spacing','word-spacing',
						 'line-height','text-align','vertical-align','direction','background-color',
						 'background-image','background-repeat','background-position',
						 'background-attachment','opacity','top','right','bottom',
						 'left','padding-top','padding-right','padding-bottom','padding-left',
						 'overflow-x','overflow-y','white-space',
						 'clip','list-style-image','list-style-position',
						 'list-style-type','marker-offset'];
		for (var i in attrArray) {
			iframeBody.css(attrArray[i], el.css(attrArray[i]));
		}

		function getInternetExplorerVersion() {
			// Returns the version of Internet Explorer or a -1
			// (indicating the use of another browser).
			var rv = -1; // Return value assumes failure.
			if (navigator.appName == 'Microsoft Internet Explorer') {
				var ua = navigator.userAgent;
				var re  = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
				if (re.exec(ua) != null)
					rv = parseFloat( RegExp.$1 );
			}
			return rv;
		}
		var ieVersion = getInternetExplorerVersion();
		if(ieVersion > -1 && ieVersion <= 8.0) {
			iframeBody.css('background-color', '');
		}
		iframeBody.css('height', 'auto');
		var containerAttrArray = ['margin-top','margin-right','margin-bottom','margin-left'];
		jQuery('#' + inst.editorContainer).css('display', 'block');
		for (var j in containerAttrArray) {
			jQuery('#' + inst.editorContainer).css(containerAttrArray[j], el.css(containerAttrArray[j]));
		}

		//Auto adding line-height when changing size of font
		inst.formatter.register({
			fontsize : {inline : 'span', styles : {fontSize : '%value', 'line-height' : '1.3em'}}
		});

		//Remove alert when toggling "Insert as text" button
		var cookie = tinymce.util.Cookie;
		cookie.set("tinymcePasteText", "1", new Date(new Date().getFullYear() + 1, 12, 31));

	};


	WYSIWYG.prototype.umiFileBrowserCallback = function (field_name, url, type, win) {

		switch (type) {
			case "file"  : uAdmin.wysiwyg.umiTreeLink(field_name, url, type, win); break;
			case "image" :
			case "media" :

				var input = win.document.getElementById(field_name);
				if(!input) return false;
				var folder = '';
				var file = '';
				if(input.value.length) {
					folder = input.value.substr(0, input.value.lastIndexOf('/'));
					file = input.value;
				}

				jQuery.ajax({
					url: "/admin/data/get_filemanager_info/",
					data: "folder=" + folder + '&file=' + file,
					dataType: 'json',
					complete: function(data){
						data = jQuery.parseJSON(data.responseText);
						var folder_hash = data.folder_hash;
						var file_hash = data.file_hash;
						var lang = data.lang;
						var fm = data.filemanager;

						var functionName = 'uAdmin.wysiwyg.umi' + fm + 'FileManager';
						eval(functionName + '(field_name, url, type, win, lang, folder_hash, file_hash)');
					}
				});
				break;
		}
		return false;

	};


	WYSIWYG.prototype.umielfinderFileManager = function (field_name, url, type, win, lang, folder_hash, file_hash) {

		var qs    = [];
		qs.push("id=" + field_name);
		switch(type) {
			case "image" :qs.push("image=1");break;
			case "media" :qs.push("media=1");break;
		}

		qs.push("folder_hash=" + folder_hash);
		qs.push("file_hash=" + file_hash);
		qs.push("lang=" + lang);

		$.openPopupLayer({
			name   : "Filemanager",
			title  : getLabel('js-file-manager'),
			width  : 660,
			height : 530,
			url    : "/styles/common/other/elfinder/umifilebrowser.html?"+ qs.join("&")
		});

		if (tinymce && tinymce.activeEditor && tinymce.activeEditor.settings.inline) {
			jQuery('#popupLayer_Filemanager .popupBody').append('<div id="watermark_wrapper"><label for="add_watermark">' + getLabel('js-water-mark') + '</label><input type="checkbox" name="add_watermark" id="add_watermark"></div>');
		} else {
			window.parent.jQuery('#popupLayer_Filemanager .popupBody').append('<div id="watermark_wrapper"><label for="add_watermark">' + getLabel('js-water-mark') + '</label><input type="checkbox" name="add_watermark" id="add_watermark"></div>');
		}

		return false;

	};


	WYSIWYG.prototype.umiflashFileManager = function (field_name, url, type, win, lang, folder_hash, file_hash) {

		var input = win.document.getElementById(field_name);
		if(!input) return false;
		var qs    = [];
		qs.push("id=" + field_name);
		switch(type) {
			case "image" :qs.push("image=1");break;
			case "media" :qs.push("media=1");break;
		}
		if(input.value.length) {
			var folder = input.value.substr(0, input.value.lastIndexOf('/'));
			qs.push("folder=." + folder);
			qs.push("file=" + input.value);
		}
		$.openPopupLayer({
			name   : "Filemanager",
			title  : getLabel('js-file-manager'),
			width  : 660,
			height : 460,
			url    : "/styles/common/other/filebrowser/umifilebrowser.html?" + qs.join("&")
		});
		return false;

	};


	WYSIWYG.prototype.umiTreeLink = function (field_name, url, type, win) {

		var domain_floated    = window.pageData ? window.pageData.domain : '';
		var domain_floated_id = window.pageData ? window.pageData.domain_id : '';
		var lang_id           = window.pageData.lang_id;
		var sTreeLinkUrl = "";
		var iPageHight = 0;
		if (tinyMCE.majorVersion < 4) {
			sTreeLinkUrl = "/js/cms/wysiwyg/tinymce/jscripts/tiny_mce/themes/umi/treelink.html?domain="+domain_floated+"&domain_id=" + domain_floated_id + "&lang_id="+lang_id;
			iPageHight = 308;
		} else {
			sTreeLinkUrl = "/js/cms/wysiwyg/tinymce4/skins/lightgray/treelink.html?domain="+domain_floated+"&domain_id=" + domain_floated_id + "&lang_id="+lang_id;
			iPageHight = 320;
		}
		tinyMCE.activeEditor.windowManager.open({
			url    : sTreeLinkUrl,
			title  : getLabel('js-choose-page'),
			width  : 525,
			height : iPageHight,
			inline         : true,
			scrollbars	   : false,
			resizable      : false,
			maximizable    : false,
			close_previous : false
		}, {
			window    : win,
			input     : field_name,
			editor_id : tinyMCE.activeEditor.id
		});
		return false;

	};


	WYSIWYG.prototype.getFilemanagerFooter = function (filemanager) {

		var footer = "";

		if (filemanager == 'elfinder') {
			footer = '<div id="watermark_wrapper" class="ui-widget-header">';
			footer += '<label for="remember_last_folder">';
			footer += getLabel('js-remember-last-dir');
			footer += '</label><input type="checkbox" name="remember_last_folder" id="remember_last_folder"'
			if (jQuery && jQuery.cookie && jQuery.cookie('remember_last_folder')) footer += 'checked="checked"';
			footer +='/></div>';
		};

		return footer;

	};

	return extend(WYSIWYG, this);
});

uAdmin.onLoad('wysiwyg', function(){

	uAdmin.wysiwyg.curr_mouse_position = {
		top: 0,
		left: 0
	};

	$('body').click(function(e) {
		uAdmin.wysiwyg.curr_mouse_position = {
			top : e.pageY - window.pageYOffset,
			left : e.pageX - window.pageXOffset
		}
	});

});