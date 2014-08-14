#!/usr/bin/php -q
<?php

require_once 'simpletest/autorun.php';
SimpleTest :: prefer(new TextReporter());
set_include_path('../php' . PATH_SEPARATOR . get_include_path());
require_once 'XML/Record/Deduplicator.php';
require_once 'File/Find/Rule.php';
require_once 'File/Set/DateSequence.php';

ini_set('memory_limit', '512M');

//error_reporting( E_ALL );

class XMLRecordDeduplicatorTest extends UnitTestCase
{
    public function __construct()
    {
        $this->dupes_directory = getcwd() . '/dupes';
        $this->deduped_directory = getcwd() . '/deduped';
        $this->rededuped_directory = getcwd() . '/rededuped';

        $this->suffix = '.xml';        
        $this->dupes_file_set = new File_Set_DateSequence(array(
            'directory' => $this->dupes_directory,
            'suffix' => $this->suffix,
        ));

        $this->deduped_file_set = new File_Set_DateSequence(array(
            'directory' => $this->deduped_directory,
            'suffix' => $this->suffix,
        ));
        $this->rededuped_file_set = new File_Set_DateSequence(array(
            'directory' => $this->rededuped_directory,
            'suffix' => $this->suffix,
        ));

        $f = new File_Find_Rule();
        $xml_dupe_file_names = $f->name('*.xml')->in( $this->dupes_directory );
        $this->dupes_file_count = count($xml_dupe_file_names);
        $this->cleanup();

        // TODO: Improve this weak good-enough-for-now test of the case of
        // unprocessed-xml-file-in-output-directory. If we copy one of the 
        // dupes files to the deduped directory, it should just get copied
        // back to the dupes directory, and the previously-written tests
        // should all still pass: 
        $dupe_base_name = basename( $xml_dupe_file_names[0] );
        copy(
            $xml_dupe_file_names[0],
            $this->deduped_directory . '/' . $dupe_base_name
        );

        $this->test_id_set = new TestIdentifierSet();
        $this->test_id_set->add_member('19226734');
    }

    public function test_new()
    {
        $xrd = new XML_Record_Deduplicator(array(
            'xml_record_class' => 'XML_Record_PubMed',
            'xml_record_file_class' => 'XML_Record_File_PubMed',
            'input_file_set' => $this->dupes_file_set,
            'output_file_set' => $this->deduped_file_set,
            'internal_id_types' => array('doi'),
            'external_id_sets' => array('pubmed' => array($this->test_id_set)),
        ));
        $this->assertIsA( $xrd, 'XML_Record_Deduplicator' );
        $this->xrd = $xrd;
    }

    public function test_new_missing_params()
    {
        $this->expectException();
        // TODO: Add tests for missing *_file_set params.
        $xrd = new XML_Record_Deduplicator(array(
            'input_file_set' => $this->dupes_file_set,
            'output_file_set' => $this->deduped_file_set,
        ));
    }

    public function test_deduplicate()
    {
        $xrd = $this->xrd;
        $xrd->deduplicate();
        $duplicates = $xrd->duplicates();
        //echo "duplicates = "; var_dump( $duplicates );
        $expected_duplicates = array(
            array('19226734' => array("Duplicate 'pubmed' identifier '19226734' in identifier set 'TestIdentifierSet'.")),
            array('19213677' => array("Duplicate 'doi' identifier '10.1056/NEJMp0808003' in records under de-duplication.")),
            array('19229165' => array("Duplicate 'pubmed' identifier '19229165' in records under de-duplication.")),
            array('19182850' => array("Duplicate 'pubmed' identifier '19182850' in records under de-duplication.")),
            array('19182850' => array("Duplicate 'pubmed' identifier '19182850' in records under de-duplication.")),
        );

        $this->assertEqual( $duplicates, $expected_duplicates );
        $this->assertEqual( $xrd->count_duplicates(), 5 );
        $this->assertEqual( $xrd->count_unique(), 14 );

        // Test that deduped originals have been compressed:
        $f = new File_Find_Rule();
        $gz_file_names = $f->name('*.gz')->in( $this->dupes_directory );
        $this->assertEqual( count($gz_file_names), $this->dupes_file_count );

        // TODO: This fails! However, print_r shows that only the .gz files
        // are being found by File_Find_Rule. Broken!!!!
        // SOLVED: We need a new File_Find_Rule object for each search.
        $f = new File_Find_Rule();
        $xml_file_names = $f->name('*.xml')->in( $this->dupes_directory );
        $this->assertEqual( count($xml_file_names), 0 );

        // Uncompress the original files to clean up for subsequent test runs:
        foreach ($gz_file_names as $gz_file_name) {
            // open file for reading
            $file_contents = join('', gzfile($gz_file_name));
            
            preg_match('/^(.*)\.gz$/', $gz_file_name, $matches);
            $xml_file_name = $matches[1];

            $xml_file = fopen($xml_file_name, 'w');
            fwrite($xml_file, $file_contents);
            fclose($xml_file);
        
            unlink($gz_file_name);
        }
    }

    public function test_rededuplicate()
    {
        $xrd = new XML_Record_Deduplicator(array(
            'xml_record_class' => 'XML_Record_PubMed',
            'xml_record_file_class' => 'XML_Record_File_PubMed',
            'input_file_set' => $this->deduped_file_set,
            'output_file_set' => $this->rededuped_file_set,
            'internal_id_types' => array('doi'),
        ));
        $this->assertIsA( $xrd, 'XML_Record_Deduplicator' );
        $xrd->deduplicate();
        $this->assertEqual( $xrd->count_duplicates(), 0 );
        $this->assertEqual( $xrd->count_unique(), 14 );

        $this->cleanup();
    }

    protected function cleanup()
    {
        // Clean out any already-existing files, e.g. from previous test runs.
        $f = new File_Find_Rule();
        // TODO: Can't I give in() an array of directories?
        // Something in File_Find_Rule is broken...
        foreach (array($this->deduped_directory, $this->rededuped_directory) as $dir) {
            $files = $f->name('*.xml*')->in( $dir );
            foreach ($files as $file) {
                unlink( $file );
            }
        }
    }

} // end class XMLRecordDeduplicatorTest

class TestIdentifierSet
{
    protected $ids = array();

    public function ids()
    {
        return $this->ids;
    }

    public function has_member( $id )
    {
        return in_array($id, $this->ids) ? true : false;
    }

    // TODO: Allow only scalars, strings???
    public function add_member( $id )
    {
        if ($this->has_member( $id ))
        {
            throw new Exception("Attempt to add duplicate member '$id'.");
        }
        $this->ids[] = $id;
    }
} // end class TestIdentifierSet

