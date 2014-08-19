<?php

namespace UmnLib\Core\XmlRecord\FeedItem;

class WaPost extends \UmnLib\Core\XmlRecord\FeedItem
{
  protected $stripHtmlTags = array('br','span','a','img','p');

  // Must return array('type' => $type, 'value' => $value) pairs.
  public function ids()
  {
    if (!isset($this->ids))
    {
      $simplepieItem = $this->asSimplepieItem();
      $url = preg_replace('/\?(nav|wprss)=rss_health/', '', $simplepieItem->get_id());
      $this->ids = array(array(
        'type' => 'url',
        'value' => $url,
      ));
    }
    return $this->ids;
  }
}
