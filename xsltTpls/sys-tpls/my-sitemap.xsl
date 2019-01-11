<?xml version="1.0" encoding="UTF-8"?>
 
<xsl:stylesheet	version="1.0"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
		xmlns:php="http://php.net/xsl"
		xsl:extension-element-prefixes="php"
		exclude-result-prefixes="php"
		xmlns:xlink="http://www.w3.org/TR/xlink"
		xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
 
	<xsl:output encoding="utf-8" method="xml" indent="yes"/>
	<xsl:param name="domain" />
 
 
	<xsl:template match="/">
		<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
			xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
			http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
			<xsl:apply-templates select="//items"/>
		</urlset>
	</xsl:template>
 
 
	<xsl:template match="items">
		<xsl:apply-templates select="item"/>	
	</xsl:template>
 
 
	<xsl:template match="item">
		<xsl:variable name="update-time" select="document(@xlink:href)/udata/page/@update-time" />
		<url>
			<loc>
				<xsl:value-of select="concat('http://', $domain, @link)" />
			</loc>
			<lastmod>
				<xsl:value-of select="document(concat('udata://system/convertDate/', $update-time, '/c/'))/udata" />
			</lastmod>
            <changefreq>
            	<xsl:text>daily</xsl:text>
            </changefreq>
		</url>
	</xsl:template>
 
</xsl:stylesheet>