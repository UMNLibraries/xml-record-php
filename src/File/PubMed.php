<?php

namespace UmnLib\Core\XmlRecord\File;

class PubMed extends \UmnLib\Core\XmlRecord\File
{
  protected $recordElementName = 'PubmedArticle';
  public function recordElementName()
  {
    return $this->recordElementName;
  }
}
