<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<!-- шаблон для контента страницы карта сайта -->
	<xsl:template match="result[@module = 'content' and @method = 'sitemap']">
		<div class="content">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="text" style="padding-top:15px;">
				<xsl:apply-templates select="document('udata://content/sitemap/')/udata/items" mode="sitemap"/>
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

	<!-- шаблон, обрамляющий списки -->
	<xsl:template match="items" mode="sitemap">
		<ul>
			<xsl:apply-templates select="item" mode="sitemap"/>
		</ul>
	</xsl:template>

	<!-- шаблон, оформляющий элемент списка -->
	<xsl:template match="item" mode="sitemap">
		<li style="list-style: disc;">
			<a href="{@link}">
				<xsl:value-of select="@name" />
			</a>
			<xsl:apply-templates select="items" mode="sitemap"/>
		</li>
	</xsl:template>

</xsl:stylesheet>