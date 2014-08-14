<?php

require_once 'XML/Record.php';

class XML_Record_PubMed extends XML_Record
{
    // Must be an id type that uniquely identifies the record, 
    // usually the record-creating organization's id.
    public static function primary_id_type()
    {
        return 'pubmed';
    }

    // Must return array( 'type' => $type, 'value' => $value ) pairs.
    public function ids()
    {
        if (!isset( $this->ids ))
        {
            $array = $this->as_array();
            $article_ids = $array['PubmedData']['ArticleIdList']['ArticleId'];

            // Normalize these so that they are always arrays of arrays.
            // Each article_id itself is an array, but will not have a key
            // of '0'.
            if (is_array($article_ids) && (!array_key_exists(0, $article_ids))) {
                $article_ids = array( $article_ids );
            }

            $ids = array();
            foreach ($article_ids as $id) {
                $ids[] = array(
                    'type' => $id['IdType'],
                    'value' => $id['_content'],
                );
            }
            $this->ids = $ids;
        }
        return $this->ids;
    }

} // end class XML_Record_PubMed
