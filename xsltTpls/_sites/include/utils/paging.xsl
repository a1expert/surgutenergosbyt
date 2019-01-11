<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<!-- оформление постраничного перехода -->
	<xsl:template match="udata[@module = 'system'][@method = 'numpages'][items]">
		<div class="pad">
			<div class="str">
				<xsl:apply-templates select="items/item" mode="paging"/>
			</div>
            <div class="clear"></div>
        </div>
	</xsl:template>

	<xsl:template match="item" mode="paging">
       	<a href="{@link}">
           	<xsl:value-of select="." />
		</a>
	</xsl:template>

	<xsl:template match="item[@is-active = 1]" mode="paging">
       	<a class="prev" href="{@link}">
           	<xsl:value-of select="." />
		</a>
	</xsl:template>

</xsl:stylesheet>