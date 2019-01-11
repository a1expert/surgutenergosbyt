<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<!-- шаблон для контента страницы не найдено -->
	<xsl:template match="result[@module = 'content' and @method = 'notfound']">
		<div class="content">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="text">
				<p>Запрошенная Вами страница не найдена. Возможно, мы удалили или переместили ее. Возможно, вы пришли по устаревшей ссылке или неверно ввели адрес. Воспользуйтесь поиском или картой сайта.</p>
			</div>
			<h1><xsl:text>Карта сайта</xsl:text></h1>
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

	<!--<xsl:template match="result[@module = 'content' and @method = 'notfound']">
		<div class="content">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<p>Запрошенная Вами страница не найдена. Возможно, мы удалили или переместили ее. Возможно, вы пришли по устаревшей ссылке или неверно ввели адрес. Воспользуйтесь поиском или картой сайта.</p>
			<h1><xsl:text>Карта сайта</xsl:text></h1>
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
	</xsl:template>-->

</xsl:stylesheet>