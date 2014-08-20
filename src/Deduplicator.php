<?php

namespace UmnLib\Core\XmlRecord;

class Deduplicator
{
  protected $externalIdSets;
  function externalIdSets()
  {
    return $this->externalIdSets;
  }

  // For de-duplicating the records against themselves. Automatically created
  // based on the idTypes argument (see below).
  protected $internalIdSets;
  function internalIdSets()
  {
    return $this->internalIdSets;
  }

  // Array of id types to be used for de-duplicating records against themselves.
  protected $internalIdTypes;
  function internalIdTypes()
  {
    return $this->internalIdTypes;
  }

  // TODO: Must either inherit from XmlRecord\Record, or implement
  // an identical interface.
  protected $xmlRecordClass;

  // TODO: Must either inherit from XmlRecord\File, or implement
  // an identical interface.
  protected $xmlRecordFileClass;

  protected $iterator;

  // Count of total number of duplicate (skipped) and
  // unique (kept) records. Each duplicated record will
  // be counted once as unique, because we keep the first
  // instance. All subsequent instances of a record will
  // be counted as duplicates. Therefore, the sum of these
  // two counts equals the total number of records.
  protected $countDuplicates = 0;
  function countDuplicates()
  {
    return $this->countDuplicates;
  }

  protected $countUnique = 0;
  function countUnique()
  {
    return $this->countUnique;
  }

  protected $inputFileSet;
  function inputFileSet()
  {
    return $this->inputFileSet;
  }

  protected $outputFileSet;
  function outputFileSet()
  {
    return $this->outputFileSet;
  }

  protected $duplicates = array();
  function duplicates()
  {
    return $this->duplicates;
  }

  function __construct( $params )
  {
    // TODO: Verify capabilities of file sets?
    if (!array_key_exists('inputFileSet', $params)) {
      throw new \InvalidArgumentException("Missing required param 'inputFileSet'");
    }
    $this->inputFileSet = $params['inputFileSet'];

    if (!array_key_exists('outputFileSet', $params)) {
      throw new \InvalidArgumentException("Missing required param 'outputFileSet'");
    }
    $this->outputFileSet = $params['outputFileSet'];

    if (!array_key_exists('xmlRecordClass', $params)) {
      throw new \InvalidArgumentException("Missing required param 'xmlRecordClass'");
    }
    $this->xmlRecordClass = $params['xmlRecordClass'];

    if (!array_key_exists('xmlRecordFileClass', $params)) {
      throw new \InvalidArgumentException("Missing required param 'xmlRecordFileClass'");
    }
    $this->xmlRecordFileClass = $params['xmlRecordFileClass'];

    // TODO: Document that this array should have idType keys
    //  and IdentifierSet values. Validate!!
    $externalIdSets = array();
    if (array_key_exists('externalIdSets', $params) && is_array($params['externalIdSets'])) {
      $externalIdSets = $params['externalIdSets'];
      foreach (array_keys($externalIdSets) as $idType) {
        if (!is_array($externalIdSets[$idType])) {
          $externalIdSets[$idType] = array($externalIdSets[$idType]);
        }
      }
    }
    $this->externalIdSets = $externalIdSets;

    $internalIdSets = array();
    $internalIdTypes = array();
    if (array_key_exists('internalIdTypes', $params) && is_array($params['internalIdTypes'])) {
      $internalIdTypes = $params['internalIdTypes'];
    }

    // Automatically create an id set to deduplicate
    // the primary id's of the xml records themselves:
    $primaryIdType = call_user_func(array(
      $this->xmlRecordClass,
      'primaryIdType',
    ));
    if (!in_array($primaryIdType, $internalIdTypes)) {
      $internalIdTypes[] = $primaryIdType;
    }

    // TODO: We should need only one id set per internal id type, but
    // use arrays anyway, in case we change our minds.
    foreach ($internalIdTypes as $idType) {
      $internalIdSets[$idType] = array(new IdentifierSet());
    }

    $this->internalIdTypes = $internalIdTypes;
    $this->internalIdSets = $internalIdSets;
  }

  function compressDedupedFile($filename)
  {
    $zipFilename = "$filename.gz";
    $zipFileContents = file_get_contents($filename);
    $zipFile = gzopen($zipFilename, "w9");
    gzwrite($zipFile, $zipFileContents);
    gzclose($zipFile);
    unlink($filename);
  }

  function deduplicate()
  {
    foreach ($this->inputFileSet->members() as $inputFilename) {

      $inputFile = new $this->xmlRecordFileClass(array(
        'name' => $inputFilename,
      ));

      $iterator = new Iterator(array(
        'xmlRecordClass' => $this->xmlRecordClass,
        'file' => $inputFile,
      ));

      list($outputFile, $outputFilename) =
        $this->openOutputFile($inputFilename, $inputFile->header());

      $countUnique = 0;
      $iterator->rewind();
      while ($iterator->valid()) {
        $record = $iterator->current();
        $duplicateProperties = $this->getDuplicateProperties($record);
        if (count($duplicateProperties) > 0) {
          $this->skip($record, $duplicateProperties);
          $this->countDuplicates++;
        } else {
          // TODO: Check to see if we can get the line number from
          // the XML Reader...
          $this->keep($outputFile, $record);
          $countUnique++;
        }
        $iterator->next();
      }
      $this->closeOutputFile($outputFile, $inputFile->footer());
      if ($countUnique == 0) {
        $this->outputFileSet->delete($outputFilename);
      }
      $this->compressDedupedFile($inputFilename);
      $this->countUnique += $countUnique;
    }
  }

  function getDuplicateProperties($record)
  {
    $duplicateProperties = array();

    // TODO: Add checks for heuristically-identifying properties.

    $ids = $record->ids();
    foreach ($ids as $id)
    {
      $idType = $id['type'];
      $idValue = $id['value'];

      // TODO: Create an interface for the idSets!

      $internalIdSets = $this->getInternalIdSets($idType);
      foreach ($internalIdSets as $internalIdSet) {
        if ($internalIdSet->hasMember($idValue)) {
          $duplicateProperties[] = "Duplicate '$idType' identifier '$idValue' in records under de-duplication.";
        } else {
          // Record that we've seen this id, so that we can de-duplicate other records against it:
          $internalIdSet->addMember($idValue);
        }
      }

      $externalIdSets = $this->getExternalIdSets($id['type']);
      foreach ($externalIdSets as $externalIdSet) {
        $externalIdSetClass = get_class($externalIdSet);
        if ($externalIdSet->hasMember($idValue)) {
          $duplicateProperties[] = "Duplicate '$idType' identifier '$idValue' in identifier set '$externalIdSetClass'.";
        }
        // Note: We don't add identifiers to external sets, because we're probalby de-duplicating in order to decide
        // whether or not to add records to those external sets.
      }
    }   
    return $duplicateProperties;
  }

  function getExternalIdSets($idType)
  {
    $externalIdSets = $this->externalIdSets();
    return array_key_exists($idType, $externalIdSets) ? $externalIdSets[$idType] : array();
  }

  function getInternalIdSets($idType)
  {
    $internalIdSets = $this->internalIdSets();
    return array_key_exists($idType, $internalIdSets) ? $internalIdSets[$idType] : array();
  }

  // TODO: Record which id('s) was found to be a duplicate??
  // Right now, this only records the primary id of each record, which
  // won't necessarily be the id that was found to be a duplicate.
  function skip($record, $duplicateProperties)
  {
    $primaryId = $record->primaryId();
    $this->duplicates[] = array($primaryId => $duplicateProperties);
  }

  function keep($outputFile, $record)
  {
    fwrite($outputFile, $record->asFragmentString());
  }

  function openOutputFile($inputFilename, $inputFileHeader)
  {
    $basename = basename($inputFilename);
    $outputFilename = $this->outputFileSet->add($basename);

    // Open output files in overwrite mode to help protect against duplicates:
    $outputFile = fopen($outputFilename, 'w')
      or die("Cannot open file '$outputFilename'");

    fwrite($outputFile, $inputFileHeader);
    return array($outputFile, $outputFilename);
  }

  function closeOutputFile($outputFile, $inputFileFooter)
  {
    fwrite($outputFile, $inputFileFooter);
    fclose($outputFile);
  }
}
