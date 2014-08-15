<?php

require_once 'XML/Record/File/WorldCat.php';

class XML_Record_File_WorldCat_MARC extends XML_Record_File_WorldCat
{
    public function record_element_namespace()
    {
        return 'http://www.loc.gov/MARC21/slim';
    }
} // end class XML_Record_File_WorldCat_MARC
