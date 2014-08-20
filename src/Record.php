<?php

namespace UmnLib\Core\XmlRecord;

/**
 * An abstract base class to represent a single XML record among many.
 *
 * $record = new $xml_record_class(array(
 *     'domElement' => $domElement, // DOMElement object
 *     'file' => $file, // subclass of XML_Record_File
 * ));
 *
 * // PHP associative array, including the xml attributes:
 * $array = $record->asArray();
 *
 * // DOM element representations:
 * $domElement = $record->asDomElement();
 * $simpleXmlElement = $record->asSimpleXmlElement();
 * $fragmentString = $record->asFragmentString();
 *
 * // DOM document representations:
 * $domDocument = $record->asDomDocument();
 * $string = $record->asString();
 *
 * // A record may contain many unique identifiers. XML_Record
 * // requires that one unique identifier type be designated primary.
 * $ids = $record->ids();
 * $primaryId = $record->primaryId();
 * $primaryIdType = $record->primaryIdType();
 *
 * // Currently must be a subclass of XML_Record_File
 * $file = $record->file();
 *
 * Since this is an abstract base classs, it must be extended and cannot
 * be instantiated directly. But extending requires implementing only these 
 * simple functions: ids() and primaryIdType()
 */

abstract class Record
{
  protected $file;
  function file()
  {
    return $this->file;
  }
  // TODO: Should have an interface compatible with XmlRecord\File
  function setFile($file)
  {
    $this->file = $file;
  }

  protected $domDocument;
  protected $domElement;
  protected $simpleXmlElement;
  protected $string;
  protected $fragmentString;
  protected $array;
  protected $ids;
  protected $primaryId;

  function __construct($params)
  {
    // TODO: Add validation??

    if (!array_key_exists('file', $params)) {
      throw new \InvalidArgumentException("Missing required param 'file'");
    }
    $this->setFile($params['file']);

    // TODO: This violates the Dependency Inversion Principle,
    // but will have to do for now.
    $domElement = $params['domElement'];
    if (!is_a($domElement, 'DOMElement')) {
      throw new \InvalidArgumentException('domElement constructor param must be of type DOMElement');
    }

    $domDocument = new \DOMDocument();
    $recurse = true;
    $this->domElement = $domDocument->importNode($domElement, $recurse);
    $domDocument->appendChild($this->domElement);
    $this->domDocument = $domDocument;
  }

  function asDomElement()
  {
    return $this->domElement;
  }

  function asDomDocument()
  {
    // TODO: Should we use the asString method here? Probably not,
    // because asString() is now messing up other methods. Need to
    // rethink all these methods.
    return $this->domDocument;
  }

  function asString()
  {
    if (!isset($this->string)) {
      // TODO: Is this really what we want here? This change messed up
      // the asArray() method, maybe others.
      // Now using the original file's header and footer, instead of
      // the generic wrapper that domDocument gives us.
      //$this->string = $this->asDomDocument()->saveXML();
      $this->string = 
        $this->file()->header() .
        $this->asFragmentString() .
        $this->file()->footer();
    }
    return $this->string;
  }

  function asFragmentString()
  {
    if (!isset($this->fragmentString)) {
      $this->fragmentString = $this->asDomDocument()->saveXML($this->asDomElement());
    }
    return $this->fragmentString;
  }

  function asArray()
  {
    if (!isset($this->array)) {
      // TODO: Investigate: do we really need to serialize it again
      // just to unserialize it?
      // Serialize the data structure
      $string = $this->asDomDocument()->saveXML();
      $this->array = \Titon\Utility\Converter::toArray($string);
    }
    return $this->array;
  }

  function asSimpleXmlElement()
  {
    if (!isset($this->simnpleXmlElement)) {
      $this->simpleXmlElement = simplexml_import_dom($this->domElement());
    }
    return $this->simpleXmlElement;
  }

  function primaryId()
  {
    if (!isset($this->primaryId)) {
      $primaryIdType = $this->primaryIdType();
      foreach ($this->ids() as $id) {
        if ($id['type'] == $primaryIdType) {
          $this->primaryId = $id['value'];
        }
      }
    }
    return $this->primaryId;
  }

  abstract static function primaryIdType();
  abstract function ids();
}
