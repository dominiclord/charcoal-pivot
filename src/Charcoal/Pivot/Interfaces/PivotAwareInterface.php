<?php

namespace Charcoal\Pivot\Interfaces;

/**
 * Defines an object that can have relationships through an intermediary object.
 */
interface PivotAwareInterface
{
    /**
     * Retrieve the object's type identifier.
     *
     * @return string
     */
    public function objType();

    /**
     * Retrieve the object's unique ID.
     *
     * @return mixed
     */
    public function id();
}
