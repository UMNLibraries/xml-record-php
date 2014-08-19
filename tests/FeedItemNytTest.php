<?php

namespace UmnLib\Core\Tests\XmlRecord;

use UmnLib\Core\XmlRecord\Iterator;
use UmnLib\Core\XmlRecord\File\Feed;
use Symfony\Component\Finder\Finder;

class FeedItemNytTest extends \PHPUnit_Framework_TestCase
{
  protected function setUp()
  {
    $this->xmlRecordClass = '\UmnLib\Core\XmlRecord\FeedItem\Nyt';

    $finder = new Finder();
    $directory = dirname(__FILE__) . '/fixtures/feeditem-nyt';
    $files = $finder->name('*.xml')->in($directory);
    $filenames = array();
    foreach ($files as $file) {
      $filenames[] = $file->getRealPath();
    }
    $this->filenames = $filenames;
  }

  function testNew()
  {
    $file = new Feed(array(
      'name' => $this->filenames[0],
    ));
    $xri = new Iterator(array(
      'file' => $file,
      'xmlRecordClass' => $this->xmlRecordClass,
    ));
    $this->assertInstanceOf('\UmnLib\Core\XmlRecord\Iterator', $xri);

    // TODO: Write tests for XmlRecord\File!!!
    //echo "header = " . $xri->file()->header() . "\n";
    //echo "footer = " . $xri->file()->footer() . "\n";

    return $xri;
  }

  /**
   * @depends testNew
   */
  function testXri($xri)
  {
    $this->runIterator($xri, 40);
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

      $simplepieItem = $record->asSimplepieItem();
      $this->assertInstanceOf('\SimplePie_Item', $simplepieItem); 

      $string = $record->asString();
      $versionString = '<?xml version="1.0"?>';
      $this->assertTrue( 
        strncmp($string, $versionString, strlen($versionString)) == 0
      ); 

      $array = $record->asArray();
      $this->assertTrue(is_array($array));
      //print_r($array);

      // TODO: Add (and test for?) other ids.
      //$ids = $record->ids();
      //print_r($ids);

      // Ensure that this is a unique record, i.e. to ensure
      // that the iterator is correctly advancing through the records:
      $url = $record->primaryId();
      $this->assertFalse(array_key_exists($url, $records));

      $records[$url] = $record;
      $xri->next();
    }
    $this->assertTrue(count($records) == $count);
  }
}
