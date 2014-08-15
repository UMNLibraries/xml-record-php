<?php

require_once 'ArgValidator.php';

// TODO: Do I really need this? Seems that just associative arrays will work...
//require_once 'XML/Record/WorldCat/MARC/Datafield/Subfield.php';

class XML_Record_WorldCat_MARC_Datafield
{
    protected $tag;
    function set_tag($tag)
    {
        ArgValidator::validate(
            $tag,
            array('is' => 'string', 'regex' => '/^\d{3}$/i')
        );
        $this->tag = $tag;
    }

    // TODO: Better validation for indicators?

    protected $ind1;
    function set_ind1($ind1)
    {
        ArgValidator::validate( $ind1, array('is' => 'string') );
        $this->ind1 = $ind1;
    }

    protected $ind2;
    function set_ind2($ind2)
    {
        ArgValidator::validate( $ind2, array('is' => 'string') );
        $this->ind2 = $ind2;
    }

    protected $subfields = array();
    function set_subfields($subfields)
    {
        // TODO: Better validation???
        // Also: I don't think ArgValidator handles this syntax with arrays yet!
        //ArgValidator::validate( $subfields, array('is' => 'array') );

        //echo "subfields = "; var_dump( $subfields ); echo "\n";
        if (!array_key_exists(0, $subfields) || !is_array($subfields[0])) {
            $subfields = array( $subfields );
        }
        foreach ($subfields as $subfield) {
            $this->subfields[] = array($subfield['code'] => $subfield['_content']);
        }
    }

    public function subfields()
    {
        $args = func_get_args();
        if (0 == count($args)) {
            return $this->subfields;
        }
        $desired_codes = $args[0];
        if (!is_array($desired_codes)) $desired_codes = array($desired_codes);

        $output_subfields = array();
        foreach ($this->subfields as $subfield) {
            // TODO: Add validation if the $code is invalid for this datafield?
            $keys = array_keys($subfield);
            $code = $keys[0]; // Should be only one.
            if (!in_array($code, $desired_codes)) continue;
            $output_subfields[] = $subfield;
        }
        return $output_subfields;
    }

    // Convenience function for requests for only a single subfield's values,
    // elminiating the need to get at the subfields via an array of associative arrays:
    public function subfield($code)
    {
        $subfields = $this->subfields(array($code));
        $subfield_values = array();
        foreach ($subfields as $subfield) {
            $subfield_values[] = $subfield[$code];
        }
        return $subfield_values;
    }

    // Accessors:
    public function __call($function, $args)
    {
        // Since we're handling only accessors here, the function name should
        // be the same as the property name:
        $property = $function;
        $class = get_class($this);
        $ref_class = new ReflectionClass( $class );
        if (!$ref_class->hasProperty($property)) {
            throw new Exception("Method '$function' does not exist in class '$class'.");
        }
        return $this->$property;
    }

    public function __construct( $args )
    {
        $validated_args = ArgValidator::validate(
            $args,
            array('tag' => array('required' => true),)
        );

        // Change the 'subfield' arg to 'subfields', which is different than
        // how it appears in the PHP array derived from the XML:
        // TODO: Shouldn't datafields *always* have subfields? Seems like a spec violation otherwise.
        if (array_key_exists('subfield', $validated_args)) {
            $validated_args['subfields'] = $validated_args['subfield'];
            unset( $validated_args['subfield'] );
        }

        foreach ($validated_args as $property => $value) {
            $mutator = 'set_' . $property;
            $this->$mutator( $value );
        }
    }

} // end class XML_Record_WorldCat_MARC_Datafield
