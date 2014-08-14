<?php

require_once 'XML/Record/Iterator/Factory.php';

class XML_Record_Iterator_PubMedFactory extends XML_Record_Iterator_Factory
{
    function __construct()
    {
        parent::__construct(array(
            'record_class'   => 'XML_Record_PubMed',
            'record_file_class'   => 'XML_Record_File_PubMed',
        ));
    }
} // end class XML_Record_Iterator_PubMedFactory
