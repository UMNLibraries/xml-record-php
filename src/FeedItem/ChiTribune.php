<?php

namespace UmnLib\Core\XmlRecord\FeedItem;

class ChiTribune extends \UmnLib\Core\XmlRecord\FeedItem
{
  // Chicago Tribune seems to include only 'br' tags in the description,
  // but leaving the other tags here, anyway.
  protected $stripHtmlTags = array('br','a','img','p');

  // Must return array('type' => $type, 'value' => $value) pairs.
  public function ids()
  {
    if (!isset($this->ids))
    {
      $simplepieItem = $this->asSimplepieItem();
      $url = preg_replace('/\?track=rss/', '', $simplepieItem->get_id());
      $this->ids = array(array(
        'type' => 'url',
        'value' => $url,
      ));
    }
    return $this->ids;
  }
}
