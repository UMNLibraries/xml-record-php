<?php

namespace UmnLib\Core\XmlRecord\File;

class Feed extends \UmnLib\Core\XmlRecord\File
{
  protected $recordElementName = 'item';
  public function recordElementName()
  {
    return $this->recordElementName;
  }
}
