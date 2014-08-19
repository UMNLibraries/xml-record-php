<?php

namespace UmnLib\Core\XmlRecord\FeedItem;

class Wired extends \UmnLib\Core\XmlRecord\FeedItem
{
  protected $stripHtmlTags = array('br','span','a','img');

  // Must return array('type' => $type, 'value' => $value) pairs.
  public function ids()
  {
    if (!isset($this->ids))
    {
      $simplepieItem = $this->as_simplepieItem();
      $this->ids = array(array(
        'type' => 'url',
        'value' => $simplepieItem->get_id(),
      ));
    }
    return $this->ids;
  }
}
