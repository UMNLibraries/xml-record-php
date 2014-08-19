<?php

namespace UmnLib\Core\XmlRecord\File;

class WorldCatMarc extends WorldCat
{
  public function recordElementNamespace()
  {
    return 'http://www.loc.gov/MARC21/slim';
  }
}
