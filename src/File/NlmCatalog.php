<?php

namespace UmnLib\Core\XmlRecord\File;

class NlmCatalog extends \UmnLib\Core\XmlRecord\File
{
  protected $recordElementName = 'NLMCatalogRecord';
  public function recordElementName()
  {
    return $this->recordElementName;
  }
}
