<?php

namespace UmnLib\Core\XmlRecord;

class IteratorFactory
{
  // TODO: All of these attributes should implement common interfaces. Validate!!!
  protected $recordClass;
  protected $recordFileClass;
  protected $recordIteratorClass = '\UmnLib\Core\XmlRecord\Iterator';

  function __construct($params)
  {
    if (!array_key_exists('recordClass', $params)) {
      throw new \InvalidArgumentException("Missing required param 'recordClass'");
    }
    $this->recordClass = $params['recordClass'];

    if (!array_key_exists('recordFileClass', $params)) {
      throw new \InvalidArgumentException("Missing required param 'recordFileClass'");
    }
    $this->recordFileClass = $params['recordFileClass'];

    // This one is optional, because there's a default:
    if (array_key_exists('recordIteratorClass', $params)) {
      $this->recordIteratorClass = $params['recordIteratorClass'];
    }
  }

  public function create($filename) {
    $file = new $this->recordFileClass(array(
      'name' => $filename,
    ));

    $iterator = new $this->recordIteratorClass(array(
      'file' => $file,
      // TODO: Change this constructor param to be more generic!!!
      'xmlRecordClass' => $this->recordClass,
    ));

    return $iterator;
  }
}
