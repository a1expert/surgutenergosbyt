<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template name="catalog-thumbnail">
		<xsl:param name="element-id" />
		<xsl:param name="field-name" />
		<xsl:param name="width">auto</xsl:param>
		<xsl:param name="height">auto</xsl:param>
		
		<xsl:variable name="property" select="document(concat('upage://', $element-id, '.', $field-name))/udata/property" />
		
		<xsl:call-template name="thumbnail">
			<xsl:with-param name="width" select="$width" />
			<xsl:with-param name="height" select="$height" />
			<xsl:with-param name="src">
				<xsl:value-of select="$property/value" />
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

	<xsl:template name="thumbnail">
		<xsl:param name="src" />
		<xsl:param name="width">auto</xsl:param>
		<xsl:param name="height">auto</xsl:param>
		<xsl:apply-templates select="document(concat('udata://system/makeThumbnailFull/(.', $src, ')/', $width, '/', $height, '/void/0/1/1/'))/udata" />
	</xsl:template>
	
	<xsl:template match="udata[@module = 'system' and (@method = 'makeThumbnail' or @method = 'makeThumbnailFull')]">
		<img src="{src}" width="{width}" height="{height}" alt="{@alt-name}"></img>
	</xsl:template>

</xsl:stylesheet>