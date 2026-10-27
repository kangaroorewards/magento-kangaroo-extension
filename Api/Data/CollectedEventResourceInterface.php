<?php

namespace Kangaroorewards\Core\Api\Data;

interface CollectedEventResourceInterface
{
    /**
     * @param string $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getId();

    /**
     * @param string|null $type
     * @return $this
     */
    public function setType($type);

    /**
     * @return string|null
     */
    public function getType();

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName($name);

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param \Kangaroorewards\Core\Api\Data\CollectedEventAttributesInterface|null $attributes
     * @return $this
     */
    public function setAttributes($attributes);

    /**
     * @return \Kangaroorewards\Core\Api\Data\CollectedEventAttributesInterface|null
     */
    public function getAttributes();
}
