#!/usr/bin/php -q
<?php

require_once 'simpletest/autorun.php';
SimpleTest :: prefer(new TextReporter());
set_include_path('../php' . PATH_SEPARATOR . get_include_path());
require_once 'XML/Record/Iterator.php';
require_once 'XML/Record/File/NLMCatalog.php';
require_once 'File/Find/Rule.php';

ini_set('memory_limit', '512M');

//error_reporting( E_STRICT );

class XMLRecordIteratorTest extends UnitTestCase
{
    public function __construct()
    {
        $f = new File_Find_Rule();
        $this->directory = getcwd() . '/nlmcatalog';
        $this->file_names = $f->name('*.xml')->in( $this->directory );
        $this->xml_record_class = 'XML_Record_NLMCatalog';
    }

    public function test_new()
    {
        $file = new XML_Record_File_NLMCatalog(array(
            'name' => $this->file_names[0],
        ));
        $xri = new XML_Record_Iterator(array(
            'file' => $file,
            'xml_record_class' => $this->xml_record_class,
        ));
        $this->assertIsA( $xri, 'XML_Record_Iterator' );
        $this->xri = $xri;
    }

    public function test_xri()
    {
        $records = $this->run_iterator($this->xri, 4);
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
            $version_string = '<?xml version="1.0" encoding="UTF-8"?>';
            $this->assertTrue( 
                strncmp($string, $version_string, strlen($version_string)) == 0
            ); 

            $array = $record->as_array();
            $this->assertTrue( is_array($array) );
            //print_r( $array );

            // TODO: Add (and test for?) other ids.
            $ids = $record->ids();
            //print_r( $ids );

            // Ensure that this is a unique record, i.e. to ensure
            // that the iterator is correctly advancing through the records:
            $nlmUniqueId = $record->primary_id();
            $this->assertFalse( array_key_exists($nlmUniqueId, $records) );

            $records[$nlmUniqueId] = $record;
            $xri->next();
        }
        $record_count = count($records);
        $this->assertEqual(count($records), $count);
        return $records;
    }

}
