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

	<!-- 2018 список файлов покупки электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f2014">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/2014')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
	</xsl:template>

	<!-- 2018 список файлов отпуска электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f2027">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/2027')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
	</xsl:template>

	<!-- 2017 список файлов покупки электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1901">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1901')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
	</xsl:template>

	<!-- 2017 список файлов отпуска электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1914">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1914')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
	</xsl:template>

	<!-- 2016 список файлов покупки электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1777">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1777')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
	</xsl:template>

	<!-- 2016 список файлов отпуска электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1790">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1790')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
	</xsl:template>

	<!-- 2015 список файлов покупки электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1645">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1645')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
	</xsl:template>

	<!-- 2015 список файлов отпуска электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1658">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1658')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
	</xsl:template>

	<!-- 2014 список файлов покупки электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1447">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1447')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
	</xsl:template>

	<!-- 2014 список файлов отпуска электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1460">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1460')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
	</xsl:template>

	<!-- список файлов покупки электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1207">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1207')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <!--<xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>-->
	</xsl:template>

	<!-- список файлов отпуска электроэнергии -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1194">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1194')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <!--<xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>-->
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
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1226')/udata"/>
		<ul class="files">
			<xsl:apply-templates select="$filelist/items" mode="f1"/>
		</ul>
        <!--<xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>-->
	</xsl:template>

	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f2040">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/2040')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1927">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1927')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1803">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1803')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1671">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1671')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1486">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1486')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1175">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1175')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
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

	<!-- Охрана труда -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f1587">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1587')/udata"/>
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

	<!-- Предельный уровень нерегулируемой цены. Прогноз -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="tariff_prognoz_13">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1140')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>
	<!-- Предельный уровень нерегулируемой цены. Факт -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="tariff_fact_13">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1153')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- Предельный уровень нерегулируемой цены. Прогноз -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="tariff_prognoz_14">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1411')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>
	<!-- Предельный уровень нерегулируемой цены. Факт -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="tariff_fact_14">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1424')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- Предельный уровень нерегулируемой цены. Прогноз -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="tariff_prognoz_15">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1613')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>
	<!-- Предельный уровень нерегулируемой цены. Факт -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="tariff_fact_15">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1626')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- Предельный уровень нерегулируемой цены. Прогноз -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="tariff_prognoz_16">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1748')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>
	<!-- Предельный уровень нерегулируемой цены. Факт -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="tariff_fact_16">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1761')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- Предельный уровень нерегулируемой цены. Прогноз -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="tariff_prognoz_17">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1872')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>
	<!-- Предельный уровень нерегулируемой цены. Факт -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="tariff_fact_17">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1885')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

	<!-- Предельный уровень нерегулируемой цены. Прогноз -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="tariff_prognoz_18">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1985')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>
	<!-- Предельный уровень нерегулируемой цены. Факт -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="tariff_fact_18">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/1998')/udata"/>
		<ul class="files2">
			<xsl:apply-templates select="$filelist/items" mode="f2"/>
		</ul>
        <xsl:apply-templates select="document(concat('udata://system/numpages/', $filelist/total, '/', $filelist/per_page))/udata"/>
	</xsl:template>

<!-- ########################################################################################################################################### -->

	<!-- Документы -->
	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']" mode="f9">
    	<xsl:variable name="filelist" select="document('udata://filemanager/list_files/5//5')/udata"/>
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
		<!--<li>
           	<a href="{$filelink}">
       			<xsl:value-of select="document(concat('upage://', @id, '.h1'))//value" />
			</a>
       		<xsl:value-of select="document(concat('upage://', @id, '.content'))//value" disable-output-escaping="yes" />
		</li>-->
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