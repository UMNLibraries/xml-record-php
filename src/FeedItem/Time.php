<?php

namespace UmnLib\Core\XmlRecord\FeedItem;

class Time extends \UmnLib\Core\XmlRecord\FeedItem
{
  protected $stripHtmlTags = array('img',);

  // Must return array('type' => $type, 'value' => $value) pairs.
  public function ids()
  {
    if (!isset($this->ids))
    {
      $simplepieItem = $this->asSimplepieItem();
      $url = $simplepieItem->get_id();
      $url = preg_replace('/\?xid=rss-health/', '', $url);

      $this->ids = array(array(
        'type' => 'url',
        'value' => $url,
      ));
    }
    return $this->ids;
  }
}
