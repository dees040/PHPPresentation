<?php

namespace PhpOffice\PhpPresentation\Tests;

use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;

class PhpPresentationTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PhpPresentation
     */
    protected $oPresentation;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var string
     */
    protected $workDirectory;

    /**
     * DOMDocument object
     *
     * @var \DOMDocument
     */
    private $xmlDom;

    /**
     * DOMXpath object
     *
     * @var \DOMXpath
     */
    private $xmlXPath;

    /**
     * File name
     *
     * @var string
     */
    private $xmlFile;

    /**
     * Executed before each method of the class
     */
    public function setUp()
    {
        $this->workDirectory = sys_get_temp_dir() . '/PhpPresentation_Unit_Test/';
        $this->oPresentation = new PhpPresentation();

        $this->filePath = tempnam(sys_get_temp_dir(), 'PhpPresentation');
        $this->resetPresentationFile();
    }

    /**
     * Executed after each method of the class
     */
    public function tearDown()
    {
        $this->oPresentation = null;
        $this->resetPresentationFile();
    }

    /**
     * Delete directory
     *
     * @param string $dir
     */
    private function deleteDir($dir)
    {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            } elseif (is_file($dir . "/" . $file)) {
                unlink($dir . "/" . $file);
            } elseif (is_dir($dir . "/" . $file)) {
                $this->deleteDir($dir . "/" . $file);
            }
        }

        rmdir($dir);
    }

    private function getXmlDom($file)
    {
        $baseFile = $file;
        if (null !== $this->xmlDom && $file === $this->xmlFile) {
            return $this->xmlDom;
        }

        $this->xmlXPath = null;
        $this->xmlFile = $file;

        $file = $this->workDirectory . '/' . $file;
        $this->xmlDom = new \DOMDocument();
        $strContent = file_get_contents($file);
        // docProps/app.xml
        if ($baseFile == 'docProps/app.xml') {
            $strContent = str_replace(' xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"', '', $strContent);
        }
        // docProps/custom.xml
        if ($baseFile == 'docProps/custom.xml') {
            $strContent = str_replace(' xmlns="http://schemas.openxmlformats.org/officeDocument/2006/custom-properties"', '', $strContent);
        }
        // _rels/.rels
        if (strpos($baseFile, '_rels/') !== false && strpos($baseFile, '.rels') !== false) {
            $strContent = str_replace(' xmlns="http://schemas.openxmlformats.org/package/2006/relationships"', '', $strContent);
        }
        $this->xmlDom->loadXML($strContent);
        return $this->xmlDom;
    }

    private function getXmlNodeList($file, $xpath)
    {
        if ($this->xmlDom === null || $file !== $this->xmlFile) {
            $this->getXmlDom($file);
        }

        if (null === $this->xmlXPath) {
            $this->xmlXPath = new \DOMXpath($this->xmlDom);
        }

        return $this->xmlXPath->query($xpath);
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     */
    protected function writePresentationFile(PhpPresentation $oPhpPresentation, $writerName)
    {
        if (is_file($this->filePath)) {
            return;
        }

        $xmlWriter = IOFactory::createWriter($oPhpPresentation, $writerName);
        $xmlWriter->save($this->filePath);

        $zip = new \ZipArchive;
        $res = $zip->open($this->filePath);
        if ($res === true) {
            $zip->extractTo($this->workDirectory);
            $zip->close();
        }
    }

    protected function resetPresentationFile()
    {
        $this->xmlFile = null;
        $this->xmlDom = null;
        $this->xmlXPath = null;
        if (is_file($this->filePath)) {
            unlink($this->filePath);
        }
        if (is_dir($this->workDirectory)) {
            $this->deleteDir($this->workDirectory);
        }
        if (!is_dir($this->workDirectory)) {
            mkdir($this->workDirectory);
        }
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     * @param string $filePath
     */
    public function assertZipFileExists(PhpPresentation $oPhpPresentation, $writerName, $filePath)
    {
        $this->writePresentationFile($oPhpPresentation, $writerName);
        self::assertThat(is_file($this->workDirectory . $filePath), self::isTrue());
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     * @param string $filePath
     */
    public function assertZipFileNotExists(PhpPresentation $oPhpPresentation, $writerName, $filePath)
    {
        $this->writePresentationFile($oPhpPresentation, $writerName);
        self::assertThat(is_file($this->workDirectory . $filePath), self::isFalse());
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     * @param string $filePath
     * @param string $xPath
     */
    public function assertZipXmlElementExists(PhpPresentation $oPhpPresentation, $writerName, $filePath, $xPath)
    {
        $this->writePresentationFile($oPhpPresentation, $writerName);
        $nodeList = $this->getXmlNodeList($filePath, $xPath);
        self::assertThat(!($nodeList->length == 0), self::isTrue());
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     * @param string $filePath
     * @param string $xPath
     */
    public function assertZipXmlElementNotExists(PhpPresentation $oPhpPresentation, $writerName, $filePath, $xPath)
    {
        $this->writePresentationFile($oPhpPresentation, $writerName);
        $nodeList = $this->getXmlNodeList($filePath, $xPath);
        self::assertThat(!($nodeList->length == 0), self::isFalse());
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     * @param string $filePath
     * @param string $xPath
     * @param mixed $value
     */
    public function assertZipXmlElementEquals(PhpPresentation $oPhpPresentation, $writerName, $filePath, $xPath, $value)
    {
        $this->writePresentationFile($oPhpPresentation, $writerName);
        $nodeList = $this->getXmlNodeList($filePath, $xPath);
        self::assertEquals($nodeList->item(0)->nodeValue, $value);
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     * @param string $filePath
     * @param string $xPath
     * @param int $num
     */
    public function assertZipXmlElementCount(PhpPresentation $oPhpPresentation, $writerName, $filePath, $xPath, $num)
    {
        $this->writePresentationFile($oPhpPresentation, $writerName);
        $nodeList = $this->getXmlNodeList($filePath, $xPath);
        self::assertEquals($nodeList->length, $num);
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     * @param string $filePath
     * @param string $xPath
     * @param string $attribute
     * @param mixed $value
     */
    public function assertZipXmlAttributeEquals(PhpPresentation $oPhpPresentation, $writerName, $filePath, $xPath, $attribute, $value)
    {
        $this->writePresentationFile($oPhpPresentation, $writerName);
        $nodeList = $this->getXmlNodeList($filePath, $xPath);
        self::assertEquals($value, $nodeList->item(0)->getAttribute($attribute));
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     * @param string $filePath
     * @param string $xPath
     * @param string $attribute
     * @param mixed $value
     */
    public function assertZipXmlAttributeStartsWith(PhpPresentation $oPhpPresentation, $writerName, $filePath, $xPath, $attribute, $value)
    {
        $this->writePresentationFile($oPhpPresentation, $writerName);
        $nodeList = $this->getXmlNodeList($filePath, $xPath);
        self::assertStringStartsWith($value, $nodeList->item(0)->getAttribute($attribute));
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     * @param string $filePath
     * @param string $xPath
     * @param string $attribute
     * @param mixed $value
     */
    public function assertZipXmlAttributeEndsWith(PhpPresentation $oPhpPresentation, $writerName, $filePath, $xPath, $attribute, $value)
    {
        $this->writePresentationFile($oPhpPresentation, $writerName);
        $nodeList = $this->getXmlNodeList($filePath, $xPath);
        self::assertStringEndsWith($value, $nodeList->item(0)->getAttribute($attribute));
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     * @param string $filePath
     * @param string $xPath
     * @param string $attribute
     * @param mixed $value
     */
    public function assertZipXmlAttributeContains(PhpPresentation $oPhpPresentation, $writerName, $filePath, $xPath, $attribute, $value)
    {
        $this->writePresentationFile($oPhpPresentation, $writerName);
        $nodeList = $this->getXmlNodeList($filePath, $xPath);
        self::assertContains($value, $nodeList->item(0)->getAttribute($attribute));
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     * @param string $filePath
     * @param string $xPath
     * @param string $attribute
     */
    public function assertZipXmlAttributeExists(PhpPresentation $oPhpPresentation, $writerName, $filePath, $xPath, $attribute)
    {
        $this->writePresentationFile($oPhpPresentation, $writerName);
        $nodeList = $this->getXmlNodeList($filePath, $xPath);
        self::assertTrue($nodeList->item(0)->hasAttribute($attribute));
    }

    /**
     * @param PhpPresentation $oPhpPresentation
     * @param string $writerName
     * @param string $filePath
     * @param string $xPath
     * @param string $attribute
     */
    public function assertZipXmlAttributeNotExists(PhpPresentation $oPhpPresentation, $writerName, $filePath, $xPath, $attribute)
    {
        $this->writePresentationFile($oPhpPresentation, $writerName);
        $nodeList = $this->getXmlNodeList($filePath, $xPath);
        self::assertFalse($nodeList->item(0)->hasAttribute($attribute));
    }
}
