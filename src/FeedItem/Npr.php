<?php

namespace UmnLib\Core\XmlRecord\FeedItem;

class Npr extends \UmnLib\Core\XmlRecord\FeedItem
{
  // Must return array('type' => $type, 'value' => $value) pairs.
  public function ids()
  {
    if (!isset($this->ids))
    {
      $simplepieItem = $this->asSimplepieItem();
      $url = $simplepieItem->get_id();
      $url = preg_replace('/(\?|\&amp\;)ft=\d+\&amp\;f=\d+$/', '', $url);
      $this->ids = array(array(
        'type' => 'url',
        'value' => $url,
      ));
    }
    return $this->ids;
  }
}
