<?php
/**
 * @package    Zend_Pdf
 * @subpackage UnitTests
 */


/**
 * Zend_Pdf_Element_Stream
 */
require_once 'Zend/Pdf/Element/Stream.php';

/**
 * PHPUnit2 Test Case
 */
require_once 'PHPUnit2/Framework/TestCase.php';


/**
 * @package    Zend_Pdf
 * @subpackage UnitTests
 */
class Zend_Pdf_Element_StreamTest extends PHPUnit2_Framework_TestCase
{
    public function testPDFStream()
    {
        $streamObj = new Zend_Pdf_Element_Stream('some text');
        $this->assertTrue($streamObj instanceof Zend_Pdf_Element_Stream);
    }

    public function testGetType()
    {
        $streamObj = new Zend_Pdf_Element_Stream('some text');
        $this->assertEquals($streamObj->getType(), Zend_Pdf_Element::TYPE_STREAM);
    }

    public function testToString()
    {
        $streamObj = new Zend_Pdf_Element_Stream("some text (\x00\x01\x02)\n");
        $this->assertEquals($streamObj->toString(), "stream\n\rsome text (\x00\x01\x02)\n\n\rendstream");
    }

    public function testLength()
    {
        $streamObj = new Zend_Pdf_Element_Stream("some text (\x00\x01\x02)\n");
        $this->assertEquals($streamObj->length(), 16);
    }

    public function testClear()
    {
        $streamObj = new Zend_Pdf_Element_Stream("some text (\x00\x01\x02)\n");
        $streamObj->clear();
        $this->assertEquals($streamObj->length(), 0);
        $this->assertEquals($streamObj->toString(), "stream\n\r\n\rendstream");
    }

    public function testAppend()
    {
        $streamObj = new Zend_Pdf_Element_Stream("some text (\x00\x01\x02)\n");
        $streamObj->append("somethong\xAF");
        $this->assertEquals($streamObj->length(), 16 + 10);
        $this->assertEquals($streamObj->toString(), "stream\n\rsome text (\x00\x01\x02)\nsomethong\xAF\n\rendstream");
    }
}
