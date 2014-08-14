<?php

require_once 'XML/Record.php';

class XML_Record_NLMCatalog extends XML_Record
{
    // Must be an id type that uniquely identifies the record, 
    // usually the record-creating organization's id.
    public static function primary_id_type()
    {
        return 'nlm';
    }

    // Must return array( 'type' => $type, 'value' => $value ) pairs.
    public function ids()
    {
        if (!isset( $this->ids )) {
            // output
            $ids = array();

            $array = $this->as_array();
            $nlmUniqueId = $array['NlmUniqueID'];

            $ids[] = array('type' => 'nlm', 'value' => $nlmUniqueId);

            if (array_key_exists('OtherID', $array)) {
                $otherIds = $array['OtherID'];
                if (!array_key_exists(0, $otherIds)) {
                    $otherIds = array($otherIds);
                }
                //echo "otherIds = "; print_r($otherIds);
                foreach ($otherIds as $otherId) {
                    if ('OCLC' == $otherId['Source']) {
                        $oclcId = trim($otherId['_content']);
                        // For some goofy reason, some of the OCLC ids are prefixed with 'ocm':
                        $oclcId = preg_replace('/^ocm/', '', $oclcId);
                        $ids[] = array('type' => 'oclc', 'value' => $oclcId);
                    }
                }
            }

            //echo "ids = "; print_r($ids);
            $this->ids = $ids;
        }
        return $this->ids;
    }
}
