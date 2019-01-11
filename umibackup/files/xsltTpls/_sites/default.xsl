<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	xmlns:date="http://exslt.org/dates-and-times"
	xmlns:udt="http://umi-cms.ru/2007/UData/templates"
	xmlns:umi="http://www.umi-cms.ru/TR/umi"
	extension-element-prefixes="php"
	exclude-result-prefixes="xsl php date udt">

<xsl:output	method="html"
	encoding="utf-8"
	indent="yes"
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	cdata-section-elements="script noscript"
	undeclare-namespaces="yes"
	omit-xml-declaration="yes"/>
    
    <xsl:variable name="document-page-id" select="/result/@pageId" />

	<xsl:include href="include/content.xsl" />
    <xsl:include href="include/faq.xsl" />
    <xsl:include href="include/files.xsl" />
    <xsl:include href="include/menu.xsl" />
    <xsl:include href="include/news.xsl" />
    <xsl:include href="include/webforms.xsl" />

    <xsl:include href="include/utils/errors.xsl" />
    <xsl:include href="include/utils/navibar.xsl" />
    <xsl:include href="include/utils/notfound.xsl" />
    <xsl:include href="include/utils/paging.xsl" />
    <xsl:include href="include/utils/search.xsl" />
    <xsl:include href="include/utils/sitemap.xsl" />
	<xsl:include href="include/utils/thumbnails.xsl" />
    
	<xsl:template match="/">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
			<head>
            	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title><xsl:value-of select="//result/@title" /></title>
				<meta name="DESCRIPTION" content="{//result/meta/description}" />
				<meta name="KEYWORDS" content="{//result/meta/keywords}" />
                <meta name="google-site-verification" content="yZDAKGNPvkPEOga0zmkn23N4aA_mqj5-p0sKgBLuDBA" />
                <meta name='yandex-verification' content='4c453d5cbb029223' />
                <link rel="shortcut icon" href="/images/ses/favicon.ico" />
				<link rel="stylesheet" type="text/css" href="/css/ses/style.css" />
                <link rel="stylesheet" type="text/css" href="/css/ses/jquery.fancybox.css" media="screen" />
                <xsl:comment>[if IE 6]&gt;&lt;link rel=stylesheet href="/css/ses/ie6.css"&gt;&lt;![endif]</xsl:comment>
                <xsl:comment>[if IE 7]&gt;&lt;link rel=stylesheet href="/css/ses/ie6.css"&gt;&lt;![endif]</xsl:comment>

				<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
				<script type="text/javascript" src="/js/ses/jquery.collapse.js"></script>
				<script type="text/javascript" src="/js/ses/jquery.cookie.js"></script>
				<script type="text/javascript" src="/js/ses/jquery.easing.1.3.js"></script>
				<script type="text/javascript" src="/js/ses/jquery.fancybox-1.2.1.pack.js"></script>
				<script type="text/javascript" src="/js/ses/jquery.self.js"></script>

			</head>
			<body>
		 		<div class="line">
					<div class="centermap">
                    	<xsl:apply-templates select="document('udata://search/insert_form')/udata"/>
            			<div class="icons">
            				<a href="/"><img src="/images/ses/ic1.gif" alt="" /></a>
                            <a href="/contacts/"><img src="/images/ses/ic2.gif" alt="" /></a>
                            <a href="/content/sitemap/"><img src="/images/ses/ic3.gif" alt="" /></a>
            			</div>
            			<div class="clear"></div>
					</div>
				</div>
				<div class="centermap">
					<div class="head">
						<div class="logo">
							<a href="/"><img src="/images/ses/logo.jpg" alt="" /> Сургут<span>энергосбыт</span></a>					  </div>
				    <xsl:apply-templates select="document('udata://content/menu/')/udata"/>
						<div class="clear"></div>
					</div>
					<div class="foto">
						<img src="/images/ses/foto.jpg" alt="" />
					</div>
					<xsl:apply-templates select="result" />
				</div>
    			<div class="foot">
    				<div class="centermap">
        				<p class="copy">
                        	© 2011  ООО «Сургутэнергосбыт»
                            <span><a target="_blank" href="http://www.webartika.ru" title="Разработка сайтов в Сургуте">Разработка сайта</a> &#8212; Студия «Webartika»</span></p>
            			<div class="adress">
            				<!--<p><span>Адрес:</span> 628400, Россия, Тюменская область, ХМАО-Югра, г. Сургут,<div style="padding-left:42px;">Андреевский заезд, д.2</div></p>-->
                            <p><span>Адрес:</span> 628400, Российская Федерация, Тюменская область,<div style="padding-left:42px;">ХМАО - Югра, г. Сургут, ул.Нефтяников, 13А</div></p>
                			<p><span>Телефон:</span> (3462) 41-49-81 <span>Факс:</span>(3462) 41-50-71</p>
                			<p><span>Электронная почта: </span> <a href="mailto:info@surgutenergosbyt.ru">info@surgutenergosbyt.ru</a></p>
            			</div>
        			</div>
    			</div>
				<div class="counter">
					<!-- begin of Top100 code -->
					<script id="top100Counter" type="text/javascript" src="http://counter.rambler.ru/top100.jcn?2433006"></script>
					<noscript>
						<a href="http://top100.rambler.ru/navi/2433006/">
							<img src="http://counter.rambler.ru/top100.cnt?2433006" alt="Rambler's Top100" border="0" />						</a>
					</noscript>
					<!-- end of Top100 code -->
			  </div>
		</body>
		</html>
	</xsl:template>

</xsl:stylesheet>