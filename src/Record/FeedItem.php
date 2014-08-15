<?php

require_once 'XML/Record.php';
require 'simplepie.inc';

abstract class XML_Record_FeedItem extends XML_Record
{
    // Must be an id type that uniquely identifies the record, 
    // usually the record-creating organization's id.
    public static function primary_id_type()
    {
        return 'url';
    }

    protected $strip_html_tags;
    public function strip_html_tags()
    {
        return $this->strip_html_tags;
    }
    protected function set_strip_html_tags(array $strip_html_tags)
    {
        $this->strip_html_tags = $strip_html_tags;
    }

    protected $simplepie_item;
    public function as_simplepie_item()
    {
        if (!isset($this->simplepie_item)) {
            // SimplePie can't take a string. Grrr...
            // Have to hard-code the directory, since sys_get_temp_dir() is >= 5.2.1. Damn Red Hat!
            $file_name = tempnam('/tmp', 'simplepie');
            file_put_contents($file_name, $this->as_string());
    
            $simplepie_file = new SimplePie_File( $file_name );
            $feed = new SimplePie();
            $feed->set_file( $simplepie_file );
    
            $feed->init();
            if ($feed->error()) {
                throw new Exception( $feed->error() );
            }
    
            // Useful for stripping ads, images, etc.
            $feed->strip_htmltags( $this->strip_html_tags() );
    
            $feed_items = $feed->get_items();
            // There should be only one item:
            $this->simplepie_item = $feed_items[0];

            // Clean up the temporary file created above:
            unlink( $file_name );
        }
        return $this->simplepie_item;
    }

    protected $title;
    public function title()
    {
        if (!isset($this->title)) {
            $simplepie_item = $this->as_simplepie_item();
            $title = $simplepie_item->get_title();
            // This should be fine, because internal encoding in PHP is always UTF-8:
            $this->title = html_entity_decode(trim($title), ENT_QUOTES, 'UTF-8');
        }
        return $this->title;
    }

    protected $content;
    public function content()
    {
        if (!isset($this->content)) {
            $simplepie_item = $this->as_simplepie_item();
            $content = $simplepie_item->get_content();
            // This should be fine, because internal encoding in PHP is always UTF-8:
            $this->content = html_entity_decode(trim($content), ENT_QUOTES, 'UTF-8');
        }
        return $this->content;
    }

    protected $description;
    public function description()
    {
        if (!isset($this->description)) {
            $simplepie_item = $this->as_simplepie_item();
            $description = $simplepie_item->get_description();
            // This should be fine, because internal encoding in PHP is always UTF-8:
            $this->description = html_entity_decode(trim($description), ENT_QUOTES, 'UTF-8');
        }
        return $this->description;
    }

    protected $creator;
    public function creator()
    {
        if (!isset($this->creator)) {
            $simplepie_item = $this->as_simplepie_item();
            $creator = $simplepie_item->get_item_tags(
                'http://purl.org/dc/elements/1.1/', // previously: http://dublincore.org/documents/dcmi-namespace/
                'creator'
            );
            // This should be fine, because internal encoding in PHP is always UTF-8:
            $this->creator = html_entity_decode(trim($creator[0]['data']), ENT_QUOTES, 'UTF-8');
        }
        return $this->creator;
    }

    protected $author;
    public function author()
    {
        if (!isset($this->author)) {
            $simplepie_item = $this->as_simplepie_item();
            $author = $simplepie_item->get_item_tags(
                'http://purl.org/dc/elements/1.1/', // previously: http://dublincore.org/documents/dcmi-namespace/
                'author'
            );
            // This should be fine, because internal encoding in PHP is always UTF-8:
            $this->author = html_entity_decode(trim($author[0]['data']), ENT_QUOTES, 'UTF-8');
        }
        return $this->author;
    }

    protected $categories;
    public function categories()
    {
        if (!isset($this->categories)) {
            $simplepie_item = $this->as_simplepie_item();
            $categories = array();
            foreach ($simplepie_item->get_categories() as $category) {
                // This should be fine, because internal encoding in PHP is always UTF-8:
                $categories[] = html_entity_decode(trim($category->get_label()), ENT_QUOTES, 'UTF-8');;
            }
            $this->categories = $categories;
        }
        return $this->categories;
    }

} // end class XML_Record_FeedItem
