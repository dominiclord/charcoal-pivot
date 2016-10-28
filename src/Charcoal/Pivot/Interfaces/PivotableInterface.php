<?php

namespace Charcoal\Pivot\Interfaces;

/**
 * Defines an object that is the target of an intermediary pivot object.
 */
interface PivotableInterface
{
    /**
     * Retrieve the object's label.
     *
     * @return string
     */
    public function pivotLabel();
}
