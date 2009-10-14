<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml" encoding="UTF-8" omit-xml-declaration="no" indent="yes" />
	<xsl:template match="/">
		<xsl:element name="javascript">
			<xsl:apply-templates select="javascript/object" />
		</xsl:element>
	</xsl:template>

	<xsl:template match="object[./resources/resource]">
		<xsl:element name="object">
			<xsl:attribute name="location">
				<xsl:value-of select="@location" />
			</xsl:attribute>
			<xsl:element name="resources">
				<xsl:apply-templates select="./resources/resource" />
			</xsl:element>
		</xsl:element>
	</xsl:template>

	<xsl:template match="object/resources/resource">
		<xsl:element name="resource">
			<xsl:value-of select="./text()" />
		</xsl:element>
	</xsl:template>

	<!-- we include the following template because PHP's XSLT engine likes to apply text anyways. -->
	<xsl:template match="text()"></xsl:template>
</xsl:stylesheet>
