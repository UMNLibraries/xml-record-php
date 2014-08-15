#!/usr/bin/php -q
<?php

require_once 'simpletest/autorun.php';
SimpleTest :: prefer(new TextReporter());
set_include_path('../php' . PATH_SEPARATOR . get_include_path());
require_once 'XML/Record/Iterator.php';
require_once 'XML/Record/File/Feed.php';
require_once 'File/Find/Rule.php';

ini_set('memory_limit', '2G');

//error_reporting( E_STRICT );

class XMLRecordFeedItemTest extends UnitTestCase
{
    public function test_entity_references_in_lat()
    {
        // Los Angeles Times entity references tests:
        $directory = getcwd() . '/feeditem/entity_references/lat';
        $xml_record_class = 'XML_Record_FeedItem_LAT';
        $expected_output = array(
             'http://www.latimes.com/features/health/la-he-0412-the-md-20100412,0,5385285.story' => array(
                  'title' => 'Communication is key to every office visit',
                  // content contains an em-dash reference:
                  'content' => 'Sometimes physicians annoy us — and sometimes we annoy them. Both sides need to work harder.
                        
                        When it comes to doctors, people tend to be strongly opinionated. Some patients adore their physicians and feel they can do no wrong. Others complain about the doctors they see — for keeping them waiting, for poor bedside manner or because they doubt the physician\'s clinical prowess.',
              ),
              'http://www.latimes.com/features/health/la-he-skeptic-20100412,0,5348639.story' => array(
                  'title' => 'Evaluating homeopathic approaches to tinnitus',
                  // content contains multiple em-dash references:
                  'content' => 'Studies suggest that ginkgo biloba may offer some relief, but more widely, no evidence confirms reduction or elimination of constant ringing in the ears.
                        
                        For millions of people, the quietest room is never quiet enough. Even when surrounded by silence, they can hear a ringing or buzzing in their ears that drives them to distraction. The sound is called tinnitus, and sufferers — often people with hearing trouble thanks to advanced age or loud sounds — are willing to go to great lengths to stop the noise.',
              ),
              'http://www.latimes.com/features/health/la-he-unreal-20100412,0,6305200.column' => array(
                  // title contains references for fancy quotes and a fancy apostrophe:
                  'title' => '‘Grey’s Anatomy’ episode about suicide hits the mark', 
                  // content contains a reference for a fancy apostrophe:
                  'content' => 'Washington state’s Death With Dignity Act is fairly handled in television series installment dealing with physician-assisted suicide.
                        
                    "Grey\'s Anatomy"',
              ),
        );

        $this->run_entity_references_tests($directory, $xml_record_class, $expected_output);
    }

    public function test_entity_references_in_wapost()
    {
        // Los Angeles Times entity references tests:
        $directory = getcwd() . '/feeditem/entity_references/wapost';
        $xml_record_class = 'XML_Record_FeedItem_WaPost';
        $expected_output = array(
            'http://www.washingtonpost.com/wp-dyn/content/article/2010/05/28/AR2010052801861.html' => array(
                'title' => 'In "America and the Pill," Elaine Tyler May traces the pill\'s influence on women',
                'content' => 'Sanger is one of the heroes of "America and the Pill," a new cultural history of the birth control pill written by Elaine Tyler May, a professor of American studies and history at the University of Minnesota.




 
United States - Birth control - Health - Combined oral contraceptive pill - Elaine Tyler May',
            ),
        );

        $this->run_entity_references_tests($directory, $xml_record_class, $expected_output);
    }

    public function test_entity_references_in_nyt()
    {
        // Los Angeles Times entity references tests:
        $directory = getcwd() . '/feeditem/entity_references/nyt';
        $xml_record_class = 'XML_Record_FeedItem_NYT';
        $expected_output = array(
            'http://www.nytimes.com/2010/06/04/nyregion/04harlem.html' => array(
                'title' => '1,000 More Unread Heart Tests at Harlem Hospital',
                'creator' => 'By ANEMONA HARTOCOLLIS',
                'content' => 'New York hospital officials said the echocardiograms went back to 2005, not 2007, and totaled 1,000 more than previously thought.',
                'categories' => array(
                    'Harlem Hospital Center',
                    'Heart',
                    'Tests and Testing',
                    'Medicine and Health',
                    'Health and Hospitals Corp',
                    'Hospitals',
                    'Harlem (NYC)',
                    'Doctors',
                    'Deaths (Fatalities)',
                ),
            ),
        );

        $this->run_entity_references_tests($directory, $xml_record_class, $expected_output);
    }

    public function run_entity_references_tests($directory, $xml_record_class, $expected_output)
    {
        $f = new File_Find_Rule();
        $file_names = $f->name('*.xml')->in( $directory );

        // TODO: Assumes that each directory has only one file!!!
        $file = new XML_Record_File_Feed(array(
            'name' => $file_names[0],
        ));
        $xri = new XML_Record_Iterator(array(
            'file' => $file,
            'xml_record_class' => $xml_record_class,
        ));

        $xri->rewind();
        $records = array();

        while ($xri->valid()) {
            $record = $xri->current();
            $url = $record->primary_id();
            //echo "url = $url\n";
            $expected = $expected_output[$url];
            foreach ($expected as $property => $value) {
                //$record_value = $record->$property();
                //echo "$property => "; print_r( $record_value ); echo "\n";
                $this->assertEqual($record->$property(), $expected[$property]);
            }
            $xri->next();
        }
    }

} // end class XMLRecordFeedItemTest
