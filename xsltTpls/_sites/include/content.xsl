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

	<!-- Обратная связь -->
	<xsl:template match="result[@module = 'content' and @method = 'content' and page/@id= '2119']" >
		<div class="content">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="text">
				<xsl:apply-templates select="document('udata://webforms/add/817/27546')/udata" mode="webform"/>
			</div>
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
                	<!-- 2018 -->
					<h3><xsl:apply-templates select="document('upage://2014.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f2014"/>
					<h3><xsl:apply-templates select="document('upage://2027.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f2027"/>
                	<!-- 2017 -->
					<h3><xsl:apply-templates select="document('upage://1901.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1901"/>
					<h3><xsl:apply-templates select="document('upage://1914.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1914"/>
                	<!-- 2016 -->
					<h3><xsl:apply-templates select="document('upage://1777.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1777"/>
					<h3><xsl:apply-templates select="document('upage://1790.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1790"/>
                	<!-- 2015 -->
					<h3><xsl:apply-templates select="document('upage://1645.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1645"/>
					<h3><xsl:apply-templates select="document('upage://1658.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1658"/>
                	<!-- 2014 -->
					<h3><xsl:apply-templates select="document('upage://1447.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1447"/>
					<h3><xsl:apply-templates select="document('upage://1460.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1460"/>
                	<!-- 2013 -->
					<h3><xsl:apply-templates select="document('upage://1207.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1207"/>
					<h3><xsl:apply-templates select="document('upage://1194.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1194"/>
					<!-- 2012 -->
					<h3><xsl:apply-templates select="document('upage://882.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f2-1"/>
					<h3><xsl:apply-templates select="document('upage://895.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f3-1"/>
					<!-- 2011 -->
                    <h3><xsl:apply-templates select="document('upage://524.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f2"/>
					<h3><xsl:apply-templates select="document('upage://525.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f3"/>
				<h2><xsl:apply-templates select="document('upage://532.h1')//value" /></h2>
					<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f4"/>
				<h2><xsl:apply-templates select="document('upage://534.h1')//value" /></h2>
					<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f5"/>
					<h3><xsl:apply-templates select="document('upage://2040.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f2040"/>

					<h3><xsl:apply-templates select="document('upage://1927.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1927"/>
					<h3><xsl:apply-templates select="document('upage://1803.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1803"/>
					<h3><xsl:apply-templates select="document('upage://1671.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1671"/>
					<h3><xsl:apply-templates select="document('upage://1486.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1486"/>
					<h3><xsl:apply-templates select="document('upage://1175.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1175"/>
					<h3><xsl:apply-templates select="document('upage://1036.h1')//value" /></h3>
						<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1036"/>
				<h2><xsl:apply-templates select="document('upage://537.h1')//value" /></h2>
					<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f6"/>
				<h2><xsl:apply-templates select="document('upage://1587.h1')//value" /></h2>
					<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="f1587"/>
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

				<h2><xsl:apply-templates select="document('upage://1984.h1')//value" /></h2>
                		<h3><xsl:apply-templates select="document('upage://1985.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="tariff_prognoz_18"/>
						<h3><xsl:apply-templates select="document('upage://1998.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="tariff_fact_18"/>

				<h2><xsl:apply-templates select="document('upage://1871.h1')//value" /></h2>
                		<h3><xsl:apply-templates select="document('upage://1872.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="tariff_prognoz_17"/>
						<h3><xsl:apply-templates select="document('upage://1885.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="tariff_fact_17"/>
				<h2><xsl:apply-templates select="document('upage://1747.h1')//value" /></h2>
                		<h3><xsl:apply-templates select="document('upage://1748.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="tariff_prognoz_16"/>
						<h3><xsl:apply-templates select="document('upage://1761.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="tariff_fact_16"/>
				<h2><xsl:apply-templates select="document('upage://1612.h1')//value" /></h2>
                		<h3><xsl:apply-templates select="document('upage://1613.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="tariff_prognoz_15"/>
						<h3><xsl:apply-templates select="document('upage://1626.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="tariff_fact_15"/>
				<h2><xsl:apply-templates select="document('upage://1410.h1')//value" /></h2>
                		<h3><xsl:apply-templates select="document('upage://1411.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="tariff_prognoz_14"/>
						<h3><xsl:apply-templates select="document('upage://1424.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="tariff_fact_14"/>
				<h2><xsl:apply-templates select="document('upage://1139.h1')//value" /></h2>
                		<h3><xsl:apply-templates select="document('upage://1140.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="tariff_prognoz_13"/>
						<h3><xsl:apply-templates select="document('upage://1153.h1')//value" /></h3>
							<xsl:apply-templates select="document('udata://filemanager/list_files')/udata" mode="tariff_fact_13"/>
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
				<p><xsl:value-of select="//property[@name='yandex']/value" disable-output-escaping="yes" /></p>
			</div>
			<!--<h1 style="padding-bottom:10px;"><xsl:text>Отправить сообщение</xsl:text></h1>
			<xsl:apply-templates select="document('udata://webforms/add/817/27546')/udata" mode="webform"/>-->
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