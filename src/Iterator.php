<?php

namespace UmnLib\Core\XmlRecord;

class Iterator implements \Iterator
{
  protected $file;
  public function file()
  {
    return $this->file;
  }
  // TODO: Should have an interface compatible with XmlRecordFile
  public function setFile( $file )
  {
    $this->file = $file;
  }

  protected $reader;
  public function reader()
  {
    if (!isset($this->reader)) {
      $this->reader = new \XMLReader();

      // In case XMLReader can read a file without sucking the whole
      // thing into memory, prefer using a filename instead of the
      // the whole file as a string:
      if (null != $this->file()->name()) {
        $this->reader->open($this->file()->name());
      } else {
        $this->reader->XML($this->file()->string());
      }
    }
    return $this->reader;
  }

  // TODO: Must either inherit from XmlRecord, or implement
  // an identical interface.
  protected $xmlRecordClass;
  protected function setXmlRecordClass($xmlRecordClass)
  {
    $this->xmlRecordClass = $xmlRecordClass;
  }

  protected $bootstrapped = false;
  protected $valid = true; // This is also a bootstrap...
  protected $current;
  protected $currentKey;

  function __construct($params)
  {
    if (!array_key_exists('file', $params)) {
      throw new \InvalidArgumentException("Missing required param 'file'");
    }
    $this->setFile($params['file']);

    if (!array_key_exists('xmlRecordClass', $params)) {
      throw new \InvalidArgumentException("Missing required param 'xmlRecordClass'");
    }
    $this->setXmlRecordClass($params['xmlRecordClass']);
  }

  function rewind()
  {
    $this->valid = true;
    $this->bootstrapped = false;
  }

  function current()
  {
    // Given Iterator's goofy method call order,
    // in which it calls current() before next(),
    // we have to bootstrap current so that it 
    // will have a value for the first loop iteration.
    if (!$this->bootstrapped) {
      $this->bootstrapped = true;
      $this->nextRecord();

    }
    return $this->current;
  }

  protected function setKey( $key )
  {
    $this->currentKey = $key;
  }

  function key()
  {
    return $this->currentKey;
  }

  function next()
  {
    $this->nextRecord();
  }

  function nextRecord()
  {
    $recordElementName = $this->file()->recordElementName();
    $recordElementNamespace = $this->file()->recordElementNamespace();

    $reader = $this->reader();
    while ($reader->read()) {

      // TODO: Somewhere in here, maybe multiple places, we allow
      // for the value of $this->current to be something other than
      // and XmlRecord!!!

      if ($reader->name != $recordElementName || $reader->nodeType == \XMLReader::END_ELEMENT) {
          continue;
      }

      if (isset($recordElementNamespace) && $reader->namespaceURI != $recordElementNamespace) {
          continue;
        }

      $domElement = $reader->expand();
      $current = new $this->xmlRecordClass(array(
        'domElement' => $domElement,
        'file' => $this->file(),
      ));
      $this->current = $current;
      $this->setKey($current->primaryId());

      return true;
    }
    unset($this->current);
    // Only set valid to false when there are no
    // more records to read:
    $this->valid = false;
  }

  function valid()
  {
    return $this->valid;
  }
}
