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
    /* TODO: Shouldn't need this anymore...
    $recordClass_filename =
      preg_replace('/_/', '/', $this->recordClass) . '.php';
    $include_path = get_include_path();
    require_once $recordClass_filename ;
     */

    if (!array_key_exists('recordFileClass', $params)) {
      throw new \InvalidArgumentException("Missing required param 'recordFileClass'");
    }
    $this->recordFileClass = $params['recordFileClass'];
    /* TODO: Shouldn't need this anymore...
    $recordFileClass_filename =
      preg_replace('/_/', '/', $this->recordFileClass) . '.php';
    require_once $recordFileClass_filename;
     */

    // This one is optional, because there's a default:
    if (array_key_exists('recordIteratorClass', $params)) {
      $this->recordIteratorClass = $params['recordIteratorClass'];
    }
    /* TODO: Shouldn't need this anymore...
    $recordIteratorClass_filename =
      preg_replace('/_/', '/', $this->recordIteratorClass) . '.php';
    require_once $recordIteratorClass_filename;
     */
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
