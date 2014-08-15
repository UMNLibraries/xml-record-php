<?php

require_once 'XML/Record.php';

class XML_Record_WorldCat_DC extends XML_Record
{
    // Must be an id type that uniquely identifies the record, 
    // usually the record-creating organization's id.
    public static function primary_id_type()
    {
        return 'oclc';
    }

    // Must return array( 'type' => $type, 'value' => $value ) pairs.
    public function ids()
    {
        if (!isset( $this->ids ))
        {
            $array = $this->as_array();
            // TODO: Add other ids!!!!
            $record_ids = $array['recordData']['oclcdcs']['oclcterms:recordIdentifier'];

            // Normalize these so that they are always arrays of arrays:
            if (!is_array($record_ids[0])) {
                $record_ids = array( $record_ids );
            }

            //print_r( $record_ids );
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
            foreach ($record_ids as $id) {
                if (is_array($id)) {
                    if ($id['xsi:type'] == 'http://purl.org/oclc/terms/lccn') {
                        $ids[] = array(
                            'type' => 'lccn',
                            'value' => $id['_content'],
                        );
                    } else {
                        throw new Exception("Unrecognized id type."); 
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

} // end class XML_Record_WorldCat_DC
