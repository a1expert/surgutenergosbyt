<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/seo">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:xlink="http://www.w3.org/TR/xlink">

	<xsl:template match="/result[@module = 'seo' and @method = 'island_edit']/data">
		<form method="post" action="do/" enctype="multipart/form-data">
			<input type="hidden" name="referer" value="{/result/@referer-uri}"/>
			<input type="hidden" name="domain" value="{$domain-floated}"/>

			<xsl:apply-templates mode="form-modify" />

			<xsl:apply-templates select=".//field[@name = 'island_type']/values/item" />
		</form>
	</xsl:template>

	<xsl:template match="field[@name = 'island_type']" mode="form-modify">
		<div class="field text">
			<label class="inline" for="{generate-id()}">
				<span class="label">
					<acronym>
						<xsl:apply-templates select="." mode="sys-tips" />
						<xsl:value-of select="@title" />
					</acronym>
				</span>

				<script src="/styles/common/js/island.control.js" />

				<xsl:apply-templates select="values/item" mode="island_type" />
			</label>
		</div>
	</xsl:template>

	<xsl:template match="values/item" mode="island_type">
		<p>
			<label>
				<input type="radio" class="checkbox island_type" name="{../../@input_name}" value="{@id}">
					<xsl:if test="@selected = 'selected'">
						<xsl:attribute name="checked" select="'checked'" />
					</xsl:if>
				</input>
				<acronym>
					<xsl:value-of select="." />
				</acronym>
			</label>
		</p>
	</xsl:template>

	<xsl:template match="field[@name = 'island_user_fields' or @name='island_url_link' or @name = 'island_tree_link']" mode="form-modify"/>

	<xsl:template match="field[@name = 'island_type']/values/item">
		<div class="panel properties-group island_type" id="g_{@id}">
			<xsl:if test="not(@selected = 'selected')">
				<xsl:attribute name="style"><xsl:text>display: none;</xsl:text></xsl:attribute>
			</xsl:if>

			<div class="header">
				<span>
					<xsl:value-of select="." />
				</span>
				<div class="l" /><div class="r" />
			</div>

			<div class="content">
				<xsl:variable name="island_type_name" select="document(concat('uobject://', @id))/udata//property[@name = 'island_island_type']/value" />
				<xsl:choose>
					<xsl:when test="$island_type_name = 'relation'">
						<div class="field symlink onlyOne" id="{generate-id()}" name="{/result//field[@name = 'island_tree_link']/@input_name}">
							<label for="symlinkInput{generate-id()}">
								<span class="label">
									<acronym>
										<xsl:value-of select="/result//field[@name = 'island_tree_link']/@title" />
									</acronym>
									<xsl:apply-templates select="/result//field[@name = 'island_tree_link']" mode="required_text" />
								</span>
								<span id="symlinkInput{generate-id()}" rel="1">
									<ul>
										<xsl:apply-templates select="/result//field[@name = 'island_tree_link']/values/item" mode="symlink" />
									</ul>
								</span>
							</label>
						</div>
						<xsl:apply-templates select="/result[@module = 'seo' and @method = 'island_edit']//group[@name='filter_relation']" mode="form-modify-group-filter">
							<xsl:with-param name="group-id"><xsl:value-of select="@id"/></xsl:with-param>
						</xsl:apply-templates>
					</xsl:when>
					<xsl:otherwise>
						<div class="field" id="{generate-id()}">
							<label for="{generate-id()}">
								<span class="label">
									<acronym>
										<xsl:value-of select="/result//field[@name = 'island_url_link']/@title" />
									</acronym>
									<xsl:apply-templates select="/result//field[@name = 'island_url_link']" mode="required_text" />
								</span>
								<span>
									<input type="text" class="string" id="{generate-id()}" value="{/result//field[@name = 'island_url_link']}" name="{/result//field[@name = 'island_url_link']/@input_name}" />
								</span>
							</label>
						</div>
						<xsl:apply-templates select="/result[@module = 'seo' and @method = 'island_edit']//group[@name='filter_system']" mode="form-modify-group-filter">
							<xsl:with-param name="group-id"><xsl:value-of select="@id"/></xsl:with-param>
						</xsl:apply-templates>
					</xsl:otherwise>
				</xsl:choose>

				<xsl:call-template name="std-form-buttons" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@module = 'seo' and @method = 'island_edit']/data//group[@name != 'common']" mode="form-modify" />

	<xsl:template match="/result[@module = 'seo' and @method = 'island_edit']/data//group[@name = 'filter_relation' or @name = 'filter_system']" mode="form-modify-group-filter">
		<xsl:param name="group-id">1</xsl:param>
		<div id="group-fields">
			<div id="groupsContainer{$group-id}" class="groupsContainer"></div>
			<script type="text/javascript">
				var type = new typeControl(<xsl:value-of select="/result//object/@id"/>, {container: "#groupsContainer<xsl:value-of select="$group-id"/>"});
				type.addGroup({id    : 1,
				title : '<xsl:value-of select="./@title"/>',
				name  : '<xsl:value-of select="./@name"/>',
				visible : <xsl:value-of select="boolean(./@visible)" />,
				locked  : false});
				<xsl:apply-templates select="./field"  mode="form-modify-seo">
					<xsl:with-param name="group-id"><xsl:value-of select="$group-id"/></xsl:with-param>
				</xsl:apply-templates>
			</script>
		</div>
	</xsl:template>

	<xsl:template match="group[@name = 'filter_relation' or @name = 'filter_system']/field" mode="form-modify-seo">
		<xsl:param name="group-id">1</xsl:param>
		type.addField(1, {id  : <xsl:value-of select="./@id"/>,
		title    : '<xsl:value-of select="./@title"/>',
		name     : '<xsl:value-of select="./@name"/>',
		tip      : '',
		typeId   : <xsl:value-of select="./@field-type-id"/>,
		typeName : '<xsl:value-of select="./@field-type-title"/>',
		guideId :' <xsl:value-of select="./@guide-id"/>',

		visible    : <xsl:value-of select="boolean(./@visible = 1)" />,
		required   : false,
		filterable : true,
		indexable  : false,
		locked  : false});
	</xsl:template>

</xsl:stylesheet>