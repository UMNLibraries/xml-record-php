<?php
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
 * $record_element_name = $record->record_element_name();
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
 * @see record_element_name()
 *
 * @package XML_Record_File
 *
 * @author     David Naughton <nihiliad@gmail.com>
 * @copyright  2009 David Naughton <nihiliad@gmail.com>
 * @version    0.1.0
 */

require_once 'XML/Unserializer.php';

abstract class XML_Record_File
{
    protected $name;
    public function name()
    {
        return $this->name;
    }
    public function set_name( $name )
    {
        $this->name = $name;
    }

    protected $string;
    public function string()
    {
        // Lazy loading of file strings: only suck the whole string into
        // Memory if the user explicitly requests it:
        if (!isset($this->string)) {
            $this->set_string( file_get_contents($this->name()) );
        }
        return $this->string;
    }
    public function set_string( $string )
    {
        $this->string = $string;
    }

    protected $header;
    public function header()
    {
        if (!isset($this->header)) {
            $header_pattern = '/^(.*?)<' . $this->record_element_name() . '( |>)/s';
            preg_match($header_pattern, $this->string(), $matches);
            $this->header = $matches[1];
        }
        return $this->header;
    }

    protected $footer;
    public function footer()
    {
        if (!isset($this->footer)) {
            $footer_pattern = '/^.*<\/' . $this->record_element_name() . '>(.*)$/s';
            preg_match($footer_pattern, $this->string(), $matches);
            $this->footer = $matches[1];
        }
        return $this->footer;
    }

    public function __construct( $params )
    {
        if (array_key_exists('string', $params)) {
            $this->set_string( $params['string'] );
        } else if (array_key_exists('name', $params)) {
            // TODO: Check that the file exists?
            $this->set_name( $params['name'] );
            //$this->set_string( file_get_contents($this->name()) );
        } else {
            throw new Exception("A param of either 'string' or 'name' is required");
        }
    }

    // This should be static, but doesn't work well with PHP < 5.3.
    abstract function record_element_name();

    // Many files won't need this, so it should default to undef.
    protected $record_element_namespace;
    public function record_element_namespace()
    {
        return $this->record_element_namespace;
    }

} // end class XML_Record_File
