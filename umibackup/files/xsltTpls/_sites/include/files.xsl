<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<!-- список файлов бухгалтерской отчетности -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/522')/udata"/>
		<ul class="files">
			<xsl:apply-templates select="$filelist/items" mode="f1-fin"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- список файлов покупки электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f2-1">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/882')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- список файлов отпуска электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f3-1">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/895')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- список файлов покупки электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f2">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/524')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- список файлов отпуска электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f3">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/525')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- oсновные условия договора электроснабжения -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f4">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/532')/udata"/>
		<ul class="files">
			<xsl:apply-templates select="$filelist/items" mode="f1"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- Информация о составляющих  цены на электрическую энергию -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f5">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/534//3')/udata"/>
		<ul class="files">
			<xsl:apply-templates select="$filelist/items" mode="f1"/>
		</ul>
        <!--<xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>-->
	</xsl:template>

	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1036">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1036')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- Структура и объем затрат на покупку и реализацию электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f6">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/537')/udata"/>
		<ul class="files">
			<xsl:apply-templates select="$filelist/items" mode="f1"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

<!-- ########################################################################################################################################### -->

	<!-- Предельный уровень нерегулируемой цены. Прогноз -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f7">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/539')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- Предельный уровень нерегулируемой цены. Факт -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f8">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/540')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- Предельный уровень нерегулируемой цены. Прогноз -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f12">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/815')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- Предельный уровень нерегулируемой цены. Факт -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f13">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/829')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

<!-- ########################################################################################################################################### -->

	<!-- Документы -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f9">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/5//1')/udata"/>
		<ul class="files">
			<xsl:apply-templates select="$filelist/items" mode="f1"/>
		</ul>
        <!--<xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>-->
	</xsl:template>

<!-- ########################################################################################################################################### -->

	<!-- Федеральные законы -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f10">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/674')/udata"/>
		<ul class="files">
			<xsl:apply-templates select="$filelist/items" mode="f1"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- Постановления правительства РФ -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f11">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/675')/udata"/>
		<ul class="files">
			<xsl:apply-templates select="$filelist/items" mode="f1"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

<!-- ########################################################################################################################################### -->

	<!-- оформление списка файлов бухгалтерской отчетности | условия договора электроснабжения -->
	<xsl:template match="items" mode="f1-fin">
    	<xsl:variable name="filelink" select="document(concat('upage://', @id, '.fs_file'))//value"/>
		<xsl:choose>
			<xsl:when test="@name = 'separator'">
				<li class="separator">&#160;</li>
			</xsl:when>
			<xsl:otherwise>
				<li>
                	<a href="{$filelink}"><xsl:value-of select="document(concat('upage://', @id, '.h1'))//value" /></a>
					<xsl:value-of select="document(concat('upage://', @id, '.content'))//value" disable-output-escaping="yes" />
				</li>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- оформление списка файлов -->
	<xsl:template match="items" mode="f1">
    	<xsl:variable name="filelink" select="document(concat('upage://', @id, '.fs_file'))//value"/>
		<li>
           	<a href="{$filelink}">
       			<xsl:value-of select="document(concat('upage://', @id, '.h1'))//value" />
			</a>
       		<xsl:value-of select="document(concat('upage://', @id, '.content'))//value" disable-output-escaping="yes" />
		</li>
	</xsl:template>

	<!-- оформление списка файлов покупки и отпуска электроэнергии -->
	<xsl:template match="items" mode="f2">
    	<xsl:variable name="filelink" select="document(concat('upage://', @id, '.fs_file'))//value"/>
		<li>
           	<a href="{$filelink}">
       			<xsl:value-of select="document(concat('upage://', @id, '.h1'))//value" />
			</a>
       		<xsl:value-of select="document(concat('upage://', @id, '.content'))//value" disable-output-escaping="yes" />
		</li>
	</xsl:template>

</xsl:stylesheet>