<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:template match="/">
		<html>
			<head>
				<title>
					<xsl:text>&cms-name; - </xsl:text>
					<xsl:value-of select="$header" />
				</title>

				<!-- Global variables -->
				<script type="text/javascript">
					var pre_lang = '<xsl:value-of select="$lang-prefix" />';
				</script>

				<script type="text/javascript" src="/js/jquery/jquery.js" charset="utf-8" />
				<script type="text/javascript" src="/js/jquery/jquery-ui.js" charset="utf-8" />
				<script type="text/javascript" src="/js/jquery/jquery.umipopups.js" charset="utf-8" />
				<script type="text/javascript" src="/js/jquery/jquery.contextmenu.js" charset="utf-8" />

				<!-- Include labels -->
				<script type="text/javascript" src="/ulang/{$iface-lang}/common/content/date/{$module}?js" charset="utf-8" />


				<!-- Umi ui controls -->
				<xsl:if test="/result/data[@type = 'list']">
					<script	type="text/javascript" src="/js/smc/compressed.js"></script>
				</xsl:if>

				<!-- umi ui css -->
				<link type="text/css" rel="stylesheet" href="/styles/common/css/compiled.css"/>
				
				<script type="text/javascript" src="/styles/common/js/file.control.js" charset="utf-8" />
				<script type="text/javascript" src="/styles/common/js/relation.control.js" charset="utf-8" />
				<script type="text/javascript" src="/styles/common/js/permissions.control.js" charset="utf-8" />
				<script type="text/javascript" src="/styles/common/js/symlink.control.js" charset="utf-8" />
				<script type="text/javascript" src="/styles/common/js/permissions.control.js" charset="utf-8" />
				<script type="text/javascript" src="/styles/common/js/utilities.js" charset="utf-8" />

				<script	type="text/javascript" src="/js/cms/admin.js"></script>
				<script	type="text/javascript" src="/js/cms/wysiwyg/wysiwyg.js"></script>
				<script type="text/javascript">
					uAdmin('type', 'tinymce', 'wysiwyg');
					uAdmin({
						'csrf': '<xsl:value-of select="//@csrf" />'
					});
				</script>

				<script type="text/javascript"><![CDATA[
					uAdmin('settings', {
						theme             : "umisimple",
						language          : "ru",
						plugins	          : "advimage,advlink,inlinepopups",
						inline_styles     : false,
						inlinepopups_skin : 'butterfly'
					},'wysiwyg');

					function autoHeightIframe(mode) {
						var eip_page = document.getElementById('eip_page');
						var height = (mode == 'load') ? document.body.scrollHeight : eip_page.offsetHeight;
						if (jQuery(".wysiwyg").length) {
							height += 10;
						}
						height = (height > 500) ? 500 : height;
						frameElement.height = height;
						frameElement.style.height = height;
					}

					function showSubtypes(block, sub_class) {
						var sub_block = document.getElementById('eip_page_subtype_' + sub_class);
						block.parentNode.style.display = 'none';
						sub_block.style.display = 'block';
						autoHeightIframe();
					}

					function hideSubtypes(block) {
						var sub_block = document.getElementById('eip_page_types_choice');
						block.style.display = 'none';
						sub_block.style.display = 'block';
						autoHeightIframe();
					}

					function submitAddPage(type_id) {
						csrfPart = uAdmin.csrf ? '&csrf=' + uAdmin.csrf : '';
						location.href = '?hierarchy-type-id='+type_id+csrfPart;
					}

					function popupCancel() {
						window.parent.$.closePopupLayer(null, {});
					}

					jQuery(document).ready(function(){
						jQuery("fieldset legend a").click(function() {
							var i;
							if(i = this.href.indexOf('#')) {
								var id = this.href.substring(i + 1);
								jQuery("fieldset").children().filter("div").hide();
								jQuery('div#' + id).show();
								autoHeightIframe();
							}
							return false;
						});

						uAdmin.wysiwyg.init();

						jQuery(':input[name=hierarchy-type-id]').click(function () {
							jQuery('.object-types').css('display', 'none');
							jQuery('#object-types-' + this.value).css('display', 'block');
							autoHeightIframe();
						});

						jQuery("img").on("load", function(){
							autoHeightIframe('load');
						})

						autoHeightIframe('load');
					});]]></script>
				
				<style>
					.mceToolbar {
						width: 200px;
					}
					
					.mceLayout {
						display: table;
					}
				</style>

				<!-- <link href="/styles/skins/_eip/css/design.css" rel="stylesheet" type="text/css" /> -->

				<link href="/styles/skins/_eip/css/permissions.control.css" rel="stylesheet" type="text/css" />
				<link href="/styles/skins/_eip/css/relation.control.css" rel="stylesheet" type="text/css" />
				<link href="/styles/skins/_eip/css/symlink.control.css" rel="stylesheet" type="text/css" />
				<link href="/styles/skins/_eip/css/popup.css" rel="stylesheet" type="text/css" />
				<link href="/styles/skins/_eip/css/popup_page.css" rel="stylesheet" type="text/css" />
			</head>
			
			<body>
				<xsl:apply-templates select="$errors" />
				<xsl:apply-templates select="result" />
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>