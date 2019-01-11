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

	<!-- вывод полной ленты новостей --> 
	<xsl:template match="result[@module = 'news'][@method = 'rubric']">
		<xsl:variable name="lastlist" select="document(concat('udata://news/lastlist/', @pageId))/udata"/>
		<div class="content">
			<h1><xsl:text>Все новости</xsl:text></h1>
			<div class="text" style="padding-top:15px;">
				<xsl:apply-templates select="$lastlist" mode="news_full" />
				<!-- постраничный переход -->
				<xsl:apply-templates select="document(concat('udata://system/numpages/', $lastlist/total, '/', $lastlist/per_page, '//p/20'))/udata"/>
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

	<xsl:template match="udata[@module = 'news'][@method = 'lastlist']" mode="news_full">
		<xsl:apply-templates select="items/item" mode="news_full"/>
	</xsl:template>

	<xsl:template match="item" mode="news_full">
		<xsl:variable name="surce" select="document(concat('upage://', @id, '.source_url'))//value" />
    	<div class="news">
			<p class="date2"><xsl:value-of select="document(concat('udata://system/convertDate/', @publish_time, '/(d.m.Y)'))/udata" /></p>
			<p class="link"><a href="{$surce}" target="_blank"><xsl:value-of select="document(concat('upage://', @id, '.h1'))//value" /></a></p>
			<!--<p class="link"><a href="{@link}"><xsl:value-of select="document(concat('upage://', @id, '.h1'))//value" /></a></p>-->
			<!--<div class="date2"><xsl:value-of select="document(concat('udata://system/convertDate/', @publish_time, '/(d.m.Y)'))/udata" /></div>
            <div class="zag"><xsl:value-of select="document(concat('upage://', @id, '.h1'))//value" /></div>
			<xsl:value-of select="document(concat('upage://', @id, '.anons'))//value" disable-output-escaping="yes" />-->
		</div>
	</xsl:template>

	<!-- вывод содержания новости -->
	<xsl:template match="result[@module = 'news'][@method = 'item']">
		<div class="content">
			<h1 class="news_one"><xsl:value-of select="//result/@header" /></h1>
			<div class="text" style="padding-top:15px;">
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
    
	<!-- вывод ограниченной ленты новостей --> 
	<xsl:template match="item" mode="news1">
		<xsl:variable name="surce" select="document(concat('upage://', @id, '.source_url'))//value" />
		<xsl:variable name="title_cut" select="document(concat('upage://', @id, '.h1'))//value" />
		<div class="tumb">
			<p class="data"><xsl:value-of select="document(concat('udata://system/convertDate/', @publish_time, '/(d.m.Y)'))/udata" /></p>
			<p class="link"><a href="{$surce}" target="_blank"><xsl:value-of select="substring($title_cut, 1, 100)" />&nbsp;<xsl:text>...</xsl:text></a></p>
		</div>
	</xsl:template>
    
	<xsl:template match="item" mode="news2">
		<xsl:variable name="surce" select="document(concat('upage://', @id, '.source_url'))//value" />
		<xsl:variable name="title_cut" select="document(concat('upage://', @id, '.h1'))//value" />
		<div class="tumb2">
			<p class="data"><xsl:value-of select="document(concat('udata://system/convertDate/', @publish_time, '/(d.m.Y)'))/udata" /></p>
			<p class="link"><a href="{$surce}" target="_blank"><xsl:value-of select="substring($title_cut, 1, 100)" />&nbsp;<xsl:text>...</xsl:text></a></p>
		</div>
	</xsl:template>
    
	<xsl:template match="item" mode="news3">
		<xsl:variable name="surce" select="document(concat('upage://', @id, '.source_url'))//value" />
		<xsl:variable name="title_cut" select="document(concat('upage://', @id, '.h1'))//value" />
		<div class="tumb3">
			<p class="data"><xsl:value-of select="document(concat('udata://system/convertDate/', @publish_time, '/(d.m.Y)'))/udata" /></p>
			<p class="link"><a href="{$surce}" target="_blank"><xsl:value-of select="substring($title_cut, 1, 100)" />&nbsp;<xsl:text>...</xsl:text></a></p>
		</div>
	</xsl:template>

</xsl:stylesheet>