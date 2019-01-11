<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<!-- шаблон вывода формы -->
	<xsl:template match="udata[@module = 'search'][@method = 'insert_form']">
		<div class="search">
        	<form method="get" action="/search/search_do/">
            	<input class="inp" type="text" name="search_string" value="{last_search_string}"/>
				<input class="searchf" type="submit" value="" />
			</form>
		</div>
	</xsl:template>

	<!-- шаблон страницы с результатами поиска -->
	<xsl:template match="result[@module = 'search'][@method = 'search_do']">
		<xsl:variable name="search-results" select="document('udata://search/search_do/')/udata" />
		<div class="content">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="text">
				<xsl:apply-templates select="$search-results"/>
                <xsl:apply-templates select="document(concat('udata://system/numpages/', $search-results/total, '/', $search-results/per_page, '/notemplate/p/3'))" mode="paging.numbers"/>
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

	<!-- когда ничего не найдено -->
	<xsl:template match="udata[@module = 'search'][@method = 'search_do'][not(items/item)]">
		<p>
			<xsl:text>По запросу </xsl:text>
			<span>&#171;<xsl:value-of select="$search_string" />&#187;</span>
			<xsl:text> ничего не найдено.</xsl:text>
		</p>
	</xsl:template>

	<!-- обработка результатов поиска -->
	<xsl:template match="udata[@module = 'search'][@method = 'search_do'][items/item]">
		<p>
			<xsl:text>Найдено страниц: </xsl:text>
			<xsl:value-of select="total" />
		</p>
		<ul class="search_rez">
			<xsl:apply-templates select="items/item" mode="search.results"/>
		</ul>
	</xsl:template>

	<!-- отдельный результат из списка -->
	<xsl:template match="item" mode="search.results" >
		<li>
			<a href="{@link}">
				<xsl:value-of select="@name"/>
			</a>
			<xsl:value-of select="." disable-output-escaping="yes"/>
		</li>
	</xsl:template>

</xsl:stylesheet>