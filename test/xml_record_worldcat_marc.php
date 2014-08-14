#!/usr/bin/php -q
<?php

require_once 'simpletest/autorun.php';
SimpleTest :: prefer(new TextReporter());
set_include_path('../php' . PATH_SEPARATOR . get_include_path());
require_once 'XML/Record/Iterator.php';
require_once 'XML/Record/File/WorldCat/MARC.php';
require_once 'File/Find/Rule.php';

ini_set('memory_limit', '512M');

//error_reporting( E_STRICT );

class XMLRecordIteratorTest extends UnitTestCase
{
    public function __construct()
    {
        $f = new File_Find_Rule();
        $this->directory = getcwd() . '/worldcat_marc';
        $this->file_names = $f->name('*.xml')->in( $this->directory );
        $this->xml_record_class = 'XML_Record_WorldCat_MARC';
    }

    public function test_new()
    {
        $file = new XML_Record_File_WorldCat_MARC(array(
            //'name' => $this->file_names[0], // Got confused with the naming of multiple files in this directory, led to mysterious bugs.
            // TODO: Fix the hard-coding!!!
            'name' => getcwd() . '/worldcat_marc/2009-04-06-16-02-48.xml',
        ));
        $xri = new XML_Record_Iterator(array(
            'file' => $file,
            'xml_record_class' => $this->xml_record_class,
        ));
        $this->assertIsA( $xri, 'XML_Record_Iterator' );
        $this->xri = $xri;

        // TODO: Write tests for XML_Record_File!!!
        //echo "file_header = " . $xri->file()->header() . "\n";
        //echo "file_footer = " . $xri->file()->footer() . "\n";
    }

    public function test_xri()
    {
        $records = $this->run_iterator($this->xri, 12);
    }

    public function run_iterator($xri, $count)
    {
        $xri->rewind();
        $records = array();

        while ($xri->valid()) {
            $record = $xri->current();

            // Sanity check that deserializing the record to a 
            // PHP array was successful:
            $this->assertIsA( $record, 'XML_Record' );
            
            $string = $record->as_string();
            $version_string = '<?xml version="1.0"?>';
            $this->assertTrue( 
                strncmp($string, $version_string, strlen($version_string)) == 0
            ); 

/*
            $fragment_string = $record->as_fragment_string();
            $element_string = '<record>';
            $this->assertTrue( 
                strncmp($fragment_string, $element_string, strlen($element_string)) == 0
            ); 
*/

            $array = $record->as_array();
            $this->assertTrue( is_array($array) );
            //print_r( $array );

            // TODO: Add (and test for?) other ids.
            $ids = $record->ids();
            //print_r( $ids );

            // Ensure that this is a unique record, i.e. to ensure
            // that the iterator is correctly advancing through the records:
            $oclc_id = $record->primary_id();
            $this->assertFalse( array_key_exists($oclc_id, $records) );

            $records[$oclc_id] = $record;
            $xri->next();
        }
        $record_count = count($records);
        $this->assertEqual($record_count, $count);
        return $records;
    }

    public function test_fields()
    {
        // TODO: Rewinding an already-existing iterator isn't working!!!
        /*
        $xri = $this->xri;
        $xri->rewind();
        */

        $file = new XML_Record_File_WorldCat_MARC(array(
            'name' => $this->file_names[0],
        ));
        $xri = new XML_Record_Iterator(array(
            'file' => $file,
            'xml_record_class' => $this->xml_record_class,
        ));

        $xri->rewind();
        while ($xri->valid()) {
            $record = $xri->current();

            //echo "record = "; var_dump($record); echo "\n";

            // We create Datafield objects via lazily, so make sure that
            // we aren't creating a new object every time we call datafield():
            $datafields_042 = $record->datafield('042');
            $count = count($datafields_042);
            $this->assertTrue($count == 1);
            $datafields_042_2 = $record->datafield('042');
            $count_2 = count($datafields_042_2);
            $this->assertTrue($count == $count_2);

            $datafield_042 = $datafields_042[0];
            //echo "datafield_042 = "; print_r($datafield_042); echo "\n";
            $this->assertIsA($datafield_042, 'XML_Record_WorldCat_MARC_Datafield');

            // Test the attributes;
            $this->assertEqual('042', $datafield_042->tag());
            $this->assertEqual(' ', $datafield_042->ind1());
            $this->assertEqual(' ', $datafield_042->ind2());

            // Test multiple subfields with the same code:
            $subfield_a = $datafield_042->subfield('a');
            $this->assertEqual($subfield_a, array('lccopycat', 'lcode'));
            $subfields_042 = $datafield_042->subfields();
            $this->assertEqual(
                $subfields_042,
                array(
                    0 => array('a' => 'lccopycat'),
                    1 => array('a' => 'lcode'),
                )
            );
            
            // Test subfields with all different codes, including numeric, make sure
            // they are returned in the same order that they appear in the XML file:
            $datafields_245 = $record->datafield('245');
            $datafield_245 = $datafields_245[0];
            $this->assertEqual('245', $datafield_245->tag());
            $this->assertEqual(1, $datafield_245->ind1());
            $this->assertEqual(0, $datafield_245->ind2());
            $subfield_a = $datafield_245->subfield('a');
            // Should be only one value this time:
            $this->assertEqual($subfield_a, array('Ḥuqūq al-insān bayna al-ʻArab wa-al-Amrīkān /'));
            $subfields_245 = $datafield_245->subfields();
            $this->assertEqual(
                $subfields_245,
                array(
                    0 => array('6' => '880-02'),
                    1 => array('a' => 'Ḥuqūq al-insān bayna al-ʻArab wa-al-Amrīkān /'),
                    2 => array('c' => 'taʼlīf Muḥammad ibn ʻAlī al-Hirfī.'),
                )
            );

            // Run these tests only on the first record.
            break;
        }
    }

    public function test_ids()
    {
        // TODO: Rewinding an already-existing iterator isn't working!!!
        /*
        $xri = $this->xri;
        $xri->rewind();
        */

        $file = new XML_Record_File_WorldCat_MARC(array(
            'name' => getcwd() . '/worldcat_marc/ids.xml',
        ));
        $xri = new XML_Record_Iterator(array(
            'file' => $file,
            'xml_record_class' => $this->xml_record_class,
        ));

        $dois = array();

        $xri->rewind();
        while ($xri->valid()) {
            $record = $xri->current();

            $oclc_id = $record->primary_id();
            $current_dois = array();
            $ids = $record->ids();
            foreach ($ids as $id) {
                if ('doi' != $id['type']) continue;
                $current_dois[] = $id['value'];
            }
            $this->assertEqual(1, count($current_dois));
            $dois[$oclc_id] = $current_dois[0];

            $xri->next();
        }
        $this->assertEqual(
            $dois,
            array(
               '187312314' => '10.1007/978-1-4020-5214-9',
               '642838992' => '10.2986/tren.083-0034',
               '432044712' => '10.1201/9780203875438',
               '636383267' => '10.1007/978-1-59745-096-6',
               '681908642' => '10.1057/9780230286269',
               '680435658' => '10.1111/j.1467-8519.2004.00378.x',
               '319064490' => '10.1093/acprof:oso/9780195325461.001.0001',
               '441838450' => '10.1136/jme.2005.014415',
            )
        );
    }

    public function test_leader()
    {
        // TODO: Rewinding an already-existing iterator isn't working!!!
        /*
        $xri = $this->xri;
        $xri->rewind();
        */

        $file = new XML_Record_File_WorldCat_MARC(array(
            'name' => $this->file_names[0],
        ));
        $xri = new XML_Record_Iterator(array(
            'file' => $file,
            'xml_record_class' => $this->xml_record_class,
        ));

        $xri->rewind();
        while ($xri->valid()) {
            $record = $xri->current();
            $this->assertEqual($record->leader(), '00000cam a22000004a 4500');
            $xri->next();
            break;
        }
    }

}
