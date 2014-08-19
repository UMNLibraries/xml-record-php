<?php

namespace UmnLib\Core\Tests\XmlRecord;

use UmnLib\Core\XmlRecord\Iterator;
use UmnLib\Core\XmlRecord\File\Feed;
use Symfony\Component\Finder\Finder;

class FeedItemTest extends \PHPUnit_Framework_TestCase
{
  protected function setUp()
  {
    $this->finder = new Finder();
  }

  function testEntityReferencesInLat()
  {
    // Los Angeles Times entity references tests:
    $directory = dirname(__FILE__) . '/fixtures/feeditem/entity-references/lat';
    $xmlRecordClass = 'UmnLib\Core\XmlRecord\FeedItem\Lat';
    $expectedOutput = array(
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
        'title' => "'Grey's Anatomy' episode about suicide hits the mark", 
        // content contains a reference for a fancy apostrophe:
        'content' => 'Washington state’s Death With Dignity Act is fairly handled in television series installment dealing with physician-assisted suicide.
                        
                    "Grey\'s Anatomy"',
      ),
    );

    $this->runEntityReferencesTests($directory, $xmlRecordClass, $expectedOutput);
  }

  function testEntityReferencesInWapost()
  {
    $directory = dirname(__FILE__) . '/fixtures/feeditem/entity-references/wapost';
    $xmlRecordClass = 'UmnLib\Core\XmlRecord\FeedItem\WaPost';
    $expectedOutput = array(
      'http://www.washingtonpost.com/wp-dyn/content/article/2010/05/28/AR2010052801861.html' => array(
        'title' => 'In "America and the Pill," Elaine Tyler May traces the pill\'s influence on women',
        'content' => 'Sanger is one of the heroes of "America and the Pill," a new cultural history of the birth control pill written by Elaine Tyler May, a professor of American studies and history at the University of Minnesota.

United States - Birth control - Health - Combined oral contraceptive pill - Elaine Tyler May',
      ),
    );

    $this->runEntityReferencesTests($directory, $xmlRecordClass, $expectedOutput);
  }

  function testEntityReferencesInNyt()
  {
    $directory = dirname(__FILE__) . '/fixtures/feeditem/entity-references/nyt';
    $xmlRecordClass = 'UmnLib\Core\XmlRecord\FeedItem\Nyt';
    $expectedOutput = array(
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

    $this->runEntityReferencesTests($directory, $xmlRecordClass, $expectedOutput);
  }

  function runEntityReferencesTests($directory, $xmlRecordClass, $expectedOutput)
  {
    $files = $this->finder->name('*.xml')->in($directory);
    $filenames = array();
    foreach ($files as $file) {
      $filenames[] = $file->getRealPath();
    }

    // TODO: Assumes that each directory has only one file!!!
    $file = new Feed(array(
      'name' => $filenames[0],
    ));
    $xri = new Iterator(array(
      'file' => $file,
      'xmlRecordClass' => $xmlRecordClass,
    ));

    $xri->rewind();
    $records = array();

    while ($xri->valid()) {
      $record = $xri->current();
      $url = $record->primaryId();
      //echo "url = $url\n";
      $expected = $expectedOutput[$url];
      foreach ($expected as $property => $value) {
        //$record_value = $record->$property();
        //echo "$property => "; print_r( $record_value ); echo "\n";
        $this->assertEquals($expected[$property], $record->$property());
      }
      $xri->next();
    }
  }
}
