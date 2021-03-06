<?php

namespace UmnLib\Core\XmlRecord\FeedItem;

class Nyt extends \UmnLib\Core\XmlRecord\FeedItem
{
  protected $stripHtmlTags = array('br','span','a','img');

  // Must return array('type' => $type, 'value' => $value) pairs.
  public function ids()
  {
    if (!isset($this->ids))
    {
      $simplepieItem = $this->asSimplepieItem();
      $this->ids = array(array(
        'type' => 'url',
        'value' => $simplepieItem->get_id(),
      ));
    }
    return $this->ids;
  }
}
