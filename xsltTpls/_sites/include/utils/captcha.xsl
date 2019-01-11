<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="udata[@module = 'system' and @method = 'captcha']" />
	<xsl:template match="udata[@module = 'system' and @method = 'captcha' and count(url)]">
		<td colspan="2">
			<div style="overflow:auto; width:100%;">
				<div style="float:left; padding:10px 0px 10px 20px;">Символы на картинке<br />
					<input name="captcha" type="text" style="width:620px; height:20px; background:#FFFFFF; border:1px solid #CCCCCC; color:#191970;" maxlength="12" />
				</div>
				<div style="float:right; padding:10px 20px 10px 0px;">
					<img src="{url}{url/@random-string}" id="captcha_img" />
				</div>
			</div>
		</td>
	</xsl:template>

</xsl:stylesheet>