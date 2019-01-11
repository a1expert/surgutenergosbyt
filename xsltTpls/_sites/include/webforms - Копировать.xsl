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
           	<div class="forma">
            	<p>
                	<xsl:apply-templates select=".//field[@type = 'string']" />
                </p>
                <xsl:apply-templates select=".//field[@type = 'text']" />
            </div>
		</form>
	</xsl:template>

	<!-- оформление полей формы -->
	<xsl:template match="field[@type = 'string' and @name = 'email_addr']">
    	<input class="inp3" type="text" name="{@input_name}" value="E-mail" />
	</xsl:template>
  
	<xsl:template match="field[@type = 'string' and @name = 'first_name']">
    	<input class="inp2" type="text" name="{@input_name}" value="Имя" />
	</xsl:template>
    
	<xsl:template match="field[@type = 'string' and @name = 'last_name']">
    	<input class="inp2" type="text" name="{@input_name}" value="Фамилия" />
	</xsl:template>
  
	<xsl:template match="field[@type = 'text']">
		<div class="area">
			<input type="submit" class="knopa" value="" />
			<textarea name="{@input_name}">Ваше сообщение</textarea>
        </div>
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