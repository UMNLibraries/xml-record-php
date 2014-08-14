<?php

require_once 'XML/Record.php';
require_once 'XML/Record/WorldCat/MARC/Datafield.php';
require_once 'ISBN/Factory.php';

class XML_Record_WorldCat_MARC extends XML_Record
{
    protected $datafields = array();
    public function datafields()
    {
        $args = func_get_args();
        $tags = $args[0];
        $datafields_tag_map = $this->datafields_tag_map();
        if (!is_array($tags)) {
            if (isset($tags)) {
                // TODO: Use ArgValidator!!!
                throw new Exception("The 'tags' arg must be of type array.");
            } else {
                $tags = array_keys( $datafields_tag_map );
            }
        }
        if (count($this->datafields) == 0) {
            $this->datafields = array();
        }
        $output_datafields = array();
        foreach ($tags as $tag) {
            if (!array_key_exists($tag, $datafields_tag_map)) continue;
            if (!array_key_exists($tag, $this->datafields)) {
                $this->datafields[$tag] = array();
                foreach ($datafields_tag_map[$tag] as $datafield) {
                    $this->datafields[$tag][] = new XML_Record_WorldCat_MARC_Datafield( $datafield );
                }
            }
            $output_datafields[$tag] = $this->datafields[$tag];
        }
        return $output_datafields;
    }

    // Convenience function for requests for only a single datafield's values,
    // elminiating the need to get at the datafields via an associative array:
    public function datafield($tag)
    {
        $datafields = $this->datafields(array($tag));
        return array_key_exists($tag, $datafields) ? $datafields[$tag] : array();
    }

    protected $datafields_tag_map = array();
    public function datafields_tag_map()
    {
        if (count($this->datafields_tag_map) == 0) {
            $this->datafields_tag_map = $this->generate_field_tag_map( 'datafield' );
        }
        return $this->datafields_tag_map;
    }

    protected $controlfields = array();
    public function controlfields()
    {
        $args = func_get_args();
        $tags = $args[0];
        $controlfields_tag_map = $this->controlfields_tag_map();
        if (!is_array($tags)) {
            if (isset($tags)) {
                // TODO: Use ArgValidator!!!
                throw new Exception("The 'tags' arg must be of type array.");
            } else {
                $tags = array_keys( $controlfields_tag_map );
            }
        }
        if (count($this->controlfields) == 0) {
            $this->controlfields = array();
        }
        $output_controlfields = array();
        foreach ($tags as $tag) {
            if (!array_key_exists($tag, $this->controlfields)) {
                // Control fields are effectively just key-value pairs.
                // Kind of clunky that the '0' index has to be here, but it eliminates the need for
                // an extra 'generate_controlfields_tag_map' method.
                $this->controlfields[$tag] = $controlfields_tag_map[$tag][0]['_content'];
            }
            $output_controlfields[$tag] = $this->controlfields[$tag];
        }
        return $output_controlfields;
    }

    // Convenience function for requests for only a single controlfield's values,
    // elminiating the need to get at the controlfields via an associative array:
    public function controlfield($tag)
    {
        $controlfields = $this->controlfields(array($tag));
        // There should be no more than one of each controlfield, so we return NULL
        // if it doesn't exist, rather than an empty array:
        return array_key_exists($tag, $controlfields) ? $controlfields[$tag] : NULL;
    }

    protected $controlfields_tag_map = array();
    public function controlfields_tag_map()
    {
        if (count($this->controlfields_tag_map) == 0) {
            $this->controlfields_tag_map = $this->generate_field_tag_map( 'controlfield' );
        }
        return $this->controlfields_tag_map;
    }

    // Make an associative array for fields of the given type (control|data)
    // where the keys are tags and the values are arrays of all fields for that tag.
    public function generate_field_tag_map($type)
    {
        $marc_array = $this->as_array();
        $map = array();
        $fields = $marc_array[$type];
        foreach ($fields as $field) {
            $tag = $field['tag'];
            if (!array_key_exists($tag, $map)) {
                $map[$tag] = array();
            }
            // Why was I doing it this way? The shorter non-commented
            // code below seems to work just as well.
            //$tag_map =& $map[$tag];
            //$tag_map[] = $field;
            $map[$tag][] = $field;
        }
        return $map;
    }

    public function leader()
    {
        $marc_array = $this->as_array();
        return $marc_array['leader'];
    }

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

            // TODO: Add other ids???
            $ids = array();

            // OCLC ID
            $ids[] = array(
                'type' => 'oclc',
                'value' => $this->controlfield('001'),
            );

/*
            // ISBNs
            $isbns = array(); // To check for duplicate ISBNs.
            foreach ($this->datafield('020') as $datafield) {
                foreach($datafield->subfield('a') as $isbn) {
                    // ISBNs are often messy, and must be cleaned up for de-duplication purposes.
                    $isbn = trim($isbn);
                    preg_match('/^([0-9X]+)/', $isbn, $matches);
                    try {
                        $isbn_obj = ISBN_Factory::create( $matches[1] );
                        // For a de-duplication ID, standardize on ISBN13:
                        $isbn13 = $isbn_obj->as_isbn13()->isbn();
                    } catch (Exception $e) {
                        // ISBNs are not critical IDs, so just skip this one if it's invalid:
                        // TODO: Enhance this error message!
                        error_log( $e->getMessage() );
                        continue;
                    }
                    if (in_array($isbn13, $isbns)) continue;
                    $isbns[] = $isbn13;

                    $ids[] = array(
                        'type' => 'isbn',
                        'value' => $isbn13,
                    );
                }
            }
*/

            // SuDoc Numbers
            $sudocs = array(); // To check for duplicate SuDoc #s.
            foreach ($this->datafield('086') as $datafield) {
                if ($datafield->ind1() != '0') continue; // An ind1 of 0 == SuDoc Number
                foreach($datafield->subfield('a') as $sudoc) {
                    $sudoc = trim($sudoc);
                    $sudoc = strtoupper($sudoc); // Normalize case to aid de-duplication.

                    if (in_array($sudoc, $sudocs)) continue;
                    $sudocs[] = $sudoc;
                    
                    $ids[] = array(
                        'type' => 'sudoc',
                        'value' => $sudoc,
                    );
                }
            }

            // DOIs
            $dois = array(); // To check for duplicates.
            foreach ($this->datafield('024') as $datafield) {
                $proto_doi = '';
                $code_2 = '';
                foreach($datafield->subfield('a') as $proto_doi) { // Should be only one of these.
                    $proto_doi = trim($proto_doi);
                }
                foreach($datafield->subfield('2') as $code_2) { // Should be only one of these.
                    $code_2 = trim($code_2);
                }
                if (preg_match('/^doi$/i', $code_2) || (preg_match('/^doi:/i', $proto_doi))) {
                    $doi = preg_replace('/^doi:/i', '', $proto_doi); 
                    if (in_array($doi, $dois)) continue;
                    $dois[] = $doi;
                    $ids[] = array(
                        'type' => 'doi',
                        'value' => $doi,
                    );
                }
            }

            // URIs: Currently using these only for DOIs.
            $urls = array();
            foreach ($this->datafield('856') as $datafield) {
                if (in_array($datafield->ind2(), array(2,8))) {
                    // An ind2 of 2 == "Related resource"; an ind2 of 8 == "No display content generated>"
                    continue; 
                }
                foreach($datafield->subfield('u') as $url) { // A code of u == URI
                    $url = trim($url);
                    if (in_array($url, $urls)) continue;
                    $urls[] = $url;

                    // DOIs: Appear mostly in URIs in WorldCat MARC records.
                    // Try to extract a DOI:
                    $doi = NULL;
                    $matches = array();
                    if (preg_match('/doi\.org\/(.*)$/i', $url, $matches)) {
                        $doi = $matches[1];
                    } else if (preg_match('/doi\/book\/(.*)$/i', $url, $matches)) {
                        $doi = $matches[1];
                    } else if (preg_match('/id=doi:(.*)$/i', $url, $matches)) {
                        $doi = $matches[1];
                    } else if (preg_match('/doifinder\/(.*)$/i', $url, $matches)) {
                        $doi = $matches[1];
                    } else if (preg_match('/^doi:(.*)$/i', $url, $matches)) {
                        $doi = $matches[1];
                    }
                    if (NULL == $doi || in_array($doi, $dois)) continue;
                    $dois[] = $doi;
                    $ids[] = array(
                        'type' => 'doi',
                        'value' => $doi,
                    );
                }
            }

            $this->ids = $ids;
        }
        return $this->ids;
    }

} // end class XML_Record_WorldCat_MARC
