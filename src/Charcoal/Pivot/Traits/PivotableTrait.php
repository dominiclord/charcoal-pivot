<?php
namespace Charcoal\Pivot\Traits;

use InvalidArgumentException;

// From 'charcoal-core'
use Charcoal\Model\ModelInterface;
use Charcoal\Loader\CollectionLoader;

// From 'charcoal-admin'
use Charcoal\Admin\Widget\PivotWidget;

// From 'charcoal-pivot'
use Charcoal\Pivot\Interfaces\PivotableInterface;
use Charcoal\Pivot\Object\Pivot;

/**
 * Provides support for pivots on objects.
 *
 * Used by target objects that are pivoted upon by another source object.
 *
 * Abstract methods needs to be implemented.
 *
 * Implementation of {@see \Charcoal\Pivot\Interfaces\PivotableInterface}
 *
 * ## Required Services
 *
 * - "model/factory" — {@see \Charcoal\Model\ModelFactory}
 * - "model/collection/loader" — {@see \Charcoal\Loader\CollectionLoader}
 */
trait PivotableTrait
{
    /**
     * Retrieve the source object associated to the current target object.
     *
     * @param  string|null $sourceObjType Filter the pivots by an object type identifier.
     * @throws InvalidArgumentException If the $sourceObjType is invalid.
     * @return Collection|Pivot[]
     */
    public function belongsTo($sourceObjType = null)
    {
        if ($sourceObjType === null) {
            throw new InvalidArgumentException('Source object type required.');
        } elseif (!is_string($sourceObjType)) {
            throw new InvalidArgumentException('Source object type must be a string.');
        }

        $targetObjType = $this->objType();
        $targetObjId   = $this->id();

        $pivotProto = $this->modelFactory()->get(Pivot::class);
        $pivotTable = $pivotProto->source()->table();

        $sourceObjProto = $this->modelFactory()->get($sourceObjType);
        $sourceObjType  = $sourceObjProto->objType();
        $sourceObjTable = $sourceObjProto->source()->table();

        if (!$sourceObjProto->source()->tableExists() || !$pivotProto->source()->tableExists()) {
            return [];
        }

        $query = '
            SELECT
                source_obj.*,
                pivot_obj.id AS pivot_id
            FROM
                `'.$sourceObjTable.'` AS source_obj
            LEFT JOIN
                `'.$pivotTable.'` AS pivot_obj
            ON
                pivot_obj.source_object_id = source_obj.id
            WHERE
                source_obj.active = 1
            AND
                pivot_obj.target_object_type = "'.$targetObjType.'"
            AND
                pivot_obj.target_object_id = "'.$targetObjId.'"
            AND
                pivot_obj.source_object_type = "'.$sourceObjType.'"

            ORDER BY pivot_obj.position ASC';

        $loader = $this->collectionLoader();
        $loader->setModel($sourceObjProto);

        $collection = $loader->loadFromQuery($query);

        return $collection[0];
    }

    /**
     * Save hook called after saving the model.
     *
     * @return boolean
     */
    public function postPivotSave()
    {
        return true;
    }

    /**
     * Detach the current object's parent relationships.
     *
     * @return boolean
     */
    public function removeParentJoins()
    {
        $loader = $this->collectionLoader();
        $loader->reset()
               ->setModel(Pivot::class)
               ->addFilter('target_object_type', $this->objType())
               ->addFilter('target_object_id', $this->id());

        $collection = $loader->load();

        foreach ($collection as $obj) {
            $obj->delete();
        }

        return true;
    }

    // Abstract Methods
    // =============================================================================

    /**
     * Retrieve the object's type identifier.
     *
     * @return string
     */
    abstract function objType();

    /**
     * Retrieve the object's unique ID.
     *
     * @return mixed
     */
    abstract function id();

    /**
     * Retrieve the object model factory.
     *
     * @return \Charcoal\Factory\FactoryInterface
     */
    abstract public function modelFactory();

    /**
     * Retrieve the object's label.
     *
     * @return string
     */
    abstract public function collectionLoader();
}
