<?php

namespace Kangaroorewards\Core\Model\CollectedEvent;

use Kangaroorewards\Core\Api\Data\CollectedEventAttributesInterface;
use Kangaroorewards\Core\Api\Data\CollectedEventResourceInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class Resource extends AbstractSimpleObject implements CollectedEventResourceInterface
{
    public function setId($id)
    {
        return $this->_set('id', $id);
    }

    public function getId()
    {
        return $this->_get('id');
    }

    public function setType($type)
    {
        return $this->_set('type', $type);
    }

    public function getType()
    {
        return $this->_get('type');
    }

    public function setName($name)
    {
        return $this->_set('name', $name);
    }

    public function getName()
    {
        return $this->_get('name');
    }

    public function setAttributes($attributes)
    {
        return $this->_set('attributes', $attributes);
    }

    public function getAttributes()
    {
        return $this->_get('attributes');
    }
}
