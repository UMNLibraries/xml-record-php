<?php

namespace UmnLib\Core\XmlRecord;

abstract class FeedItem extends Record
{
  // Must be an id type that uniquely identifies the record, 
  // usually the record-creating organization's id.
  public static function primaryIdType()
  {
    return 'url';
  }

  protected $stripHtmlTags;
  function stripHtmlTags()
  {
    return $this->stripHtmlTags;
  }
  protected function setStripHtmlTags(array $stripHtmlTags)
  {
    $this->stripHtmlTags = $stripHtmlTags;
  }

  protected $simplepieItem;
  function asSimplepieItem()
  {
    if (!isset($this->simplepieItem)) {
      // SimplePie can't take a string. Grrr...
      // Have to hard-code the directory, since sys_get_temp_dir() is >= PHP 5.2.1. Damn Red Hat!
      $filename = tempnam('/tmp', 'simplepie');
      file_put_contents($filename, $this->asString());

      $feed = new \SimplePie();
      $simplepieFile = new \SimplePie_File($filename);
      $feed->set_file($simplepieFile);

      $feed->init();
      if ($feed->error()) {
        throw new \RuntimeException($feed->error());
      }

      // Useful for stripping ads, images, etc.
      $feed->strip_htmltags( $this->stripHtmlTags() );

      $feedItems = $feed->get_items();
      // There should be only one item:
      $this->simplepieItem = $feedItems[0];

      // Clean up the temporary file created above:
      unlink($filename);
    }
    return $this->simplepieItem;
  }

  protected $title;
  function title()
  {
    if (!isset($this->title)) {
      $simplepieItem = $this->asSimplepieItem();
      $title = $simplepieItem->get_title();
      $this->title = $this->decodeEntities($title);
    }
    return $this->title;
  }

  protected $content;
  function content()
  {
    if (!isset($this->content)) {
      $simplepieItem = $this->asSimplepieItem();
      $content = $simplepieItem->get_content();
      $this->content = $this->decodeEntities($content);
    }
    return $this->content;
  }

  protected $description;
  function description()
  {
    if (!isset($this->description)) {
      $simplepieItem = $this->asSimplepieItem();
      $description = $simplepieItem->get_description();
      $this->description = $this->decodeEntities($description);
    }
    return $this->description;
  }

  protected $creator;
  function creator()
  {
    if (!isset($this->creator)) {
      $simplepieItem = $this->asSimplepieItem();
      $creator = $simplepieItem->get_item_tags(
        'http://purl.org/dc/elements/1.1/', // previously: http://dublincore.org/documents/dcmi-namespace/
        'creator'
      );
      $this->creator = $this->decodeEntities($creator[0]['data']);
    }
    return $this->creator;
  }

  protected $author;
  function author()
  {
    if (!isset($this->author)) {
      $simplepieItem = $this->asSimplepieItem();
      $author = $simplepieItem->get_item_tags(
        'http://purl.org/dc/elements/1.1/', // previously: http://dublincore.org/documents/dcmi-namespace/
        'author'
      );
      $this->author = $this->decodeEntities($author[0]['data']);
    }
    return $this->author;
  }

  protected $categories;
  function categories()
  {
    if (!isset($this->categories)) {
      $simplepieItem = $this->asSimplepieItem();
      $categories = array();
      foreach ($simplepieItem->get_categories() as $category) {
        $categories[] = $this->decodeEntities($category->get_label());
      }
      $this->categories = $categories;
    }
    return $this->categories;
  }

  function decodeEntities($string)
  {
    // This should be fine, because internal encoding in PHP is always UTF-8:
    $string = html_entity_decode(trim($string), ENT_QUOTES, 'UTF-8');

    // Replace some numeric entities that the above misses:
    $codes = array('&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8242;', '&#8243;');
    $replacements = array("'", "'", '"', '"', "'", '"');
    $string = str_replace($codes, $replacements, $string);

    return $string;
  }
}
