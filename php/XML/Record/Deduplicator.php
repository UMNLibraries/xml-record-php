<?php

require_once 'XML/Record/Iterator.php';
require_once 'XML/Record/IdentifierSet.php';

class XML_Record_Deduplicator
{
    protected $external_id_sets;
    public function external_id_sets()
    {
        return $this->external_id_sets;
    }

    // For de-duplicating the records against themselves. Automatically created
    // based on the id_types argument (see below).
    protected $internal_id_sets;
    public function internal_id_sets()
    {
        return $this->internal_id_sets;
    }

    // Array of id types to be used for de-duplicating records against themselves.
    protected $internal_id_types;
    public function internal_id_types()
    {
        return $this->internal_id_types;
    }

    // TODO: Must either inherit from XML_Record, or implement
    // an identical interface.
    protected $xml_record_class;

    // TODO: Must either inherit from XML_Record_File, or implement
    // an identical interface.
    protected $xml_record_file_class;

    protected $iterator;

    // Count of total number of duplicate (skipped) and
    // unique (kept) records. Each duplicated record will
    // be counted once as unique, because we keep the first
    // instance. All subsequent instances of a record will
    // be counted as duplicates. Therefore, the sum of these
    // two counts equals the total number of records.
    protected $count_duplicates = 0;
    public function count_duplicates()
    {
        return $this->count_duplicates;
    }

    protected $count_unique = 0;
    public function count_unique()
    {
        return $this->count_unique;
    }

    protected $input_file_set;
    public function input_file_set()
    {
        return $this->input_file_set;
    }

    protected $output_file_set;
    public function output_file_set()
    {
        return $this->output_file_set;
    }

    protected $duplicates = array();
    public function duplicates()
    {
        return $this->duplicates;
    }

    public function __construct( $params )
    {
        // TODO: Verify capabilities of file sets?
        if (!array_key_exists('input_file_set', $params)) {
            throw new Exception("Missing required param 'input_file_set'");
        }
        $this->input_file_set = $params['input_file_set'];

        if (!array_key_exists('output_file_set', $params)) {
            throw new Exception("Missing required param 'output_file_set'");
        }
        $this->output_file_set = $params['output_file_set'];
        
        if (!array_key_exists('xml_record_class', $params)) {
            throw new Exception("Missing required param 'xml_record_class'");
        }
        $this->xml_record_class = $params['xml_record_class'];
        $xml_record_class_file_name = 
            preg_replace('/_/', '/', $this->xml_record_class) . '.php';
        require_once $xml_record_class_file_name;

        if (!array_key_exists('xml_record_file_class', $params)) {
            throw new Exception("Missing required param 'xml_record_file_class'");
        }
        $this->xml_record_file_class = $params['xml_record_file_class'];
        $xml_record_file_class_file_name = 
            preg_replace('/_/', '/', $this->xml_record_file_class) . '.php';
        require_once $xml_record_file_class_file_name;

        // TODO: Document that this array should have id_type keys
        //  and IdentifierSet values. Validate!!
        $external_id_sets = array();
        if (array_key_exists('external_id_sets', $params) && is_array($params['external_id_sets'])) {
            $external_id_sets = $params['external_id_sets'];
            foreach (array_keys($external_id_sets) as $id_type) {
                if (!is_array($external_id_sets[$id_type])) {
                    $external_id_sets[$id_type] = array( $external_id_sets[$id_type] );
                }
            }
        }
        $this->external_id_sets = $external_id_sets;

        $internal_id_sets = array();
        $internal_id_types = array();
        if (array_key_exists('internal_id_types', $params) && is_array($params['internal_id_types'])) {
            $internal_id_types = $params['internal_id_types'];
        }

        // Automatically create an id set to deduplicate
        // the primary id's of the xml records themselves:
        $primary_id_type = call_user_func(array(
            $this->xml_record_class,
            'primary_id_type',
        ));
        if (!in_array($primary_id_type, $internal_id_types)) {
            $internal_id_types[] = $primary_id_type;
        }

        // TODO: We should need only one id set per internal id type, but
        // use arrays anyway, in case we change our minds.
        foreach ($internal_id_types as $id_type) {
            $internal_id_sets[$id_type] = array( new XML_Record_IdentifierSet() );
        }
 
        $this->internal_id_types = $internal_id_types;
        $this->internal_id_sets = $internal_id_sets;
    }

    public function compress_deduped_file( $file_name )
    {
        $zip_file_name = "$file_name.gz";
        $zip_file_contents = file_get_contents( $file_name );
        $zip_file = gzopen($zip_file_name, "w9");
        gzwrite( $zip_file, $zip_file_contents );
        gzclose( $zip_file );
        unlink( $file_name );
    }

    public function deduplicate()
    {
        foreach ($this->input_file_set->members() as $input_file_name) {

            $input_file = new $this->xml_record_file_class(array(
                'name' => $input_file_name,
            ));

            $iterator = new XML_Record_Iterator(array(
                'xml_record_class' => $this->xml_record_class,
                'file' => $input_file,
            ));

            list($output_file, $output_file_name) =
                $this->open_output_file( $input_file_name, $input_file->header() );

            $count_unique = 0;
            $iterator->rewind();
            while ( $iterator->valid() ) {
                $record = $iterator->current();
                $duplicate_properties = $this->get_duplicate_properties( $record );
                if (count($duplicate_properties) > 0) {
                    //echo "duplicate_properties = "; var_dump( $duplicate_properties );
                    $this->skip( $record, $duplicate_properties );
                    $this->count_duplicates++;
                } else {
                    // TODO: Check to see if we can get the line number from
                    // the XML Reader...
                    $this->keep( $output_file, $record );
                    $count_unique++;
                }
                $iterator->next();
            }
            $this->close_output_file( $output_file, $input_file->footer() );
            if ($count_unique == 0) {
                $this->output_file_set->delete( $output_file_name );
            }
            $this->compress_deduped_file( $input_file_name );
            $this->count_unique += $count_unique;
        }
    }
    
    public function get_duplicate_properties( $record )
    {
        $duplicate_properties = array();

        // TODO: Add checks for heuristically-identifying properties.

        $ids = $record->ids();
        foreach ($ids as $id)
        {
            $id_type = $id['type'];
            $id_value = $id['value'];

            // TODO: Create an interface for the id_sets!

            $internal_id_sets = $this->get_internal_id_sets( $id_type );
            foreach ($internal_id_sets as $internal_id_set) {
                if ($internal_id_set->has_member($id_value)) {
                    $duplicate_properties[] = "Duplicate '$id_type' identifier '$id_value' in records under de-duplication.";
                } else {
                    // Record that we've seen this id, so that we can de-duplicate other records against it:
                    $internal_id_set->add_member($id_value);
                }
            }

            $external_id_sets = $this->get_external_id_sets( $id['type'] );
            foreach ($external_id_sets as $external_id_set) {
                $external_id_set_class = get_class($external_id_set);
                if ($external_id_set->has_member($id_value)) {
                    $duplicate_properties[] = "Duplicate '$id_type' identifier '$id_value' in identifier set '$external_id_set_class'.";
                }
                // Note: We don't add identifiers to external sets, because we're probalby de-duplicating in order to decide
                // whether or not to add records to those external sets.
            }
        }   
        return $duplicate_properties;
    }

    public function get_external_id_sets( $id_type )
    {
        $external_id_sets = $this->external_id_sets();
        return array_key_exists($id_type, $external_id_sets) ? $external_id_sets[$id_type] : array();
    }

    public function get_internal_id_sets( $id_type )
    {
        $internal_id_sets = $this->internal_id_sets();
        return array_key_exists($id_type, $internal_id_sets) ? $internal_id_sets[$id_type] : array();
    }

    // TODO: Record which id('s) was found to be a duplicate??
    // Right now, this only records the primary id of each record, which
    // won't necessarily be the id that was found to be a duplicate.
    public function skip( $record, $duplicate_properties )
    {
        $primary_id = $record->primary_id();
        $this->duplicates[] = array($primary_id => $duplicate_properties);
    }

    public function keep( $output_file, $record )
    {
        fwrite($output_file, $record->as_fragment_string());
    }

    public function open_output_file( $input_file_name, $input_file_header )
    {
        $base_name = basename( $input_file_name );
        $output_file_name = $this->output_file_set->add( $base_name );
  
        // Open output files in overwrite mode to help protect against duplicates:
        $output_file = fopen($output_file_name, 'w')
            or die("Cannot open file '$output_file_name'");

        fwrite($output_file, $input_file_header);
        return array($output_file, $output_file_name);
    }
 
    public function close_output_file( $output_file, $input_file_footer )
    {
        fwrite($output_file, $input_file_footer);
        fclose($output_file);
    }

} // end class XML_Record_Deduplicator
