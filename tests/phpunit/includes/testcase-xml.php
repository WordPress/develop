<?php

abstract class WP_Test_XML_TestCase extends WP_UnitTestCase {
	/**
	 * Load XML from a string.
	 *
	 * @param string $xml
	 * @param int    $options Bitwise OR of the {@link https://www.php.net/manual/en/libxml.constants.php libxml option constants}.
	 *                        Default is 0.
	 * @return DOMDocument The DOMDocument object loaded from the XML.
	 */
	public function loadXML( $xml, $options = 0 ) {
		// Suppress PHP warnings generated by DOMDocument::loadXML(), which would cause
		// PHPUnit to incorrectly report an error instead of a just a failure.
		$internal = libxml_use_internal_errors( true );
		libxml_clear_errors();

		$xml_dom = new DOMDocument();
		$xml_dom->loadXML( $xml, $options );
		$libxml_last_error = libxml_get_last_error();

		$this->assertFalse(
			isset( $libxml_last_error->message ),
			isset( $libxml_last_error->message ) ? sprintf( 'Non-well-formed XML: %s.', $libxml_last_error->message ) : ''
		);

		// Restore default error handler.
		libxml_use_internal_errors( $internal );
		libxml_clear_errors();

		return $xml_dom;
	}

	/**
	 * Normalize an XML document to make comparing two documents easier.
	 *
	 * @param string $xml
	 * @param int    $options Bitwise OR of the {@link https://www.php.net/manual/en/libxml.constants.php libxml option constants}.
	 *                        Default is 0.
	 * @return string The normalized form of `$xml`.
	 */
	public function normalizeXML( $xml, $options = 0 ) {
		if ( ! class_exists( 'XSLTProcessor' ) ) {
			$this->markTestSkipped( 'This test requires the XSL extension.' );
		}

		static $xslt_proc;

		if ( ! $xslt_proc ) {
			$xslt_proc = new XSLTProcessor();
			$xslt_proc->importStyleSheet( simplexml_load_file( __DIR__ . '/normalize-xml.xsl' ) );
		}

		return $xslt_proc->transformToXML( $this->loadXML( $xml, $options ) );
	}

	/**
	 * Reports an error identified by `$message` if the namespace normalized form of the XML document in `$actualXml`
	 * is equal to the namespace normalized form of the XML document in `$expectedXml`.
	 *
	 * This is similar to {@link https://phpunit.de/manual/6.5/en/appendixes.assertions.html#appendixes.assertions.assertXmlStringEqualsXmlString assertXmlStringEqualsXmlString()}
	 * except that differences in namespace prefixes are normalized away, such that given
	 * `$actualXml = "<root xmlns='urn:wordpress.org'><child/></root>";` and
	 * `$expectedXml = "<ns0:root xmlns:ns0='urn:wordpress.org'><ns0:child></ns0:root>";`
	 * then `$this->assertXMLEquals( $expectedXml, $actualXml )` will succeed.
	 *
	 * @param string $expectedXml
	 * @param string $actualXml
	 * @param string $message   Optional. Message to display when the assertion fails.
	 */
	public function assertXMLEquals( $expectedXml, $actualXml, $message = '' ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$this->assertSame( $this->normalizeXML( $expectedXml ), $this->normalizeXML( $actualXml ), $message ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	}

	/**
	 * Reports an error identified by `$message` if the namespace normalized form of the XML document in `$actualXml`
	 * is not equal to the namespace normalized form of the XML document in `$expectedXml`.
	 *
	 * This is similar to {@link https://phpunit.de/manual/6.5/en/appendixes.assertions.html#appendixes.assertions.assertXmlStringEqualsXmlString assertXmlStringNotEqualsXmlString()}
	 * except that differences in namespace prefixes are normalized away, such that given
	 * `$actualXml = "<root xmlns='urn:wordpress.org'><child></root>";` and
	 * `$expectedXml = "<ns0:root xmlns:ns0='urn:wordpress.org'><ns0:child/></ns0:root>";`
	 * then `$this->assertXMLNotEquals( $expectedXml, $actualXml )` will fail.
	 *
	 * @param string $expectedXml
	 * @param string $actualXml
	 * @param string $message   Optional. Message to display when the assertion fails.
	 */
	public function assertXMLNotEquals( $expectedXml, $actualXml, $message = '' ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$this->assertNotEquals( $this->normalizeXML( $expectedXml ), $this->normalizeXML( $actualXml ), $message ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	}
}
