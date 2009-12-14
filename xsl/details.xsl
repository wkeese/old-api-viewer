<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml" encoding="UTF-8" omit-xml-declaration="no" indent="yes" />

	<!-- Final XSLT to create a new minimized version of the api.xml file. -->
	<xsl:template match="/">
		<xsl:element name="javascript">
			<xsl:apply-templates select="javascript/object" />
		</xsl:element>
	</xsl:template>

	<xsl:template match="object">
		<xsl:element name="object">
			<xsl:attribute name="location">
				<xsl:value-of select="@location" />
			</xsl:attribute>
			<xsl:if test="@type">
				<xsl:attribute name="type">
					<xsl:value-of select="@type" />
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="@classlike">
				<xsl:attribute name="classlike">
					<xsl:value-of select="@classlike" />
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="@superclass">
				<xsl:attribute name="superclass">
					<xsl:value-of select="@superclass" />
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="@private">
				<xsl:attribute name="private">
					<xsl:value-of select="@private" />
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="summary">
				<xsl:element name="summary">
					<xsl:value-of select="./summary/text()" />
				</xsl:element>
			</xsl:if>
			<xsl:if test="description">
				<xsl:element name="description">
					<xsl:value-of select="./description/text()" />
				</xsl:element>
			</xsl:if>
			<xsl:if test="mixins">
				<xsl:element name="mixins">
					<xsl:apply-templates select="./mixins/mixin" />
				</xsl:element>
			</xsl:if>
			<xsl:if test="examples">
				<xsl:element name="examples">
					<xsl:apply-templates select="./examples/example" />
				</xsl:element>
			</xsl:if>
			<xsl:if test="properties">
				<xsl:element name="properties">
					<xsl:apply-templates select="./properties/property" />
				</xsl:element>
			</xsl:if>
			<xsl:if test="methods">
				<xsl:element name="methods">
					<xsl:apply-templates select="./methods/method" />
				</xsl:element>
			</xsl:if>
		</xsl:element>
	</xsl:template>

	<xsl:template match="mixins/mixin">
		<xsl:element name="mixin">
			<xsl:attribute name="scope">
				<xsl:value-of select="@scope" />
			</xsl:attribute>
			<xsl:attribute name="location">
				<xsl:value-of select="@location" />
			</xsl:attribute>
		</xsl:element>
	</xsl:template>

	<xsl:template match="properties/property">
		<xsl:element name="property">
			<xsl:attribute name="name">
				<xsl:value-of select="@name" />
			</xsl:attribute>
			<xsl:if test="@scope">
				<xsl:attribute name="scope">
					<xsl:value-of select="@scope" />
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="@type">
				<xsl:attribute name="type">
					<xsl:value-of select="@type" />
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="summary">
				<xsl:element name="summary">
					<xsl:value-of select="./summary/text()" />
				</xsl:element>
			</xsl:if>
			<xsl:if test="description">
				<xsl:element name="description">
					<xsl:value-of select="./description/text()" />
				</xsl:element>
			</xsl:if>
		</xsl:element>
	</xsl:template>

	<xsl:template match="methods/method">
		<xsl:element name="method">
			<xsl:if test="@constructor">
				<xsl:attribute name="constructor">
					<xsl:value-of select="@constructor" />
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="@super">
				<xsl:attribute name="super">
					<xsl:value-of select="@super" />
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="@name">
				<xsl:attribute name="name">
					<xsl:value-of select="@name" />
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="@scope">
				<xsl:attribute name="scope">
					<xsl:value-of select="@scope" />
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="summary">
				<xsl:element name="summary">
					<xsl:value-of select="./summary/text()" />
				</xsl:element>
			</xsl:if>
			<xsl:if test="description">
				<xsl:element name="description">
					<xsl:value-of select="./description/text()" />
				</xsl:element>
			</xsl:if>
			<xsl:if test="return-description">
				<xsl:element name="return-description">
					<xsl:value-of select="./return-description/text()" />
				</xsl:element>
			</xsl:if>
			<xsl:if test="examples">
				<xsl:element name="examples">
					<xsl:apply-templates select="./examples/example" />
				</xsl:element>
			</xsl:if>
			<xsl:if test="parameters">
				<xsl:element name="parameters">
					<xsl:apply-templates select="./parameters/parameter" />
				</xsl:element>
			</xsl:if>
			<xsl:if test="return-types">
				<xsl:element name="return-types">
					<xsl:apply-templates select="./return-types/return-type" />
				</xsl:element>
			</xsl:if>
		</xsl:element>
	</xsl:template>

	<xsl:template match="examples/example">
		<xsl:element name="example">
			<xsl:value-of select="text()" />
		</xsl:element>
	</xsl:template>

	<xsl:template match="parameters/parameter">
		<xsl:element name="parameter">
			<xsl:attribute name="name">
				<xsl:value-of select="@name" />
			</xsl:attribute>
			<xsl:attribute name="type">
				<xsl:value-of select="@type" />
			</xsl:attribute>
			<xsl:attribute name="usage">
				<xsl:value-of select="@usage" />
			</xsl:attribute>
			<xsl:if test="summary">
				<xsl:element name="summary">
					<xsl:value-of select="./summary/text()" />
				</xsl:element>
			</xsl:if>
		</xsl:element>
	</xsl:template>

	<xsl:template match="return-types/return-type">
		<xsl:element name="return-type">
			<xsl:attribute name="type">
				<xsl:value-of select="@type" />
			</xsl:attribute>
		</xsl:element>
	</xsl:template>

	<!-- we include the following template because PHP's XSLT engine likes to apply text anyways. -->
	<xsl:template match="text()"></xsl:template>
</xsl:stylesheet>
