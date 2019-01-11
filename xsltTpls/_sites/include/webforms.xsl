<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="udata[@module = 'webforms'][@method = 'add']" mode="webform">
    	<xsl:apply-templates select="document('udata://system/listErrorMessages/')/udata[items]" />
		<form method="post" action="/webforms/send/">
		    <!-- необходимо передать идентификатор формы -->
    		<input type="hidden" name="system_form_id" value="{@form_id}" />
            <input type="hidden" name="system_email_to" value="27546" />
		    <!-- этот парамет указывает куда совершать редирект -->
    		<!-- в случае успешной отправки сообщения -->
    		<input type="hidden" name="ref_onsuccess" value="/webforms/posted/" />

			<table style="width:800px; text-align:left;">
				<tr>
					<xsl:apply-templates select=".//field[@type = 'string' and @name = 'first_name']" />
					<xsl:apply-templates select=".//field[@type = 'string' and @name = 'last_name']" />
				</tr>
				<tr>
					<xsl:apply-templates select=".//field[@type = 'string' and @name = 'email_addr']" />
					<xsl:apply-templates select=".//field[@type = 'string' and @name = 'phone']" />
				</tr>
				<tr>
					<xsl:apply-templates select=".//field[@type = 'text']" />
				</tr>
				<tr>
					<xsl:apply-templates select="document('udata://system/captcha/')/udata" />
				</tr>
				<tr>
					<td colspan="2">
						<div style="padding:5px 20px 15px 20px; text-align:center;">
							Нажимая кнопку «Отправить» Вы подтверждаете <a href="http://www.surgutenergosbyt.ru/about_us/agreement/" target="_blank">согласие на обработку своих персональных данных.</a><br /><br />
							<input type="submit" value="Отправить" style="cursor:pointer; width:200px; height:35px; font:15px tahoma;" />
						</div>
					</td>
				</tr>
			</table>

		</form>
	</xsl:template>

	<!-- оформление полей формы -->
	<xsl:template match="field[@type = 'string' and @name = 'first_name' or @name = 'last_name' or @name = 'email_addr' or @name = 'phone']">
		<td>
			<div style="padding:8px 20px 0px 20px;"><xsl:value-of select="@title"/></div>
			<div style="padding:1px 20px 0px 20px;">
				<input name="{@input_name}" type="text" style="width:358px; height:20px; background:#FFFFFF; border:1px solid #CCCCCC; color:#191970;" maxlength="50" />
			</div>
		</td>
	</xsl:template>
                  
	<xsl:template match="field[@type = 'text']">
		<td colspan="2">
			<div style="padding:8px 20px 0px 20px;"><xsl:value-of select="@title"/></div>
			<div style="padding:1px 20px 0px 20px;">
				<textarea name="{@input_name}" style="width:765px; height:100px; background:#FFFFFF; border:1px solid #CCCCCC; color:#191970;" />
			</div>
		</td>
	</xsl:template>

	<!-- страница успешной отправки сообщения -->
	<xsl:template match="result[@module = 'webforms'][@method = 'posted']" >
		<div class="content">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="text" style="padding-top:15px;">
				<xsl:text>Ваше сообщение успешно отправлено. В ближайшее время наши специалисты свяжутся с Вами.</xsl:text>
			</div>
			<h2><xsl:apply-templates select="document('upage://(news).h1')//value" /></h2>
			<div class="tumbs">
				<xsl:apply-templates select="document('udata://news/lastlist/(news)/notemplate/3/1')/udata/items/item[position() = 1]" mode="news1" />
				<xsl:apply-templates select="document('udata://news/lastlist/(news)/notemplate/3/1')/udata/items/item[position() = 2]" mode="news2" />
				<xsl:apply-templates select="document('udata://news/lastlist/(news)/notemplate/3/1')/udata/items/item[position() = 3]" mode="news3" />
				<div class="clear"></div>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>