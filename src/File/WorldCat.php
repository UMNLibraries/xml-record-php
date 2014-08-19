<?php

namespace UmnLib\Core\XmlRecord\File;

class WorldCat extends \UmnLib\Core\XmlRecord\File
{
  protected $recordElementName = 'record';
  public function recordElementName()
  {
    return $this->recordElementName;
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
}
