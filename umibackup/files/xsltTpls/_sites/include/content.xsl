<?xml version="1.0" encoding="UTF-8"?>

<!DOCTYPE local [
	<!ENTITY nbsp   "&#160;">
	<!ENTITY copy   "&#169;">
	<!ENTITY reg    "&#174;">
	<!ENTITY trade  "&#8482;">
	<!ENTITY mdash  "&#8212;">
	<!ENTITY laquo  "&#171;">
	<!ENTITY raquo  "&#187;"> 
	<!ENTITY pound  "&#163;">
	<!ENTITY yen    "&#165;">
	<!ENTITY euro   "&#8364;">
]>

<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<!-- внутренние страницы -->
	<xsl:template match="result[@module = 'content' and @method = 'content']" >
		<div class="content">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="text">
				<xsl:value-of select=".//property[@name = 'content']/value" disable-output-escaping="yes" />
			</div>
			<!--<h2><xsl:apply-templates select="document('upage://(news).h1')//value" /></h2>
			<div class="tumbs">
				<xsl:apply-templates select="document('udata://news/lastlist/(news)/notemplate/3/1')/udata/items/item[position() = 1]" mode="news1" />
				<xsl:apply-templates select="document('udata://news/lastlist/(news)/notemplate/3/1')/udata/items/item[position() = 2]" mode="news2" />
				<xsl:apply-templates select="document('udata://news/lastlist/(news)/notemplate/3/1')/udata/items/item[position() = 3]" mode="news3" />
				<div class="clear"></div>
			</div>-->
		</div>
	</xsl:template>

	<!-- Нормативно-правовая база (новый тип данных) -->
	<xsl:template match="result[@module = 'content' and @method = 'content' and page/@id= '11']" >
		<div class="content">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="t_files">
				<xsl:value-of select=".//property[@name = 'content']/value" disable-output-escaping="yes" />
				<h2><xsl:apply-templates select="document('upage://674.h1')//value" /></h2>
					<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f10"/>
				<h2><xsl:apply-templates select="document('upage://675.h1')//value" /></h2>
					<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f11"/>
			</div>
			<!--<h2><xsl:apply-templates select="document('upage://(news).h1')//value" /></h2>
			<div class="tumbs">
				<xsl:apply-templates select="document('udata://news/lastlist/(news)/notemplate/3/1')/udata/items/item[position() = 1]" mode="news1" />
				<xsl:apply-templates select="document('udata://news/lastlist/(news)/notemplate/3/1')/udata/items/item[position() = 2]" mode="news2" />
				<xsl:apply-templates select="document('udata://news/lastlist/(news)/notemplate/3/1')/udata/items/item[position() = 3]" mode="news3" />
				<div class="clear"></div>
			</div>-->
		</div>
	</xsl:template>

	<!-- страницы документов (новый тип данных) -->
	<xsl:template match="result[@module = 'content' and @method = 'content' and page/@id= '12']" >
		<div class="content_f">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="t_files">
				<xsl:value-of select=".//property[@name = 'content']/value" disable-output-escaping="yes" />
                <h2><xsl:apply-templates select="document('upage://522.h1')//value" /></h2>
					<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1"/>
                <h2><xsl:apply-templates select="document('upage://523.h1')//value" /></h2>
					<h3><xsl:apply-templates select="document('upage://882.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f2-1"/>
					<h3><xsl:apply-templates select="document('upage://895.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f3-1"/>
					<h3><xsl:apply-templates select="document('upage://524.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f2"/>
					<h3><xsl:apply-templates select="document('upage://525.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f3"/>
				<h2><xsl:apply-templates select="document('upage://532.h1')//value" /></h2>
					<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f4"/>
				<h2><xsl:apply-templates select="document('upage://534.h1')//value" /></h2>
					<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f5"/>
					<h3><xsl:apply-templates select="document('upage://1036.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1036"/>
				<h2><xsl:apply-templates select="document('upage://537.h1')//value" /></h2>
					<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f6"/>
			</div>
		</div>
	</xsl:template>

	<!-- Документы (новый тип данных) -->
	<xsl:template match="result[@module = 'content' and @method = 'content' and page/@id= '5']" >
		<div class="content_f">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="t_files">
				<xsl:value-of select=".//property[@name = 'content']/value" disable-output-escaping="yes" />
				<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f9"/>
			</div>
			<!--<h2><xsl:apply-templates select="document('upage://(news).h1')//value" /></h2>
			<div class="tumbs">
				<xsl:apply-templates select="document('udata://news/lastlist/(news)/notemplate/3/1')/udata/items/item[position() = 1]" mode="news1" />
				<xsl:apply-templates select="document('udata://news/lastlist/(news)/notemplate/3/1')/udata/items/item[position() = 2]" mode="news2" />
				<xsl:apply-templates select="document('udata://news/lastlist/(news)/notemplate/3/1')/udata/items/item[position() = 3]" mode="news3" />
				<div class="clear"></div>
			</div>-->
		</div>
	</xsl:template>

	<!-- страницы документов (новый тип данных) -->
	<xsl:template match="result[@module = 'content' and @method = 'content' and page/@id= '4']" >
		<div class="content_f">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="t_files">
				<xsl:value-of select=".//property[@name = 'content']/value" disable-output-escaping="yes" />
				<h2><xsl:apply-templates select="document('upage://813.h1')//value" /></h2>
                		<h3><xsl:apply-templates select="document('upage://815.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f12"/>
						<h3><xsl:apply-templates select="document('upage://829.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f13"/>
				<h2><xsl:apply-templates select="document('upage://812.h1')//value" /></h2>
						<h3><xsl:apply-templates select="document('upage://539.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f7"/>
						<h3><xsl:apply-templates select="document('upage://540.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f8"/>
			</div>
		</div>
	</xsl:template>
    
	<!-- страница контактов -->
	<xsl:template match="result[@module = 'content' and @method = 'content' and page/@id= '7']" >
		<div class="content">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="text">
				<xsl:value-of select=".//property[@name = 'content']/value" disable-output-escaping="yes" />
			</div>
			<h1 style="padding-bottom:10px;"><xsl:text>Отправить сообщение</xsl:text></h1>
			<xsl:apply-templates select="document('udata://webforms/add/817/27546')/udata" mode="webform"/>
		</div>
	</xsl:template>

	<!-- главная страница -->
	<xsl:template match="result[@module = 'content' and @method = 'content' and page/@is-default = '1']" >
		<div class="content">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="text">
				<xsl:value-of select=".//property[@name = 'content']/value" disable-output-escaping="yes" />
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