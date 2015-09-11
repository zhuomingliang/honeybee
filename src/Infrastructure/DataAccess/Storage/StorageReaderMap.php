<?php

namespace Honeybee\Infrastructure\DataAccess\Storage;

use Trellis\Common\Collection\TypedMap;
use Trellis\Common\Collection\UniqueCollectionInterface;

class StorageReaderMap extends TypedMap implements UniqueCollectionInterface
{
    public function getByEntityType($entity_type_prefix)
    {
        $reader_name = sprintf('%s::projection.standard::view_store::reader', $entity_type_prefix);
        if (!$this->hasKey($reader_name)) {
            throw new RuntimeError(sprintf('No storage-reader for key %s found.', $reader_name));
        }

        return $this->getItem($reader_name);
    }

    protected function getItemImplementor()
    {
        return StorageReaderInterface::CLASS;
    }
}
