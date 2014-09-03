# xml-record-php

A PHP package for processing an XML document as a stream of records.

## Example Usage

```php
$xri = new \UmnLib\Core\XmlRecord\Iterator(array(
  'file' => new \UmnLib\Core\XmlRecord\File\PubMed(array(
      'name' => $filename,
    )),
  'xmlRecordClass' => '\UmnLib\Core\XmlRecord\PubMed',
));

while ($xri->valid()) {
  $record = $xri->current();

  // PHP associative array, including the xml attributes:
  $array = $record->asArray();
 
  // DOM element representations:
  $domElement = $record->asDomElement();
  $simpleXmlElement = $record->asSimpleXmlElement();
  $fragmentString = $record->asFragmentString();
 
  // DOM document representations:
  $domDocument = $record->asDomDocument();
  $string = $record->asString();
 
  // A record may contain many unique identifiers. XmlRecord
  // requires that one unique identifier type be designated primary.
  $ids = $record->ids();
  $primaryId = $record->primaryId();
  $primaryIdType = $record->primaryIdType();

  $xri->next();
}
```

## Installing

Install via [Composer](http://getcomposer.org). In your project's `composer.json`:

```json
  "require": {
    "umnlib/xml-record": "1.0.*"
  },
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:UMNLibraries/xml-record-php.git"
    }
  ]
```

## TODO

* Increase test coverage!
* The array representation of a record does not support XML namespaces. Either patch \Titon\Utility\Converter to fix that, or use something else.
* The entire deduplication functionality could use massive improvement.

## Older Versions

For older versions of this package that did not use Composer, see the `0.x.y` releases.

## Attribution

The University of Minnesota Libraries created this software for the [EthicShare](http://www.ethicshare.org/about) project.
