<?php

namespace UmnLib\Core\XmlRecord;

// $Id$

/**
 * @file
 * An abstract base class to represent an XML file containing one or more records.
 *
 * <h2>Synopsis</h2>
 *
 * @code
 *
 * // TODO: Adapt this for XML_Record_File!!!
 * require_once $path_to_xml_record_class;
 *
 * $record = new $xml_record_class(array(
 *     'dom_element' => $dom_element, // DOMElement object
 *     'file_name' => $file_name, // optional
 * ));
 *
 * // PHP associative array, including the xml attributes:
 * $array = $record->as_array();
 *
 * // DOM element representations:
 * $dom_element = $record->as_dom_element();
 * $simplexml_element = $record->as_simplexml_element();
 * $fragment_string = $record->as_fragment_string();
 *
 * // DOM document representations:
 * $dom_document = $record->as_dom_document();
 * $string = $record->as_string();
 *
 * // A record may contain many unique identifiers. XML_Record
 * // requires that one unique identifier type be designated primary.
 * $ids = $record->ids();
 * $primary_id = $record->primary_id();
 * $primary_id_type = $record->primary_id_type();
 *
 * $recordElementName = $record->recordElementName();
 *
 * // optional
 * $file_name = $record->file_name();
 *
 * @endcode
 *
 * <h1>Extending</h1>
 *
 * Since this is an abstract base classs, it must be extended and cannot
 * be instantiated directly. But extending requires implementing only one
 * simple functions:
 * @see recordElementName()
 *
 * @package XML_Record_File
 *
 * @author     David Naughton <nihiliad@gmail.com>
 * @copyright  2009 David Naughton <nihiliad@gmail.com>
 * @version    0.1.0
 */

abstract class File
{
  protected $name;
  function name()
  {
    return $this->name;
  }
  function setName( $name )
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
      //$this->setString( file_get_contents($this->name()) );
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
