<?php
// $Id$

/**
 * @file
 * An abstract base class to represent a single XML record among many.
 *
 * <h2>Synopsis</h2>
 *
 * @code
 *
 * require_once $path_to_xml_record_class;
 *
 * $record = new $xml_record_class(array(
 *     'dom_element' => $dom_element, // DOMElement object
 *     'file' => $file, // subclass of XML_Record_File
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
 * // Currently must be a subclass of XML_Record_File
 * $file = $record->file();
 *
 * @endcode
 *
 * <h1>Extending</h1>
 *
 * Since this is an abstract base classs, it must be extended and cannot
 * be instantiated directly. But extending requires implementing only 3
 * simple functions:
 * @see ids()
 * @see primary_id_type()
 * @see root_element_name()
 *
 * @package XML_Record
 *
 * @author     David Naughton <nihiliad@gmail.com>
 * @copyright  2009 David Naughton <nihiliad@gmail.com>
 * @version    0.1.0
 */

require_once 'XML/Unserializer.php';

abstract class XML_Record
{
    protected $file;
    public function file()
    {
        return $this->file;
    }
    // TODO: Should have an interface compatible with XML_Record_File
    public function set_file( $file )
    {
        $this->file = $file;
    }

    protected $dom_document;
    protected $dom_element;
    protected $simplexml_element;
    protected $string;
    protected $fragment_string;
    protected $array;
    protected $ids;
    protected $primary_id;

    public function __construct( $params )
    {
        // TODO: Add validation??

        if (!array_key_exists('file', $params)) {
            throw new Exception("Missing required param 'file'");
        }
        $this->set_file( $params['file'] );
        
        // TODO: This violates the Dependency Inversion Principle,
        // but will have to do for now.
        $dom_element = $params['dom_element'];
        if (!is_a($dom_element, 'DOMElement')) {
            throw new Exception('dom_element constructor param must be of type DOMElement');
        }

        $dom_document = new DOMDocument();
        $recurse = true;
        $this->dom_element = $dom_document->importNode($dom_element, $recurse);
        $dom_document->appendChild( $this->dom_element );
        $this->dom_document = $dom_document;
    }

    public function as_dom_element()
    {
        return $this->dom_element;
    }

    public function as_dom_document()
    {
        // TODO: Should we use the as_string method here? Probably not,
        // because as_string() is now messing up other methods. Need to
        // rethink all these methods.
        return $this->dom_document;
    }

    public function as_string()
    {
        if (!isset( $this->string )) {
            // TODO: Is this really what we want here? This change messed up
            // the as_array() method, maybe others.
            // Now using the original file's header and footer, instead of
            // the generic wrapper that dom_document gives us.
            //$this->string = $this->as_dom_document()->saveXML();
            $this->string = 
                $this->file()->header() .
                $this->as_fragment_string() .
                $this->file()->footer();
        }
        return $this->string;
    }

    public function as_fragment_string()
    {
        if (!isset( $this->fragment_string )) {
            $this->fragment_string =
                $this->as_dom_document()->saveXML( $this->as_dom_element() );
        }
        return $this->fragment_string;
    }

    public function as_array()
    {
        if (!isset( $this->array )) {
            $u = &new XML_Unserializer(array('parseAttributes' => true));
            // TODO: Investigate: do we really need to serialize it again
            // just to unserialize it?
            // Serialize the data structure
            $string = $this->as_dom_document()->saveXML();
            $status = $u->unserialize( $string );
            if (PEAR::isError($status)) {
               throw new Exception( $status->getMessage() );
            }
            $this->array = $u->getUnserializedData();
        }
        return $this->array;
    }

    public function as_simplexml_element()
    {
        if (!isset( $this->simnplexml_element )) {
            $this->simplexml_element =
                simplexml_import_dom( $this->dom_element() );
        }
        return $this->simplexml_element;
    }

    public function primary_id()
    {
        if (!isset( $this->primary_id )) {
            $primary_id_type = $this->primary_id_type();
            foreach ($this->ids() as $id) {
                if ($id['type'] == $primary_id_type) {
                    $this->primary_id = $id['value'];
                }
            }
        }
        return $this->primary_id;
    }

    abstract static function primary_id_type();
    abstract function ids();

} // end class XML_Record
