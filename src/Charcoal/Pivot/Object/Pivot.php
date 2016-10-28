<?php

namespace Charcoal\Pivot\Object;

// Dependency from 'charcoal-core'
use \Charcoal\Model\AbstractModel;

/**
 * Pivot table to join two objects.
 */
class Pivot extends AbstractModel
{
    /**
     * The source object ID.
     *
     * @var mixed
     */
    protected $sourceObjectId;

    /**
     * The source object type.
     *
     * @var string
     */
    protected $sourceObjectType;

    /**
     * The target object ID.
     *
     * @var mixed
     */
    protected $targetObjectId;

    /**
     * The target object type.
     *
     * @var mixed
     */
    protected $targetObjectType;

    /**
     * Pivots are active by default
     *
     * @var boolean
     */
    protected $active = true;

    /**
     * The pivot's position amongst other pivots.
     *
     * @var integer
     */
    protected $position = 0;

// Setters
// =============================================================================

    /**
     * Set the source object type.
     *
     * @param string $type The object type identifier.
     * @throws InvalidArgumentException If provided argument is not of type 'string'.
     * @return self
     */
    public function setSourceObjectType($type)
    {
        if (!is_string($type)) {
            throw new InvalidArgumentException('Object type must be a string.');
        }

        $this->sourceObjectType = $type;

        return $this;
    }

    /**
     * Set the source object ID.
     *
     * @param mixed $id The object ID to join the pivot to.
     * @throws InvalidArgumentException If provided argument is not a string or numerical value.
     * @return self
     */
    public function setSourceObjectId($id)
    {
        if (!is_scalar($id)) {
            throw new InvalidArgumentException(
                'Object ID must be a string or numerical value.'
            );
        }

        $this->sourceObjectId = $id;

        return $this;
    }

    /**
     * Set the target object type.
     *
     * @param string $type The object type identifier.
     * @throws InvalidArgumentException If provided argument is not of type 'string'.
     * @return self
     */
    public function setTargetObjectType($type)
    {
        if (!is_string($type)) {
            throw new InvalidArgumentException('Object type must be a string.');
        }

        $this->targetObjectType = $type;

        return $this;
    }

    /**
     * Set the target object ID.
     *
     * @param mixed $id The object ID to pivot upon with.
     * @throws InvalidArgumentException If provided argument is not a string or numerical value.
     * @return self
     */
    public function setTargetObjectId($id)
    {
        if (!is_scalar($id)) {
            throw new InvalidArgumentException(
                'Object ID must be a string or numerical value.'
            );
        }

        $this->targetObjectId = $id;

        return $this;
    }

    /**
     * Define the pivot's position amongst other pivot targets to the source object.
     *
     * @param integer $position The position (for ordering purpose).
     * @throws InvalidArgumentException If the position is not an integer (or numeric integer string).
     * @return self
     */
    public function setPosition($position)
    {
        if ($position === null) {
            $this->position = null;
            return $this;
        }

        if (!is_numeric($position)) {
            throw new InvalidArgumentException(
                'Position must be an integer.'
            );
        }

        $this->position = (int)$position;

        return $this;
    }

    /**
     * @param boolean $active The active flag.
     * @return Content Chainable
     */
    public function setActive($active)
    {
        $this->active = !!$active;
        return $this;
    }

// Getters
// =============================================================================

    /**
     * Retrieve the source object type.
     *
     * @return string
     */
    public function sourceObjectType()
    {
        return $this->sourceObjectType;
    }

    /**
     * Retrieve the source object ID.
     *
     * @return mixed
     */
    public function sourceObjectId()
    {
        return $this->sourceObjectId;
    }

    /**
     * Retrieve the target object ID.
     *
     * @return mixed
     */
    public function targetObjectType()
    {
        return $this->targetObjectType;
    }

    /**
     * Retrieve the target object ID.
     *
     * @return mixed
     */
    public function targetObjectId()
    {
        return $this->targetObjectId;
    }

    /**
     * Retrieve the pivot's position.
     *
     * @return integer
     */
    public function position()
    {
        return $this->position;
    }

    /**
     * @return boolean
     */
    public function active()
    {
        return $this->active;
    }
}
