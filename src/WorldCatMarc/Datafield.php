<?php

namespace UmnLib\Core\XmlRecord\WorldCatMarc;

use UmnLib\Core\ArgValidator;

// TODO: Do I really need this? Seems that just associative arrays will work...
//require_once 'XML/Record/WorldCat/MARC/Datafield/Subfield.php';

class Datafield
{
  protected $tag;
  function setTag($tag)
  {
    ArgValidator::validate(
      $tag,
      array('is' => 'string', 'regex' => '/^\d{3}$/i')
    );
    $this->tag = $tag;
  }

  // TODO: Better validation for indicators?

  protected $ind1;
  function setInd1($ind1)
  {
    //ArgValidator::validate($ind1, array('is' => 'string'));
    ArgValidator::validate($ind1, array('is' => 'scalar'));
    $this->ind1 = $ind1;
  }

  protected $ind2;
  function setInd2($ind2)
  {
    //ArgValidator::validate($ind2, array('is' => 'string'));
    ArgValidator::validate($ind2, array('is' => 'scalar'));
    $this->ind2 = $ind2;
  }

  protected $subfields = array();
  function setSubfields($subfields)
  {
    // TODO: Better validation???
    // Also: I don't think ArgValidator handles this syntax with arrays yet!
    //ArgValidator::validate( $subfields, array('is' => 'array') );

    //echo "subfields = "; var_dump( $subfields ); echo "\n";
    if (!array_key_exists(0, $subfields) || !is_array($subfields[0])) {
      $subfields = array($subfields);
    }
    foreach ($subfields as $subfield) {
      $this->subfields[] = array($subfield['attributes']['code'] => $subfield['value']);
    }
  }

  public function subfields()
  {
    $args = func_get_args();
    if (0 == count($args)) {
      return $this->subfields;
    }
    $desiredCodes = $args[0];
    if (!is_array($desiredCodes)) $desiredCodes = array($desiredCodes);

    $outputSubfields = array();
    foreach ($this->subfields as $subfield) {
      // TODO: Add validation if the $code is invalid for this datafield?
      $keys = array_keys($subfield);
      $code = $keys[0]; // Should be only one.
      if (!in_array($code, $desiredCodes)) continue;
      $outputSubfields[] = $subfield;
    }
    return $outputSubfields;
  }

  // Convenience function for requests for only a single subfield's values,
  // elminiating the need to get at the subfields via an array of associative arrays:
  public function subfield($code)
  {
    $subfields = $this->subfields(array($code));
    $subfieldValues = array();
    foreach ($subfields as $subfield) {
      $subfieldValues[] = $subfield[$code];
    }
    return $subfieldValues;
  }

  // Accessors:
  public function __call($function, $args)
  {
    // Since we're handling only accessors here, the function name should
    // be the same as the property name:
    $property = $function;
    $class = get_class($this);
    $ref_class = new \ReflectionClass( $class );
    if (!$ref_class->hasProperty($property)) {
      throw new \InvalidArgumentException("Method '$function' does not exist in class '$class'.");
    }
    return $this->$property;
  }

  //public function __construct($args)
  public function __construct(Array $datafield)
  {
    /*
    $validatedArgs = ArgValidator::validate(
      $args,
      array('tag' => array('required' => true))
    );
     */

    //echo "datafield = "; print_r($datafield);
    foreach ($datafield['attributes'] as $property => $value) {
      if ('tag' == $property) {
        // Tags should always be 3 digits, 0-padded. Titon removes
        // the 0-padding, so we put it back:
        $value = sprintf("%03d", $value);
      }
      $mutator = 'set' . ucfirst($property);
      $this->$mutator($value);
    }

    // Change the 'subfield' arg to 'subfields', which is different than
    // how it appears in the PHP array derived from the XML:
    // TODO: Shouldn't datafields *always* have subfields? Seems like a spec violation otherwise.
    /*
    if (array_key_exists('subfield', $validatedArgs)) {
      $validatedArgs['subfields'] = $validatedArgs['subfield'];
      unset($validatedArgs['subfield']);
    }
     */
    if (is_array($datafield['value']) && array_key_exists('subfield', $datafield['value'])) {
      $this->setSubfields($datafield['value']['subfield']);
    }

    /*
    foreach ($validatedArgs as $property => $value) {
      $mutator = 'set' . ucfirst($property);
      $this->$mutator($value);
    }
     */
  }
}
