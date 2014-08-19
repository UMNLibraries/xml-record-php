<?php

namespace UmnLib\Core\Tests\XmlRecord;

use UmnLib\Core\XmlRecord\Iterator;
use UmnLib\Core\XmlRecord\File\PubMed;
use Symfony\Component\Finder\Finder;

class IteratorTest extends \PHPUnit_Framework_TestCase
{
  protected function setUp()
  {
    $this->xmlRecordClass = '\UmnLib\Core\XmlRecord\PubMed';

    $finder = new Finder();
    $directory = dirname(__FILE__) . '/fixtures/unique';
    $files = $finder->name('*.xml')->in($directory);
    $filenames = array();
    foreach ($files as $file) {
      $filenames[] = $file->getRealPath();
    }
    $this->filenames = $filenames;
  }

  function testNew()
  {
    $file = new PubMed(array(
      'name' => $this->filenames[0],
    ));
    $xri = new Iterator(array(
      'file' => $file,
      'xmlRecordClass' => $this->xmlRecordClass,
    ));
    $this->assertInstanceOf('\UmnLib\Core\XmlRecord\Iterator', $xri);
    return $xri;
  }

  /**
   * @expectedException InvalidArgumentException
   */
  function testNewMissingParams()
  {
    // TODO: This fails if the params array is empty!
    $xri = new Iterator(array(
      'xmlRecordClass' => $this->xmlRecordClass,
    ));
  }

  /**
   * @depends testNew
   */
  function testXri($xri)
  {
    $this->runIterator($xri, 5);
  }

  function runIterator($xri, $count)
  {
    $xri->rewind();
    $records = array();

    while ($xri->valid()) {
      $record = $xri->current();

      // Sanity check that deserializing the record to a 
      // PHP array was successful:
      $this->assertInstanceOf('\UmnLib\Core\XmlRecord\Record', $record);

      $string = $record->asString();
      $versionString = '<?xml version="1.0"?>';
      $this->assertTrue( 
        strncmp($string, $versionString, strlen($versionString)) == 0
      ); 

      $fragmentString = $record->asFragmentString();
      $elementString = '<PubmedArticle>';
      $this->assertTrue( 
        strncmp($fragmentString, $elementString, strlen($elementString)) == 0
      ); 

      $array = $record->asArray();
      $this->assertTrue(is_array($array));

      // Ensure that this is a unique record, i.e. to ensure
      // that the iterator is correctly advancing through the records:
      //$pubmedId = $array['MedlineCitation']['PMID'];
      $pubmedId = $record->primaryId();
      $key = $xri->key();
      $this->assertEquals($pubmedId, $key);
      $this->assertFalse(array_key_exists($key, $records));

      $records[$key] = $record;
      $xri->next();
    }
    $this->assertTrue(count($records) == $count);
  }

    /*
    function testMalformedXml()
    {
        $f = new File_Find_Rule();
        $directory = getcwd() . '/fubar/bbc_malformed';
        $file_names = $f->name('*.xml')->in( $this->directory );
        require_once 'XML/Record/FeedItem/BBC.php';
        require_once 'XML/Record/File/Feed.php';
        $xmlRecordClass = 'XML_Record_FeedItem_BBC';
        $file = new XML_Record_File_Feed(array(
            'name' => $file_names[0],
        ));

        $xri = new XML_Record_Iterator(array(
            'file' => $file,
            'xmlRecordClass' => $xmlRecordClass,
        ));

        $xri->rewind();
        //while ($xri->valid()) {
        //    $record = $xri->current();
        //    $xri->next();
        //}
    }
     */
}
