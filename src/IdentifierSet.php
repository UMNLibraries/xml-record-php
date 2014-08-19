<?php

namespace UmnLib\Core\XmlRecord;

class IdentifierSet
{
  protected $ids = array();

  public function ids()
  {
    return $this->ids;
  }

  public function hasMember($id)
  {
    return in_array($id, $this->ids) ? true : false;
  }

  // TODO: Allow only scalars, strings???
  public function addMember($id)
  {
    if ($this->hasMember($id)) {
      throw new \InvalidArgumentException("Attempt to add duplicate member '$id'.");
    }
    $this->ids[] = $id;
  }
}
