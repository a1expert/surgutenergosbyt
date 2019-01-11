<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<!-- вывод содержимого категории -->
	<xsl:template match="result[@module = 'faq'][@method = 'category']">
    	<xsl:variable name="category" select="document(concat('udata://faq/category//', @pageId))/udata"/>
		<div class="content">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="text" style="padding-top:15px;">
				<xsl:value-of select="//property[@name = 'content']/value" disable-output-escaping="yes" />
				<xsl:apply-templates select="document(concat('udata://faq/category//', $document-page-id))/udata" />
				<!-- постраничный переход -->
				<xsl:apply-templates select="document(concat('udata://system/numpages/', $category/total, '/', $category/per_page))/udata"/>
			</div>
			<h1><xsl:text>Задать вопрос</xsl:text></h1>
			<xsl:apply-templates select="document(concat('udata://faq/addQuestionForm//', $document-page-id))/udata" />
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'faq'][@method = 'category']">
		<xsl:apply-templates select="items/item" mode="question" />
	</xsl:template>

	<!-- оформление вопрос-ответ -->
	<xsl:template match="item" mode="question">
		<div class="faq">
			<div class="question"><xsl:value-of select="question" /></div>
			<div class="ansver"><xsl:value-of select="answer" /></div>
		</div>
	</xsl:template>

	<!-- форма задать вопрос -->
	<xsl:template match="udata[@module = 'faq'][@method = 'addQuestionForm']">
    	<form method="post" action="{action}" onsubmit="siteForms.data.save(this); return siteForms.check(this);">
           	<div class="forma">
            	<p>
                	<input class="inp2" type="text" name="nick" value="Имя" />
                	<input class="inp2" type="text" name="email" value="E-mail" />
					<input class="inp3" type="text" name="title" value="Заголовок сообщения" />
                </p>
                <div class="area">
                	<input type="submit" class="knopa" value="" />
                    <textarea name="question">Текст вопроса</textarea>
                </div>
            </div>
		</form>
	</xsl:template>

	<!-- вопрос отправлен -->
	<xsl:template match="result[@module = 'faq'][@method = 'post_question']">
		<div class="content">
			<h1><xsl:value-of select="//result/@header" /></h1>
			<div class="text" style="padding-top:15px;">
				<xsl:text>Ваш вопрос успешно отправлен. В ближайшее время наши специалисты ответят на него и опубликуют. </xsl:text>
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

</xsl:stylesheet>