<?php

require_once 'XML/Record/FeedItem.php';

class XML_Record_FeedItem_Wired extends XML_Record_FeedItem
{
    protected $strip_html_tags = array('br','span','a','img');

    // Must return array( 'type' => $type, 'value' => $value ) pairs.
    public function ids()
    {
        if (!isset( $this->ids ))
        {
            $simplepie_item = $this->as_simplepie_item();
            $this->ids = array(array(
                'type' => 'url',
                'value' => $simplepie_item->get_id(),
            ));
        }
        return $this->ids;
    }

} // end class XML_Record_FeedItem_Wired
