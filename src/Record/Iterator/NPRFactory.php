<?php

require_once 'XML/Record/Iterator/Factory.php';

class XML_Record_Iterator_NPRFactory extends XML_Record_Iterator_Factory
{
    function __construct()
    {
        parent::__construct(array(
            'record_class'   => 'XML_Record_FeedItem_NPR',
            'record_file_class'   => 'XML_Record_File_Feed',
        ));
    }
} // end class XML_Record_Iterator_NPRFactory
