<?php

require_once 'XML/Record/FeedItem.php';

class XML_Record_FeedItem_LAT extends XML_Record_FeedItem
{
    protected $strip_html_tags = array('br','a','img','p');

    // Must return array( 'type' => $type, 'value' => $value ) pairs.
    public function ids()
    {
        if (!isset( $this->ids ))
        {
            $simplepie_item = $this->as_simplepie_item();
            $orig_link = $simplepie_item->get_item_tags(
                'http://rssnamespace.org/feedburner/ext/1.0',
                'origLink'
            );
            $url = $orig_link[0]['data'];
            $url = preg_replace('/\?track=rss/', '', $url);

            $this->ids = array(array(
                'type' => 'url',
                'value' => $url,
            ));
        }
        return $this->ids;
    }

} // end class XML_Record_FeedItem_LAT
