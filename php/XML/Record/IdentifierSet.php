<?php

class XML_Record_IdentifierSet
{
    protected $ids = array();

    public function ids()
    {
        return $this->ids;
    }

    public function has_member( $id )
    {
        return in_array($id, $this->ids) ? true : false;
    }

    // TODO: Allow only scalars, strings???
    public function add_member( $id )
    {
        if ($this->has_member( $id ))
        {
            throw new Exception("Attempt to add duplicate member '$id'.");
        }
        $this->ids[] = $id;
    }
} // end class XML_Record_IdentifierSet
