<?php

namespace UmnLib\Core\XmlRecord;

/* WARNING! This class is currently broken, and has been since
 * discontinuing the use of the obsolote PEAR package XML_Serializer
 * and using instead \Titon\Utility\Converter, which does not
 * support namespaces. The array keys here are those produced
 * by the old XML_Unserializer.
 */

class WorldCatDc extends Record
{
  // Must be an id type that uniquely identifies the record, 
  // usually the record-creating organization's id.
  public static function primaryIdType()
  {
    return 'oclc';
  }

  // Must return array('type' => $type, 'value' => $value) pairs.
  public function ids()
  {
    if (!isset($this->ids))
    {
      $array = $this->asArray();
      // TODO: Add other ids!!!!
      $recordIds = $array['recordData']['oclcdcs']['oclcterms:recordIdentifier'];

      // Normalize these so that they are always arrays of arrays:
      if (!is_array($recordIds[0])) {
        $recordIds = array($recordIds);
      }

      //print_r($recordIds);
/*
                    [oclcterms:recordIdentifier] => Array
                        (
                            [0] => Array
                                (
                                    [xsi:type] => http://purl.org/oclc/terms/lccn
                                    [_content] => 2006307058
                                )

                            [1] => 65167033
 */

      $ids = array();
      foreach ($recordIds as $id) {
        if (is_array($id)) {
          if ($id['xsi:type'] == 'http://purl.org/oclc/terms/lccn') {
            $ids[] = array(
              'type' => 'lccn',
              'value' => $id['_content'],
            );
          } else {
            throw new \InvalidArgumentException("Unrecognized id type."); 
          }
        } else {
          $ids[] = array(
            'type' => 'oclc',
            'value' => $id,
          );
        }
      }
      $this->ids = $ids;
    }
    return $this->ids;
  }
}
