<?php

require_once 'XML/Record/File.php';

class XML_Record_File_WorldCat extends XML_Record_File
{
    protected $record_element_name = 'record';
    public function record_element_name()
    {
        return $this->record_element_name;
    }

    // These headers and footers currently exist only for
    // deduplication. Since the WorldCat headers and footers contain 
    // file metadata like record counts that may not be 
    // accurate in a reconstructed, deduplicated file, 
    // we just use these simpler headers and footers
    // instead of the originals. 

    protected $header;
    public function header()
    {
        if (!isset($this->header)) {
            $this->header =<<<EOD
<?xml version="1.0"?>
<records>
EOD;
        }
        return $this->header;
    }

    protected $footer;
    public function footer()
    {
        if (!isset($this->footer)) {
            $this->footer = "\n" . '</records>' . "\n";
        }
        return $this->footer;
    }
} // end class XML_Record_File_WorldCat
