<?php

namespace UmnLib\Core\XmlRecord;

/**
 * An abstract base class to represent an XML file containing one or more records.
 *
 * Since this is an abstract base classs, it must be extended and cannot
 * be instantiated directly. But extending requires implementing only one
 * simple function: recordElementName()
 */

abstract class File
{
  protected $name;
  function name()
  {
    return $this->name;
  }

  function setName($name)
  {
    $this->name = $name;
  }

  protected $string;
  function string()
  {
    // Lazy loading of file strings: only suck the whole string into
    // Memory if the user explicitly requests it:
    if (!isset($this->string)) {
      $this->setString(file_get_contents($this->name()));
    }
    return $this->string;
  }
  function setString($string)
  {
    $this->string = $string;
  }

  protected $header;
  function header()
  {
    if (!isset($this->header)) {
      $headerPattern = '/^(.*?)<' . $this->recordElementName() . '( |>)/s';
      preg_match($headerPattern, $this->string(), $matches);
      $this->header = $matches[1];
    }
    return $this->header;
  }

  protected $footer;
  function footer()
  {
    if (!isset($this->footer)) {
      $footerPattern = '/^.*<\/' . $this->recordElementName() . '>(.*)$/s';
      preg_match($footerPattern, $this->string(), $matches);
      $this->footer = $matches[1];
    }
    return $this->footer;
  }

  function __construct($params)
  {
    if (array_key_exists('string', $params)) {
      $this->setString($params['string']);
    } else if (array_key_exists('name', $params)) {
      // TODO: Check that the file exists?
      $this->setName($params['name']);
    } else {
      throw new \InvalidArgumentException("A param of either 'string' or 'name' is required");
    }
  }

  // This should be static, but doesn't work well with PHP < 5.3.
  abstract function recordElementName();

  // Many files won't need this, so it should default to undef.
  protected $recordElementNamespace;
  function recordElementNamespace()
  {
    return $this->recordElementNamespace;
  }
}
