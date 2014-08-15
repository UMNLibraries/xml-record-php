<?php

require_once 'XML/Record/FeedItem.php';

class XML_Record_FeedItem_Time extends XML_Record_FeedItem
{
    protected $strip_html_tags = array('img',);

    // Must return array( 'type' => $type, 'value' => $value ) pairs.
    public function ids()
    {
        if (!isset( $this->ids ))
        {
            $simplepie_item = $this->as_simplepie_item();
            $url = $simplepie_item->get_id();
            $url = preg_replace('/\?xid=rss-health/', '', $url);

            $this->ids = array(array(
                'type' => 'url',
                'value' => $url,
            ));
        }
        return $this->ids;
    }

} // end class XML_Record_FeedItem_Time
