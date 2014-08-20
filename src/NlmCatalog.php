<?php

namespace UmnLib\Core\XmlRecord;

class NlmCatalog extends Record
{
  // Must be an id type that uniquely identifies the record, 
  // usually the record-creating organization's id.
  public static function primaryIdType()
  {
    return 'nlm';
  }

  // Must return array( 'type' => $type, 'value' => $value ) pairs.
  public function ids()
  {
    if (!isset($this->ids)) {
      // output
      $ids = array();

      $array = $this->asArray();
      // TODO: Not sure this array key lookup will work with Titon...
      $nlmUniqueId = $array['NlmUniqueID'];

      $ids[] = array('type' => 'nlm', 'value' => $nlmUniqueId);

      if (array_key_exists('OtherID', $array)) {
        $otherIds = $array['OtherID'];
        if (!array_key_exists(0, $otherIds)) {
          $otherIds = array($otherIds);
        }
        foreach ($otherIds as $otherId) {
          if ('OCLC' == $otherId['attributes']['Source']) {
            $oclcId = trim($otherId['value']);
            // For some goofy reason, some of the OCLC ids are prefixed with 'ocm':
            $oclcId = preg_replace('/^ocm/', '', $oclcId);
            $ids[] = array('type' => 'oclc', 'value' => $oclcId);
          }
        }
      }
      $this->ids = $ids;
    }
    return $this->ids;
  }
}
