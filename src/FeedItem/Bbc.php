<?php

namespace UmnLib\Core\XmlRecord\FeedItem;

class Bbc extends \UmnLib\Core\XmlRecord\FeedItem
{
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
