<?php

namespace UmnLib\Core\XmlRecord;

class WorldCatMarc extends Record
{
  protected $datafields = array();
  public function datafields()
  {
    $args = func_get_args();
    $tags = $args[0];
    $datafieldsTagMap = $this->datafieldsTagMap();
    if (!is_array($tags)) {
      if (isset($tags)) {
        // TODO: Use ArgValidator!!!
        throw new \InvalidArgumentException("The 'tags' arg must be of type array.");
      } else {
        $tags = array_keys($datafieldsTagMap);
      }
    }
    if (count($this->datafields) == 0) {
      $this->datafields = array();
    }
    $outputDatafields = array();
    foreach ($tags as $tag) {
      if (!array_key_exists($tag, $datafieldsTagMap)) continue;
      if (!array_key_exists($tag, $this->datafields)) {
        $this->datafields[$tag] = array();
        foreach ($datafieldsTagMap[$tag] as $datafield) {
          $this->datafields[$tag][] = new WorldCatMarc\Datafield($datafield);
        }
      }
      $outputDatafields[$tag] = $this->datafields[$tag];
    }
    return $outputDatafields;
  }

  // Convenience function for requests for only a single datafield's values,
  // elminiating the need to get at the datafields via an associative array:
  public function datafield($tag)
  {
    $datafields = $this->datafields(array($tag));
    return array_key_exists($tag, $datafields) ? $datafields[$tag] : array();
  }

  protected $datafieldsTagMap = array();
  public function datafieldsTagMap()
  {
    if (count($this->datafieldsTagMap) == 0) {
      $this->datafieldsTagMap = $this->generateFieldTagMap('datafield');
    }
    return $this->datafieldsTagMap;
  }

  protected $controlfields = array();
  public function controlfields()
  {
    $args = func_get_args();
    $tags = $args[0];
    $controlfieldsTagMap = $this->controlfieldsTagMap();
    if (!is_array($tags)) {
      if (isset($tags)) {
        // TODO: Use ArgValidator!!!
        throw new \InvalidArgumentException("The 'tags' arg must be of type array.");
      } else {
        $tags = array_keys($controlfieldsTagMap);
      }
    }
    if (count($this->controlfields) == 0) {
      $this->controlfields = array();
    }
    $outputControlfields = array();
    foreach ($tags as $tag) {
      if (!array_key_exists($tag, $this->controlfields)) {
        // TODO: Obsolete comment!
        // Control fields are effectively just key-value pairs.
        // Kind of clunky that the '0' index has to be here, but it eliminates the need for
        // an extra 'generateControlfieldsTagMap' method.
        $this->controlfields[$tag] = $controlfieldsTagMap[$tag][0]['value'];
      }
      $outputControlfields[$tag] = $this->controlfields[$tag];
    }
    return $outputControlfields;
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

  protected $controlfieldsTagMap = array();
  public function controlfieldsTagMap()
  {
    if (count($this->controlfieldsTagMap) == 0) {
      $this->controlfieldsTagMap = $this->generateFieldTagMap('controlfield');
    }
    return $this->controlfieldsTagMap;
  }

  // Make an associative array for fields of the given type (control|data)
  // where the keys are tags and the values are arrays of all fields for that tag.
  public function generateFieldTagMap($type)
  {
    $marcArray = $this->asArray();
    $map = array();
    $fields = $marcArray[$type];
    foreach ($fields as $field) {
      // Tags should always be 3 digits, 0-padded. Titon removes
      // the 0-padding, so we put it back:
      $tag = sprintf("%03d", $field['attributes']['tag']);
      if (!array_key_exists($tag, $map)) {
        $map[$tag] = array();
      }
      $map[$tag][] = $field;
    }
    return $map;
  }

  public function leader()
  {
    $marcArray = $this->asArray();
    return $marcArray['leader'];
  }

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
                        $isbnObj = \UmnLib\Core\Isbn\Factory::create( $matches[1] );
                        // For a de-duplication ID, standardize on ISBN13:
                        $isbn13 = $isbnObj->asIsbn13()->isbn();
                    } catch (Exception $e) {
                        // ISBNs are not critical IDs, so just skip this one if it's invalid:
                        // TODO: Enhance this error message!
                        error_log($e->getMessage());
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
        $protoDoi = '';
        $code2 = '';
        foreach($datafield->subfield('a') as $protoDoi) { // Should be only one of these.
          $protoDoi = trim($protoDoi);
        }
        foreach($datafield->subfield('2') as $code2) { // Should be only one of these.
          $code2 = trim($code2);
        }
        if (preg_match('/^doi$/i', $code2) || (preg_match('/^doi:/i', $protoDoi))) {
          $doi = preg_replace('/^doi:/i', '', $protoDoi); 
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
}
