<?php

require_once 'XML/Record/File.php';

class XML_Record_File_PubMed extends XML_Record_File
{
    protected $record_element_name = 'PubmedArticle';
    public function record_element_name()
    {
        return $this->record_element_name;
    }
} // end class XML_Record_File_PubMed
