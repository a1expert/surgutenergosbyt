<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<!-- шаблон для всего меню и для вложенных подменю -->
	<xsl:template match="udata[@module = 'content'][@method = 'menu']">
	<div class="menu">
		<ul>
			<li class="one">
				<a href="/">О компании</a>
				<div class="nav">
					<div class="navbg"><div class="nav_top">
						<ul>
							<!--<li><a href="/about_us/workers/">Сотрудники</a></li>-->
							<li><a href="/about_us/details/">Реквизиты</a></li>
							<li><a href="/about_us/jobs/">Вакансии</a></li>
						</ul>  
					</div></div>
					<div class="nav_foot"></div>  
				</div>
			</li>
			<li><a href="/news/">Новости</a></li>
			<li><a href="/services/">Услуги</a></li>
			<li><a href="/tariff/">Тарифы</a></li>
            <!--<li><a href="/documents/">Документы</a></li>-->
			<li class="nd">
				<a href="/documents/">Документы</a>
				<div class="nav">
					<div class="nav2bg"><div class="nav2_top">
						<ul>
							<li><a href="/documents/reg_legal_fw/">Нормативно-правовая база</a></li>
							<li><a href="/documents/disclosure_info/">Раскрытие информации</a></li>
							<li><a href="/documents/uvedomleniya_ob_ogranichenii/">Уведомления об ограничении</a></li>
							<!--<li class="pad"><span>—</span> <a href="/documents/model_contracts/entities/">Юридические лица</a></li>
							<li class="pad"><span>—</span> <a href="/documents/model_contracts/individuals/">Физические лица</a></li>-->
						</ul>  
					</div></div>
					<div class="nav2_foot"></div>  
				</div>
			</li>
			<li><a href="/feedback/">Обратная связь</a></li>
			<li><a href="/contacts/">Контакты</a></li>
		</ul>
	</div>
	</xsl:template>

</xsl:stylesheet>