<?php

//require_once 'XML/Unserializer.php';

class XML_Record_Iterator implements Iterator
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

    protected $reader;
    public function reader()
    {
        if (!isset($this->reader)) {
            $this->reader = new XMLReader();

            // In case XMLReader can read a file without sucking the whole
            // thing into memory, prefer using a filename instead of the
            // the whole file as a string:
            if (null != $this->file()->name()) {
                $this->reader->open( $this->file()->name() );
            } else {
                $this->reader->XML( $this->file()->string() );
            }
        }
        return $this->reader;
    }

    // TODO: Must either inherit from XML_Record, or implement
    // an identical interface.
    protected $xml_record_class;
    protected function set_xml_record_class( $xml_record_class )
    {
        $xml_record_class_file_name = 
            preg_replace('/_/', '/', $xml_record_class) . '.php';
        require_once $xml_record_class_file_name;
        $this->xml_record_class = $xml_record_class;
    }

    protected $bootstrapped = false;
    protected $valid = true; // This is also a bootstrap...
    protected $current;
    protected $current_key;
    
    function __construct( $params )
    {
        if (!array_key_exists('file', $params)) {
            throw new Exception("Missing required param 'file'");
        }
        $this->set_file( $params['file'] );
        
        if (!array_key_exists('xml_record_class', $params)) {
            throw new Exception("Missing required param 'xml_record_class'");
        }
        $this->set_xml_record_class( $params['xml_record_class'] );
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
            $this->next_record();
            
        }
        return $this->current;
    }

    protected function set_key( $key )
    {
        $this->current_key = $key;
    }

    function key()
    {
        return $this->current_key;
    }

    function next()
    {
        $this->next_record();
    }
    
    function next_record()
    {
        $record_element_name = $this->file()->record_element_name();
        $record_element_namespace = $this->file()->record_element_namespace();

        $reader = $this->reader();
        while ($reader->read()) {

            // TODO: Somewhere in here, maybe multiple places, we allow
            // for the value of $this->current to be something other than
            // and XML_Record!!!
    
            if ($reader->name != $record_element_name ||
                $reader->nodeType == XMLReader::END_ELEMENT) {
                continue;
            }
                $namespaceURI = $reader->namespaceURI;
            if (isset($record_element_namespace) && 
                $reader->namespaceURI != $record_element_namespace) {
                continue;
            }
            
            $dom_element = $reader->expand();
            $current = new $this->xml_record_class(array(
                'dom_element' => $dom_element,
                'file' => $this->file(),
            ));
            $this->current = $current;
            $this->set_key( $current->primary_id() );

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
    
} // end class XML_Record_Iterator
