<?php

namespace UmnLib\Core\XmlRecord\IteratorFactory;

class PubMedFactory extends \UmnLib\Core\XmlRecord\IteratorFactory
{
  function __construct()
  {
    parent::__construct(array(
      'recordClass'     => '\UmnLib\Core\XmlRecord\PubMed',
      'recordFileClass' => '\UmnLib\Core\XmlRecord\File\PubMed',
    ));
  }
}
