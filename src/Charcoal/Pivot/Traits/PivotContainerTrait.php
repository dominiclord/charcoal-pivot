<?php
namespace Charcoal\Pivot\Traits;

use \UnexpectedValueException;

// From 'charcoal-core'
use \Charcoal\Model\ModelInterface;
use \Charcoal\Loader\CollectionLoader;

// From 'charcoal-translation'
use \Charcoal\Translation\TranslationString;

// From 'charcoal-pivot'
use \Charcoal\Pivot\Interfaces\PivotContainerInterface;
use \Charcoal\Pivot\Interfaces\PivotableInterface;

use \Charcoal\Pivot\Object\Pivot;

/**
 * Provides support for relationships between objects.
 *
 * Used by objects that can have a pivot to other objects.
 * This is the glue between the {@see Pivot} object and the source object.
 *
 * Abstract method needs to be implemented.
 *
 * Implementation of {@see \Charcoal\Pivot\Interfaces\PivotAwareInterface}
 *
 * ## Required Services
 *
 * - "model/factory" — {@see \Charcoal\Model\ModelFactory}
 * - "model/collection/loader" — {@see \Charcoal\Loader\CollectionLoader}
 */
trait PivotContainerTrait
{
    /**
     * The container's configuration.
     *
     * @var array
     */
    protected $pivotConfig;

    /**
     * The container's accepted pivot types.
     *
     * @var array
     */
    protected $pivotableObjects;

    /**
     * The container's group identifier.
     *
     * The group is used to create multiple widget instance on the same page.
     *
     * @var string
     */
    protected $group;

    /**
     * Alias of {@see \Charcoal\Source\StorableTrait::id()}
     *
     * Retrieve the container's (unique) ID; useful when templating the container's pivots.
     *
     * @return mixed
     */
    public function containerId()
    {
        return $this->id();
    }

    /**
     * Gets the pivots config from
     * the object metadata.
     *
     * @return array
     */
    public function pivotConfig()
    {
        if ($this->pivotConfig === null) {
            $metadata = $this->metadata();
            $this->pivotConfig = (isset($metadata['pivots']) ? $metadata['pivots'] : []);
        }

        return $this->pivotConfig;
    }

    /**
     * Returns pivotable objects
     *
     * @return array Pivotable Objects
     */
    public function pivotableObjects()
    {
        if ($this->pivotableObjects) {
            return $this->pivotableObjects;
        }

        $this->pivotableObjects = [];

        $cfg = $this->pivotConfig();
        if (isset($cfg['pivotable_objects'])) {
            foreach ($cfg['pivotable_objects'] as $pivotType => $pivotMeta) {
                // Disable an pivotable model
                if (isset($pivotMeta['active']) && !$pivotMeta['active']) {
                    continue;
                }

                // Useful for replacing a pre-defined pivot type
                if (isset($pivotMeta['pivot_type'])) {
                    $pivotType = $pivotMeta['pivot_type'];
                } else {
                    $pivotMeta['pivot_type'] = $pivotType;
                }

                // Alias
                $pivotMeta['pivotType'] = $pivotMeta['pivot_type'];

                if (isset($pivotMeta['label']) && TranslationString::isTranslatable($pivotMeta['label'])) {
                    $pivotMeta['label'] = new TranslationString($pivotMeta['label']);
                } else {
                    $pivotMeta['label'] = ucfirst(basename($pivotType));
                }

                $this->pivotableObjects[] = $pivotMeta;
            }
        }

        return $this->pivotableObjects;
    }

    /**
     * Returns true
     * @return boolean True.
     */
    public function isPivotContainer()
    {
        return true;
    }

    /**
     * Objects metadata.
     * Default behavior in Content class.
     *
     * @return string
     */
    abstract function metadata();
}
