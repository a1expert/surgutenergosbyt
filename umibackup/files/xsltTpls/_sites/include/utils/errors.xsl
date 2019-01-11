<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<!-- errors -->
	<xsl:template match="udata[@module = 'system'][@method = 'listErrorMessages']">
		<ul style="padding: 10px;">
			<xsl:apply-templates select="items/item" mode="errors"/>
		</ul>
	</xsl:template>
	
	<xsl:template match="item" mode="errors">
		<li style="color: #ff6a08; list-style:none;">
			<xsl:value-of select="." />
		</li>
	</xsl:template>

</xsl:stylesheet>