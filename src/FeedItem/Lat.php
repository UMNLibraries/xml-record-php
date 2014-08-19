<?php

namespace UmnLib\Core\XmlRecord\FeedItem;

class Lat extends \UmnLib\Core\XmlRecord\FeedItem
{
  protected $stripHtmlTags = array('br','a','img','p');

  // Must return array('type' => $type, 'value' => $value) pairs.
  public function ids()
  {
    if (!isset($this->ids))
    {
      $simplepieItem = $this->asSimplepieItem();
      $origLink = $simplepieItem->get_item_tags(
        'http://rssnamespace.org/feedburner/ext/1.0',
        'origLink'
      );
      $url = $origLink[0]['data'];
      $url = preg_replace('/\?track=rss/', '', $url);

      $this->ids = array(array(
        'type' => 'url',
        'value' => $url,
      ));
    }
    return $this->ids;
  }
}
