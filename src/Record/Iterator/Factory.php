<?php

class XML_Record_Iterator_Factory
{
    // TODO: All of these attributes should implement common interfaces. Validate!!!
    protected $record_class;
    protected $record_file_class;
    protected $record_iterator_class = 'XML_Record_Iterator';

    function __construct( $params )
    {
        if (!array_key_exists('record_class', $params)) {
            throw new Exception("Missing required param 'record_class'");
        }
        $this->record_class = $params['record_class'];
        $record_class_file_name =
            preg_replace('/_/', '/', $this->record_class) . '.php';
        $include_path = get_include_path();
        require_once $record_class_file_name ;

        if (!array_key_exists('record_file_class', $params)) {
            throw new Exception("Missing required param 'record_file_class'");
        }
        $this->record_file_class = $params['record_file_class'];
        $record_file_class_file_name =
            preg_replace('/_/', '/', $this->record_file_class) . '.php';
        require_once $record_file_class_file_name;

        // This one is optional, because there's a default:
        if (array_key_exists('record_iterator_class', $params)) {
            $this->record_iterator_class = $params['record_iterator_class'];
        }
        $record_iterator_class_file_name =
            preg_replace('/_/', '/', $this->record_iterator_class) . '.php';
        require_once $record_iterator_class_file_name;
    }

    public function create( $file_name ) {
        $file = new $this->record_file_class(array(
            'name' => $file_name,
        ));

        $iterator = new $this->record_iterator_class(array(
            'file' => $file,
            // TODO: Change this constructor param to be more generic!!!
            'xml_record_class' => $this->record_class,
        ));


        return $iterator;
    }

} // end class XML_Record_Iterator_Factory
