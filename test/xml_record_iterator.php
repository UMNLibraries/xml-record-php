#!/usr/bin/php -q
<?php

require_once 'simpletest/autorun.php';
SimpleTest :: prefer(new TextReporter());
set_include_path('../php' . PATH_SEPARATOR . get_include_path());
require_once 'XML/Record/Iterator.php';
require_once 'XML/Record/File/PubMed.php';
require_once 'File/Find/Rule.php';

ini_set('memory_limit', '512M');

//error_reporting( E_STRICT );

class XMLRecordIteratorTest extends UnitTestCase
{
    public function __construct()
    {
        $f = new File_Find_Rule();
        $this->directory = getcwd() . '/unique';
        $this->file_names = $f->name('*.xml')->in( $this->directory );
        $this->xml_record_class = 'XML_Record_PubMed';
    }

    public function test_new()
    {
        $file = new XML_Record_File_PubMed(array(
            'name' => $this->file_names[0],
        ));

        $xri = new XML_Record_Iterator(array(
            'file' => $file,
            'xml_record_class' => $this->xml_record_class,
        ));

        $this->assertIsA( $xri, 'XML_Record_Iterator' );
        $this->xri = $xri;
    }

    public function test_new_missing_params()
    {
        $this->expectException();
        // TODO: This fails if the params array is empty!
        $xri = new XML_Record_Iterator(array(
            'xml_record_class' => $this->xml_record_class,
        ));
    }

    public function test_xri()
    {
        $this->run_iterator($this->xri, 5);
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

            $fragment_string = $record->as_fragment_string();
            $element_string = '<PubmedArticle>';
            $this->assertTrue( 
                strncmp($fragment_string, $element_string, strlen($element_string)) == 0
            ); 

            $array = $record->as_array();
            $this->assertTrue( is_array($array) );

            // Ensure that this is a unique record, i.e. to ensure
            // that the iterator is correctly advancing through the records:
            //$pubmed_id = $array['MedlineCitation']['PMID'];
            $pubmed_id = $record->primary_id();
            $key = $xri->key();
            $this->assertEqual($pubmed_id, $key);
            $this->assertFalse( array_key_exists($key, $records) );

            $records[$key] = $record;
            $xri->next();
        }
        $this->assertTrue(count($records) == $count);
    }

    public function test_malformed_xml()
    {
        $f = new File_Find_Rule();
        $directory = getcwd() . '/fubar/bbc_malformed';
        $file_names = $f->name('*.xml')->in( $this->directory );
        require_once 'XML/Record/FeedItem/BBC.php';
        require_once 'XML/Record/File/Feed.php';
        $xml_record_class = 'XML_Record_FeedItem_BBC';
        $file = new XML_Record_File_Feed(array(
            'name' => $file_names[0],
        ));

        $xri = new XML_Record_Iterator(array(
            'file' => $file,
            'xml_record_class' => $xml_record_class,
        ));

        $xri->rewind();
        /*
        while ($xri->valid()) {
            $record = $xri->current();
            $xri->next();
        }
        */
    }

} // end class XMLRecordIteratorTest
