<?php

require_once 'XML/Record/FeedItem.php';

class XML_Record_FeedItem_ChiTribune extends XML_Record_FeedItem
{
    // Chicago Tribune seems to include only 'br' tags in the description,
    // but leaving the other tags here, anyway.
    protected $strip_html_tags = array('br','a','img','p');

    // Must return array( 'type' => $type, 'value' => $value ) pairs.
    public function ids()
    {
        if (!isset( $this->ids ))
        {
            $simplepie_item = $this->as_simplepie_item();
            $url = preg_replace('/\?track=rss/', '', $simplepie_item->get_id());
            $this->ids = array(array(
                'type' => 'url',
                'value' => $url,
            ));
        }
        return $this->ids;
    }

} // end class XML_Record_FeedItem_ChiTribune
