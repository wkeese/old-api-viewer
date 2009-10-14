<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml" encoding="UTF-8" omit-xml-declaration="no" indent="yes" />

	<!-- Final XSLT to create a new minimized version of the api.xml file. -->
	<xsl:template match="/">
		<xsl:element name="javascript">
			<xsl:apply-templates select="javascript/object">
				<xsl:sort select="@location" />
			</xsl:apply-templates>
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
			<xsl:if test="mixins">
				<xsl:element name="mixins">
					<xsl:apply-templates select="./mixins/mixin" />
				</xsl:element>
			</xsl:if>
		</xsl:element>
	</xsl:template>

	<xsl:template match="object/mixins/mixin">
		<xsl:element name="mixin">
			<xsl:attribute name="scope">
				<xsl:value-of select="@scope" />
			</xsl:attribute>
			<xsl:attribute name="location">
				<xsl:value-of select="@location" />
			</xsl:attribute>
		</xsl:element>
	</xsl:template>

	<!-- we include the following template because PHP's XSLT engine likes to apply text anyways. -->
	<xsl:template match="text()"></xsl:template>
</xsl:stylesheet>
