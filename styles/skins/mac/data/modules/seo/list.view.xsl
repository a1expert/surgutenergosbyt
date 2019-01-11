<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/seo" [
	<!ENTITY sys-module        'seo'>
	<!ENTITY sys-method-add        'add'>
	<!ENTITY sys-method-edit    'edit'>
	<!ENTITY sys-method-del        'del'>

]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="result[@method = 'islands']/data">
		<script type="text/javascript"><![CDATA[
			var seoCreateIsland = function() {
				for (var id in oTable.selectedList) {
					var h = '<form target="_blank" action="/admin/seo/island_get/' + id + '/" id="island_form" method="get">';
							h += '<div><input type="radio" name="as_file" checked="checked" id="island_link" value="0" /><label for="island_link">' + getLabel('js-seo-island-getlink') + '</label></div>';
							h += '<div><input type="radio" name="as_file" id="island_file" value="1" /><label for="island_file">' + getLabel('js-seo-island-getfile') + '</label></div>';
						h += '</form>';

					openDialog({
						title      : getLabel('js-seo-island'),
						text       : h,
						OKCallback : function () {
							window.location.href = "/admin/seo/island_get/" + id + "/?as_file=" + $('#island_form input[name=as_file]:checked').val() + "&r=" + Math.random();
							closeDialog();
						}
					});

					break;
				}
			}
		]]></script>
		<div class="imgButtonWrapper">
			<a href="{$lang-prefix}/admin/&sys-module;/island_add/">
				<xsl:text>&label-add-island;</xsl:text>
			</a>
			<a href="#" id="doIsland" onclick = "seoCreateIsland(); return false;" style="background: url(/images/cms/admin/mac/ico_exchange.png) no-repeat 0 0;">
				<xsl:text>&label-create-island-xml;</xsl:text>
			</a>
		</div>
		<xsl:call-template name="ui-smc-table">
			<xsl:with-param name="control-params" select="$method" />
			<xsl:with-param name="content-type">objects</xsl:with-param>
			<xsl:with-param name="js-add-buttons">
				createAddButton(
					$('#doIsland')[0],	oTable, '#', ['*']
				);
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
</xsl:stylesheet>