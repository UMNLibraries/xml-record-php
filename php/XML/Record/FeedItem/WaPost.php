<?php

require_once 'XML/Record/FeedItem.php';

class XML_Record_FeedItem_WaPost extends XML_Record_FeedItem
{
    protected $strip_html_tags = array('br','span','a','img','p');

    // Must return array( 'type' => $type, 'value' => $value ) pairs.
    public function ids()
    {
        if (!isset( $this->ids ))
        {
            $simplepie_item = $this->as_simplepie_item();
            $url = preg_replace('/\?(nav|wprss)=rss_health/', '', $simplepie_item->get_id());
            $this->ids = array(array(
                'type' => 'url',
                'value' => $url,
            ));
        }
        return $this->ids;
    }

} // end class XML_Record_FeedItem_WaPost
