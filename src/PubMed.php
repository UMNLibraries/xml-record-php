<?php

namespace UmnLib\Core\XmlRecord;

class PubMed extends Record
{
  // Must be an id type that uniquely identifies the record, 
  // usually the record-creating organization's id.
  public static function primaryIdType()
  {
    return 'pubmed';
  }

  // Must return array('type' => $type, 'value' => $value) pairs.
  public function ids()
  {
    if (!isset($this->ids))
    {
      $array = $this->asArray();
      $articleIds = $array['PubmedData']['ArticleIdList']['ArticleId'];

      // Normalize these so that they are always arrays of arrays.
      // Each articleId itself is an array, but will not have a key
      // of '0'.
      if (is_array($articleIds) && (!array_key_exists(0, $articleIds))) {
        $articleIds = array( $articleIds );
      }

      $ids = array();
      foreach ($articleIds as $id) {
        $ids[] = array(
          'type' => $id['attributes']['IdType'],
          'value' => $id['value'],
        );
      }
      $this->ids = $ids;
    }
    return $this->ids;
  }
}
