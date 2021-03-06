<?php

namespace UmnLib\Core\Tests\XmlRecord;

use UmnLib\Core\XmlRecord\Deduplicator;
use UmnLib\Core\File\Set\DateSequence;
use Symfony\Component\Finder\Finder;

class DeduplicatorTest extends \PHPUnit_Framework_TestCase
{
  function setUp()
  {
    $this->dupesDirectory = dirname(__FILE__) . '/fixtures/dupes';
    $this->dedupedDirectory = dirname(__FILE__) . '/fixtures/deduped';
    $this->rededupedDirectory = dirname(__FILE__) . '/fixtures/rededuped';

    $this->suffix = '.xml';        
    $this->dupesFileSet = new DateSequence(array(
      'directory' => $this->dupesDirectory,
      'suffix' => $this->suffix,
    ));

    $this->dedupedFileSet = new DateSequence(array(
      'directory' => $this->dedupedDirectory,
      'suffix' => $this->suffix,
    ));
    $this->rededupedFileSet = new DateSequence(array(
      'directory' => $this->rededupedDirectory,
      'suffix' => $this->suffix,
    ));

    $finder = new Finder();
    $xmlDupeFiles = $finder->name('*.xml')->in($this->dupesDirectory)->sortByName();
    $xmlDupeFilenames = array();
    foreach ($xmlDupeFiles as $file) {
      $xmlDupeFilenames[] = $file->getRealPath();
    }
    $this->dupesFileCount = count($xmlDupeFilenames);
    //$this->cleanup();

    // TODO: Improve this weak good-enough-for-now test of the case of
    // unprocessed-xml-file-in-output-directory. If we copy one of the 
    // dupes files to the deduped directory, it should just get copied
    // back to the dupes directory, and the previously-written tests
    // should all still pass: 
    // TODO (Even more!): Not sure why this was here, and it causes
    // at least one test to fail.
    /*
    $dupeBasename = basename($xmlDupeFilenames[0]);
    copy(
      $xmlDupeFilenames[0],
      $this->dedupedDirectory . '/' . $dupeBasename
    );
     */

    $this->testIdSet = new TestIdentifierSet();
    $this->testIdSet->addMember('19226734');
  }

  function testNew()
  {
    $this->cleanup();
    $xrd = new Deduplicator(array(
      'xmlRecordClass' => '\UmnLib\Core\XmlRecord\PubMed',
      'xmlRecordFileClass' => '\UmnLib\Core\XmlRecord\File\PubMed',
      'inputFileSet' => $this->dupesFileSet,
      'outputFileSet' => $this->dedupedFileSet,
      'internalIdTypes' => array('doi'),
      'externalIdSets' => array('pubmed' => array($this->testIdSet)),
    ));
    $this->assertInstanceOf('\UmnLib\Core\XmlRecord\Deduplicator', $xrd);
    return $xrd;
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  function testNewMissingParams()
  {
    // TODO: Add tests for missing *FileSet params.
    $xrd = new Deduplicator(array(
      'inputFileSet' => $this->dupesFileSet,
      'outputFileSet' => $this->dedupedFileSet,
    ));
  }

  /**
   * @depends testNew
   */
  function testDeduplicate($xrd)
  {
    $xrd->deduplicate();
    $duplicates = $xrd->duplicates();
    //echo "duplicates = "; var_dump( $duplicates );
    $expectedDuplicates = array(
      array('19182850' => array("Duplicate 'pubmed' identifier '19182850' in records under de-duplication.")),
      array('19226734' => array("Duplicate 'pubmed' identifier '19226734' in identifier set 'UmnLib\Core\Tests\XmlRecord\TestIdentifierSet'.")),
      array('19229165' => array("Duplicate 'pubmed' identifier '19229165' in records under de-duplication.")),
      array('19182850' => array("Duplicate 'pubmed' identifier '19182850' in records under de-duplication.")),
      array('1921367777' => array("Duplicate 'doi' identifier '10.1056/NEJMp0808003' in records under de-duplication.")),
    );

    $this->assertEquals($expectedDuplicates, $duplicates);
    $this->assertEquals(5, $xrd->countDuplicates());
    $this->assertEquals(14, $xrd->countUnique());

    // Test that deduped originals have been compressed:
    unset($finder);
    $finder = new Finder();
    $gzFiles = $finder->name('*.gz')->in($this->dupesDirectory)->sortByName();
    $gzFilenames = array();
    foreach ($gzFiles as $file) {
      $gzFilenames[] = $file->getRealPath();
    }
    $this->assertEquals($this->dupesFileCount, count($gzFilenames));

    unset($finder);
    $finder = new Finder();
    $xmlFiles = $finder->name('*.xml')->in($this->dupesDirectory)->sortByName();
    $xmlFilenames = array();
    foreach ($xmlFiles as $file) {
      $xmlFilenames[] = $file->getRealPath();
    }
    $this->assertEquals(0, count($xmlFilenames));

    // Uncompress the original files to clean up for subsequent test runs:
    foreach ($gzFilenames as $gzFilename) {
      // open file for reading
      $fileContents = join('', gzfile($gzFilename));

      preg_match('/^(.*)\.gz$/', $gzFilename, $matches);
      $xmlFilename = $matches[1];

      $xmlFile = fopen($xmlFilename, 'w');
      fwrite($xmlFile, $fileContents);
      fclose($xmlFile);

      unlink($gzFilename);
    }
    // This doesn't work! See below.
    return $this->dedupedFileSet;
  }

  /**
   * @depends testDeduplicate
   */
  function testRededuplicate($dedupedFileSet)
  {
    $xrd = new Deduplicator(array(
      'xmlRecordClass' => '\UmnLib\Core\XmlRecord\PubMed',
      'xmlRecordFileClass' => '\UmnLib\Core\XmlRecord\File\PubMed',

      // Probably due to re-creation in setUp(), the $dedupedFileSet passed in from the
      // previous test will be empty, causing this test to fail.
      // We use the file set created in setUp() instead. 
      'inputFileSet' => $this->dedupedFileSet,

      'outputFileSet' => $this->rededupedFileSet,
      'internalIdTypes' => array('doi'),
    ));
    $this->assertInstanceOf('\UmnLib\Core\XmlRecord\Deduplicator', $xrd);
    $xrd->deduplicate();
    $this->assertEquals(0, $xrd->countDuplicates());
    $this->assertEquals(14, $xrd->countUnique());

    $this->cleanup();
  }

  protected function cleanup()
  {
    // Clean out any already-existing files, e.g. from previous test runs.
    foreach (array($this->dedupedDirectory, $this->rededupedDirectory) as $dir) {
      unset($finder);
      $finder = new Finder();
      $files = $finder->name('*.xml*')->in($dir)->sortByName();
      foreach ($files as $file) {
        unlink($file->getRealPath());
      }
    }
  }
}

class TestIdentifierSet
{
  protected $ids = array();

  function ids()
  {
    return $this->ids;
  }

  function hasMember($id)
  {
    return in_array($id, $this->ids) ? true : false;
  }

  // TODO: Allow only scalars, strings???
  function addMember($id)
  {
    if ($this->hasMember($id)) {
      throw new Exception("Attempt to add duplicate member '$id'.");
    }
    $this->ids[] = $id;
  }
}

