<?php

require_once 'XML/Record/File.php';

class XML_Record_File_Feed extends XML_Record_File
{
    protected $record_element_name = 'item';
    public function record_element_name()
    {
        return $this->record_element_name;
    }
} // end class XML_Record_File_Feed