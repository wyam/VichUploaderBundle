<?php

namespace Vich\UploaderBundle\Storage;

use Vich\UploaderBundle\Storage\StorageInterface;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * FileSystemStorage.
 *
 * @author Dustin Dobervich <ddobervich@gmail.com>
 */
class FileSystemStorage implements StorageInterface
{
    /**
     * @var \Vich\UploaderBundle\Mapping\PropertyMappingFactory $factory
     */
    protected $factory;

    /**
     * Constructs a new instance of FileSystemStorage.
     *
     * @param \Vich\UploaderBundle\Mapping\PropertyMappingFactory $factory The factory.
     */
    public function __construct(PropertyMappingFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritDoc}
     */
    public function upload($obj)
    {
        $mappings = $this->factory->fromObject($obj);
        foreach ($mappings as $mapping) {
            $file = $mapping->getPropertyValue($obj);
            if (is_null($file) || !($file instanceof UploadedFile)) {
                continue;
            }

            if ($mapping->hasNamer()) {
                $name = $mapping->getNamer()->name($obj, $mapping->getProperty()->getName());
                $file->move($mapping->getUploadDir(), $name);
            } elseif($file instanceof UploadedFile) {
                $name = $file->getClientOriginalName();
                $file->move($mapping->getUploadDir(), $name);
            } elseif ($file instanceof File) {
                $name = $file->getFileName();
            }    
            
            $mapping->getFileNameProperty()->setValue($obj, $name);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function remove($obj)
    {
        $mappings = $this->factory->fromObject($obj);
        foreach ($mappings as $mapping) {
            if ($mapping->getDeleteOnRemove()) {
                $name = $mapping->getFileNameProperty()->getValue($obj);
                if (null === $name) {
                    continue;
                }

                @unlink(sprintf('%s/%s', $mapping->getUploadDir(), $name->getFileName()));
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function resolvePath($obj, $field)
    {
        $mapping = $this->factory->fromField($obj, $field);
        if (null === $mapping) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to find uploadable field named: "%s"', $field
            ));
        }
        
        if($mapping->getFileNameProperty()->getValue($obj) instanceof File){
            $fileName = $mapping->getFileNameProperty()->getValue($obj)->getFileName();
        } else {
            $fileName = $mapping->getFileNameProperty()->getValue($obj);
        }

        return sprintf('%s/%s',
            $mapping->getUploadDir(),
            $fileName
        );
    }
}
