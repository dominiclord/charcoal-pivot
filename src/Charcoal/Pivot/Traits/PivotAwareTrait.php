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
 * Used by source objects that need a pivot to a target object.
 *
 * Abstract methods need to be implemented.
 *
 * Implementation of {@see \Charcoal\Pivot\Interfaces\PivotAwareInterface}
 *
 * ## Required Services
 *
 * - "model/factory" — {@see \Charcoal\Model\ModelFactory}
 * - "model/collection/loader" — {@see \Charcoal\Loader\CollectionLoader}
 */
trait PivotAwareTrait
{
    /**
     * A store of cached pivots, by ID.
     *
     * @var Pivot[] $pivotCache
     */
    protected static $pivotCache = [];

    /**
     * Store a collection of node objects.
     *
     * @var Collection|Pivot[]
     */
    protected $pivots = [];

    /**
     * Store the widget instance currently displaying pivots.
     *
     * @var PivotWidget
     */
    protected $pivotWidget;

    /**
     * Retrieve the objects associated to the current object.
     *
     * @param  string|null $targetObjType Filter the pivots by an object type identifier.
     * @throws InvalidArgumentException If the $targetObjType is invalid.
     * @return Collection|Pivot[]
     */
    public function pivots($targetObjType = null)
    {
        if ($targetObjType === null) {
            throw new InvalidArgumentException('Target object type required.');
        } elseif (!is_string($targetObjType)) {
            throw new InvalidArgumentException('Target object type must be a string.');
        }

        $sourceObjType = $this->objType();
        $sourceObjId   = $this->id();

        $pivotProto = $this->modelFactory()->get(Pivot::class);
        $pivotTable = $pivotProto->source()->table();

        $targetObjProto = $this->modelFactory()->get($targetObjType);
        $targetObjType  = $targetObjProto->objType();
        $targetObjTable = $targetObjProto->source()->table();

        if (!$targetObjProto->source()->tableExists() || !$pivotProto->source()->tableExists()) {
            return [];
        }

        $query = '
            SELECT
                target_obj.*
            FROM
                `'.$targetObjTable.'` AS target_obj
            LEFT JOIN
                `'.$pivotTable.'` AS pivot_obj
            ON
                pivot_obj.target_object_id = target_obj.id
            WHERE
                target_obj.active = 1
            AND
                pivot_obj.source_object_type = "'.$sourceObjType.'"
            AND
                pivot_obj.source_object_id = "'.$sourceObjId.'"
            AND
                pivot_obj.target_object_type = "'.$targetObjType.'"

            ORDER BY pivot_obj.position ASC';

        $loader = $this->collectionLoader();
        $loader->setModel($targetObjProto);

        $collection = $loader->loadFromQuery($query);

        $this->pivots[$targetObjType] = $collection;

        return $this->pivots[$targetObjType];
    }

    /**
     * Determine if the current object has any nodes.
     *
     * @return boolean Whether $this has any nodes (TRUE) or not (FALSE).
     */
    public function hasPivots()
    {
        return !!($this->numPivots());
    }

    /**
     * Count the number of nodes associated to the current object.
     *
     * @return integer
     */
    public function numPivots()
    {
        return count($this->pivots());
    }

    /**
     * Attach an node to the current object.
     *
     * @param PivotableInterface|ModelInterface $obj An object.
     * @return boolean|self
     */
    public function addPivot($obj)
    {
        if (!$obj instanceof PivotableInterface && !$obj instanceof ModelInterface) {
            return false;
        }

        $model = $this->modelFactory()->create(Pivot::class);

        $sourceObjId   = $this->id();
        $sourceObjType = $this->objType();
        $pivotId       = $obj->id();

        $model->setPivotId($pivotId);
        $model->setObjId($sourceObjId);
        $model->setObjType($sourceObjType);

        $model->save();

        return $this;
    }

    /**
     * Detach the current object's child relationships.
     *
     * @deprecated in favour of AttachmentAwareTrait::removeChildJoins()
     * @return boolean
     */
    public function removeJoins()
    {
        $this->logger->warning(
            'PivotAwareTrait::removeJoins() is deprecated. '.
            'Use PivotAwareTrait::removeChildJoins() instead.',
            [ 'package' => 'dominiclord/charcoal-pivot' ]
        );

        $this->removeChildJoins();
    }

    /**
     * Detach the current object's child relationships.
     *
     * @return boolean
     */
    public function removeChildJoins()
    {
        $loader = $this->collectionLoader();
        $loader->reset()
               ->setModel(Pivot::class)
               ->addFilter('source_object_type', $this->objType())
               ->addFilter('source_object_id', $this->id());

        $collection = $loader->load();

        foreach ($collection as $obj) {
            $obj->delete();
        }

        return true;
    }

    /**
     * Retrieve the pivot widget.
     *
     * @return PivotWidget
     */
    protected function pivotWidget()
    {
        return $this->pivotWidget;
    }

    /**
     * Set the pivot widget.
     *
     * @param  PivotWidget $widget The widget displaying pivots.
     * @return string
     */
    protected function setPivotWidget(PivotWidget $widget)
    {
        $this->pivotWidget = $widget;

        return $this;
    }

    // Abstract Methods
    // =========================================================================

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
     * Retrieve the model collection loader.
     *
     * @return \Charcoal\Loader\CollectionLoader
     */
    abstract public function collectionLoader();
}
