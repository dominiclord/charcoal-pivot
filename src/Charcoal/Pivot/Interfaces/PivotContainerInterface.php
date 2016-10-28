<?php

namespace Charcoal\Pivot\Interfaces;

/**
 * Defines a object that can have pivots.
 */
interface PivotContainerInterface
{
    /**
     * Gets the pivot config from the object metadata.
     *
     * @return array
     */
    public function pivotConfig();

    /**
     * Returns pivotable objects
     *
     * @return array
     */
    public function pivotableObjects();

    /**
     * Returns true
     *
     * @return boolean
     */
    public function isPivotContainer();
}
