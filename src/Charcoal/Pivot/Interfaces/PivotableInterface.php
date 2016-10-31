<?php

namespace Charcoal\Pivot\Interfaces;

/**
 * Defines an object that is the target of an intermediary pivot object.
 */
interface PivotableInterface
{
    /**
     * Retrieve the object's pivot source object.
     *
     * @return string
     */
    public function belongsTo();
}
