<?php

namespace UmnLib\Core\Tests\XmlRecord;

use UmnLib\Core\XmlRecord\Iterator;
use UmnLib\Core\XmlRecord\File\WorldCatMarc;
use Symfony\Component\Finder\Finder;

class WorldCatMarcTest extends \PHPUnit_Framework_TestCase
{
  protected function setUp()
  {
    $this->xmlRecordClass = '\UmnLib\Core\XmlRecord\WorldCatMarc';

    $finder = new Finder();
    $directory = dirname(__FILE__) . '/fixtures/worldcat-marc';
    $files = $finder->name('*.xml')->in($directory);
    $filenames = array();
    foreach ($files as $file) {
      $filenames[] = $file->getRealPath();
    }
    $this->filenames = $filenames;
  }

  function testNew()
  {
    $file = new WorldCatMarc(array(
      //'name' => $this->filenames[0], // Got confused with the naming of multiple files in this directory, led to mysterious bugs.
      // TODO: Fix the hard-coding!!!
      'name' => dirname(__FILE__) . '/fixtures/worldcat-marc/2009-04-06-16-02-48.xml',
    ));
    $xri = new Iterator(array(
      'file' => $file,
      'xmlRecordClass' => $this->xmlRecordClass,
    ));
    $this->assertInstanceOf('\UmnLib\Core\XmlRecord\Iterator', $xri);

    // TODO: Write tests for XML_Record_File!!!
    //echo "header = " . $xri->file()->header() . "\n";
    //echo "footer = " . $xri->file()->footer() . "\n";

    return $xri;
  }

  /**
   * @depends testNew
   */
  function testXri($xri)
  {
    $records = $this->runIterator($xri, 12);
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

/*
      $fragmentString = $record->asFragmentString();
      $elementString = '<record>';
      $this->assertTrue( 
          strncmp($fragmentString, $elementString, strlen($elementString)) == 0
      ); 
 */
      $array = $record->asArray();
      $this->assertTrue(is_array($array));
      //print_r($array);

      // TODO: Add (and test for?) other ids.
      $ids = $record->ids();
      //print_r($ids);

      // Ensure that this is a unique record, i.e. to ensure
      // that the iterator is correctly advancing through the records:
      $oclcId = $record->primaryId();
      $this->assertFalse(array_key_exists($oclcId, $records));

      $records[$oclcId] = $record;
      $xri->next();
    }
    $recordCount = count($records);
    $this->assertEquals($count, count($records));
    return $records;
  }

  function testFields()
  {
    // TODO: Rewinding an already-existing iterator isn't working!!!
        /*
        $xri = $this->xri;
        $xri->rewind();
         */
    //echo "filename = "; print_r($this->filenames[0]);
    $file = new WorldCatMarc(array(
      //'name' => $this->filenames[0],
      // TODO: Fix the hard-coding!!! This test was working with the above line at one point.
      'name' => dirname(__FILE__) . '/fixtures/worldcat-marc/2009-04-06-16-02-48.xml',
    ));
    $xri = new Iterator(array(
      'file' => $file,
      'xmlRecordClass' => $this->xmlRecordClass,
    ));

    $xri->rewind();
    while ($xri->valid()) {
      $record = $xri->current();

      //echo "record = "; var_dump($record); echo "\n";

      // We create Datafield objects lazily, so make sure that
      // we aren't creating a new object every time we call datafield():
      $datafields042 = $record->datafield('042');
      $count = count($datafields042);
      $this->assertEquals(1, $count);
      $datafields042_2 = $record->datafield('042');
      $count2 = count($datafields042_2);
      $this->assertEquals($count, $count2);

      $datafield042 = $datafields042[0];
      //echo "datafield042 = "; print_r($datafield042); echo "\n";
      $this->assertInstanceOf('\UmnLib\Core\XmlRecord\WorldCatMarc\Datafield', $datafield042);

      // Test the attributes;
      $this->assertEquals('042', $datafield042->tag());
      $this->assertEquals(' ', $datafield042->ind1());
      $this->assertEquals(' ', $datafield042->ind2());

      // Test multiple subfields with the same code:
      $subfieldA = $datafield042->subfield('a');
      $this->assertEquals(array('lccopycat', 'lcode'), $subfieldA);
      $subfields042 = $datafield042->subfields();
      $this->assertEquals(
        array(
          0 => array('a' => 'lccopycat'),
          1 => array('a' => 'lcode'),
        ),
        $subfields042
      );

      // Test subfields with all different codes, including numeric, make sure
      // they are returned in the same order that they appear in the XML file:
      $datafields245 = $record->datafield('245');
      $datafield245 = $datafields245[0];
      $this->assertEquals('245', $datafield245->tag());
      $this->assertEquals(1, $datafield245->ind1());
      $this->assertEquals(0, $datafield245->ind2());
      $subfieldA = $datafield245->subfield('a');
      // Should be only one value this time:
      $this->assertEquals(array('Ḥuqūq al-insān bayna al-ʻArab wa-al-Amrīkān /'), $subfieldA);
      $subfields245 = $datafield245->subfields();
      $this->assertEquals(
        array(
          0 => array('6' => '880-02'),
          1 => array('a' => 'Ḥuqūq al-insān bayna al-ʻArab wa-al-Amrīkān /'),
          2 => array('c' => 'taʼlīf Muḥammad ibn ʻAlī al-Hirfī.'),
        ),
        $subfields245
      );

      // Run these tests only on the first record.
      break;
    }
  }

  function testIds()
  {
    // TODO: Rewinding an already-existing iterator isn't working!!!
        /*
        $xri = $this->xri;
        $xri->rewind();
         */

    $file = new WorldCatMarc(array(
      'name' => dirname(__FILE__) . '/fixtures/worldcat-marc/ids.xml',
    ));
    $xri = new Iterator(array(
      'file' => $file,
      'xmlRecordClass' => $this->xmlRecordClass,
    ));

    $dois = array();

    $xri->rewind();
    while ($xri->valid()) {
      $record = $xri->current();

      $oclcId = $record->primaryId();
      $currentDois = array();
      $ids = $record->ids();
      foreach ($ids as $id) {
        if ('doi' != $id['type']) continue;
        $currentDois[] = $id['value'];
      }
      $this->assertEquals(1, count($currentDois));
      $dois[$oclcId] = $currentDois[0];

      $xri->next();
    }
    $this->assertEquals(
      array(
        '187312314' => '10.1007/978-1-4020-5214-9',
        '642838992' => '10.2986/tren.083-0034',
        '432044712' => '10.1201/9780203875438',
        '636383267' => '10.1007/978-1-59745-096-6',
        '681908642' => '10.1057/9780230286269',
        '680435658' => '10.1111/j.1467-8519.2004.00378.x',
        '319064490' => '10.1093/acprof:oso/9780195325461.001.0001',
        '441838450' => '10.1136/jme.2005.014415',
      ),
      $dois
    );
  }

  function testLeader()
  {
    // TODO: Rewinding an already-existing iterator isn't working!!!
        /*
        $xri = $this->xri;
        $xri->rewind();
         */

    $file = new WorldCatMarc(array(
      'name' => $this->filenames[0],
    ));
    $xri = new Iterator(array(
      'file' => $file,
      'xmlRecordClass' => $this->xmlRecordClass,
    ));

    $xri->rewind();
    while ($xri->valid()) {
      $record = $xri->current();
      $this->assertEquals('00000cam a2200000Ia 4500', $record->leader());
      $xri->next();
      break;
    }
  }
}
