#!/usr/bin/php -q
<?php

require_once 'simpletest/autorun.php';
SimpleTest :: prefer(new TextReporter());
set_include_path('../php' . PATH_SEPARATOR . get_include_path());
require_once 'XML/Record/Deduplicator.php';
require_once 'File/Find/Rule.php';
require_once 'File/Set/DateSequence.php';

ini_set('memory_limit', '2G');

//error_reporting( E_STRICT );

class XMLRecordDeduplicatorTest extends UnitTestCase
{
    public function __construct()
    {
        $this->dupes_directory = getcwd() . '/dupes_feeditem';
        $this->deduped_directory = getcwd() . '/deduped_feeditem';
        $this->rededuped_directory = getcwd() . '/rededuped_feeditem';

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
        // dupes files to the deduped directory, it should just got copied
        // back to the dupes directory, and the previously-written tests
        // should all still pass: 
        $dupe_base_name = basename( $xml_dupe_file_names[0] );
        copy(
            $xml_dupe_file_names[0],
            $this->deduped_directory . '/' . $dupe_base_name
        );
    }

    public function test_new()
    {
        $xrd = new XML_Record_Deduplicator(array(
            'xml_record_class' => 'XML_Record_FeedItem_NYT',
            'xml_record_file_class' => 'XML_Record_File_Feed',
            'input_file_set' => $this->dupes_file_set,
            'output_file_set' => $this->deduped_file_set,
        ));
        $this->assertIsA( $xrd, 'XML_Record_Deduplicator' );
        $this->xrd = $xrd;
    }

    public function test_deduplicate()
    {
        $xrd = $this->xrd;
        $xrd->deduplicate();
        $duplicates = $xrd->duplicates();
        //$this->assertEqual( $duplicates['19229165'], 1 );
        //$this->assertEqual( $duplicates['19182850'], 2 );
        $this->assertEqual( $xrd->count_duplicates(), 40 );
        $this->assertEqual( $xrd->count_unique(), 40 );

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
            'xml_record_class' => 'XML_Record_FeedItem_NYT',
            'xml_record_file_class' => 'XML_Record_File_Feed',
            'input_file_set' => $this->deduped_file_set,
            'output_file_set' => $this->rededuped_file_set,
        ));
        $this->assertIsA( $xrd, 'XML_Record_Deduplicator' );
        $xrd->deduplicate();
        $this->assertEqual( $xrd->count_duplicates(), 0 );
        $this->assertEqual( $xrd->count_unique(), 40 );

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
