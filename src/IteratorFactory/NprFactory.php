<?php

namespace UmnLib\Core\XmlRecord\IteratorFactory;

class NprFactory extends \UmnLib\Core\XmlRecord\IteratorFactory
{
  function __construct()
  {
    parent::__construct(array(
      'recordClass'     => '\UmnLib\Core\XmlRecord\FeedItem\Npr',
      'recordFileClass' => '\UmnLib\Core\XmlRecord\File\Feed',
    ));
  }
}
