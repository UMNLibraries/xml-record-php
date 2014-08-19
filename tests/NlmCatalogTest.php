<?php

namespace UmnLib\Core\Tests\XmlRecord;

use UmnLib\Core\XmlRecord\Iterator;
use UmnLib\Core\XmlRecord\File\NlmCatalog;
use Symfony\Component\Finder\Finder;

class NlmCatalogTest extends \PHPUnit_Framework_TestCase
{
  protected function setUp()
  {
    $this->xmlRecordClass = '\UmnLib\Core\XmlRecord\NlmCatalog';

    $finder = new Finder();
    $directory = dirname(__FILE__) . '/fixtures/nlmcatalog';
    $files = $finder->name('*.xml')->in($directory);
    $filenames = array();
    foreach ($files as $file) {
      $filenames[] = $file->getRealPath();
    }
    $this->filenames = $filenames;
  }

  function testNew()
  {
    $file = new NlmCatalog(array(
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
   * @depends testNew
   */
  function testXri($xri)
  {
    $records = $this->runIterator($xri, 4);
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
      $versionString = '<?xml version="1.0" encoding="UTF-8"?>';
      $this->assertTrue( 
        strncmp($string, $versionString, strlen($versionString)) == 0
      ); 

      $array = $record->asArray();
      $this->assertTrue(is_array($array));
      //print_r( $array );

      // TODO: Add (and test for?) other ids.
      $ids = $record->ids();
      //print_r($ids);

      // Ensure that this is a unique record, i.e. to ensure
      // that the iterator is correctly advancing through the records:
      $nlmUniqueId = $record->primaryId();
      $this->assertFalse(array_key_exists($nlmUniqueId, $records));

      $records[$nlmUniqueId] = $record;
      $xri->next();
    }
    $recordCount = count($records);
    $this->assertEquals($count, count($records));
    return $records;
  }
}
