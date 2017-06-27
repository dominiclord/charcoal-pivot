<?php

namespace Charcoal\Admin\Widget;

use ArrayIterator;
use RuntimeException;
use InvalidArgumentException;

// From Pimple
use Pimple\Container;

// From Mustache
use Mustache_LambdaHelper as LambdaHelper;

// From 'charcoal-factory'
use Charcoal\Factory\FactoryInterface;

// From 'charcoal-core'
use Charcoal\Loader\CollectionLoader;
use Charcoal\Model\ModelFactory;

// From 'charcoal-object'
use Charcoal\Object\PublishableInterface;
use Charcoal\Object\RoutableInterface;

// From 'charcoal-admin'
use Charcoal\Admin\AdminWidget;
use Charcoal\Admin\Ui\ObjectContainerInterface;
use Charcoal\Admin\Ui\ObjectContainerTrait;

// From 'charcoal-translator'
use Charcoal\Translator\Translation;

/**
 * The Widget for displaying Pivots.
 */
class PivotWidget extends AdminWidget implements
    ObjectContainerInterface
{
    use ObjectContainerTrait {
        ObjectContainerTrait::createOrLoadObj as createOrCloneOrLoadObj;
    }

    /**
     * Store the factory instance for the current class.
     *
     * @var FactoryInterface
     */
    private $widgetFactory;

    /**
     * The widget's title.
     *
     * @var Translation|string|null
     */
    private $title;

    /**
     * The object type identifier.
     *
     * @var string
     */
    protected $sourceObjectType;

    /**
     * The pivot target object type.
     *
     * @var string
     */
    protected $targetObjectType;

    /**
     * Inject dependencies from a DI Container.
     *
     * @param Container $container A dependencies container instance.
     * @return void
     */
    public function setDependencies(Container $container)
    {
        parent::setDependencies($container);

        $this->setModelFactory($container['model/factory']);
        $this->setWidgetFactory($container['widget/factory']);
    }

    /**
     * Create or load the object.
     *
     * @return ModelInterface
     */
    protected function createOrLoadObj()
    {
        $obj = $this->createOrCloneOrLoadObj();

        $obj->setData([
            'pivot_widget' => $this
        ]);

        return $obj;
    }

    /**
     * Pivots by object type.
     *
     * @return Collection
     */
    public function pivots()
    {
        $pivots = $this->obj()->pivots($this->targetObjectType());

        foreach ($pivots as $pivot) {
            if ($pivot instanceof RoutableInterface) {
                $pivot['active'] = $pivot->isActiveRoute();
            } elseif ($pivot instanceof PublishableInterface) {
                $pivot['active'] = $pivot->isPublished();
            }

            yield $pivot;
        }
    }

    /**
     * Determine the number of pivots.
     *
     * @return boolean
     */
    public function hasPivots()
    {
        return count(iterator_to_array($this->pivots()));
    }

    /**
     * Retrieves a Closure that prepends relative URIs with the project's base URI.
     *
     * @return callable
     */
    public function withBaseUrl()
    {
        static $search;

        if ($search === null) {
            $attr = [ 'href', 'link', 'url', 'src' ];
            $uri  = [ '../', './', '/', 'data', 'fax', 'file', 'ftp', 'geo', 'http', 'mailto', 'sip', 'tag', 'tel', 'urn' ];

            $search = sprintf(
                '(?<=%1$s=["\'])(?!%2$s)(\S+)(?=["\'])',
                implode('=["\']|', array_map('preg_quote', $attr)),
                implode('|', array_map('preg_quote', $uri))
            );
        }

        /**
         * Prepend the project's base URI to all relative URIs in HTML attributes (e.g., src, href).
         *
         * @param  string       $text   Text to parse.
         * @param  LambdaHelper $helper For rendering strings in the current context.
         * @return string
         */
        $lambda = function ($text, LambdaHelper $helper) use ($search) {
            $text = $helper->render($text);

            if (preg_match('~'.$search.'~i', $text)) {
                $base = $helper->render('{{ baseUrl }}');
                return preg_replace('~'.$search.'~i', $base.'$1', $text);
            } elseif ($this->baseUrl instanceof \Psr\Http\Message\UriInterface) {
                if ($text && strpos($text, ':') === false && !in_array($text[0], [ '/', '#', '?' ])) {
                    return $this->baseUrl->withPath($text);
                }
            }

            return $text;
        };
        $lambda = $lambda->bindTo($this);

        return $lambda;
    }

// Setters
// =============================================================================

    /**
     * Set the widget's data.
     *
     * @param  array $data The widget data.
     * @return self
     */
    public function setData(array $data)
    {
        /**
         * @todo Kinda hacky, but works with the concept of form.
         *     Should work embeded in a form group or in a dashboard.
         */
        $data = array_merge($_GET, $data);

        parent::setData($data);

        return $this;
    }

    /**
     * Set the widget's pivot source object type.
     *
     * @param string $type The object type.
     * @return self
     */
    public function setSourceObjectType($type)
    {
        $this->sourceObjectType = $type;

        return $this;
    }

    /**
     * Set the widget's pivot target object type.
     *
     * @param string $type The object type.
     * @return self
     */
    public function setTargetObjectType($type)
    {
        $this->targetObjectType = $type;

        return $this;
    }

    /**
     * Set an widget factory.
     *
     * @param FactoryInterface $factory The factory to create widgets.
     * @return self
     */
    protected function setWidgetFactory(FactoryInterface $factory)
    {
        $this->widgetFactory = $factory;

        return $this;
    }

    /**
     * Set the widget's title.
     *
     * @param mixed $title The title for the current widget.
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $this->translator()->translation($title);

        return $this;
    }

    /**
     * Set how many pivots are displayed per page.
     *
     * @param integer $num The number of results to retrieve, per page.
     * @throws InvalidArgumentException If the parameter is not numeric or < 0.
     * @return self
     */
    public function setNumPerPage($num)
    {
        if (!is_numeric($num)) {
            throw new InvalidArgumentException(
                'Num-per-page needs to be numeric.'
            );
        }

        $num = (int)$num;

        if ($num < 0) {
            throw new InvalidArgumentException(
                'Num-per-page needs to be >= 0.'
            );
        }

        $this->numPerPage = $num;

        return $this;
    }

    /**
     * Set the current page listing of pivots.
     *
     * @param integer $page The current page. Start at 0.
     * @throws InvalidArgumentException If the parameter is not numeric or < 0.
     * @return self
     */
    public function setPage($page)
    {
        if (!is_numeric($page)) {
            throw new InvalidArgumentException(
                'Page number needs to be numeric.'
            );
        }

        $page = (int)$page;

        if ($page < 0) {
            throw new InvalidArgumentException(
                'Page number needs to be >= 0.'
            );
        }

        $this->page = $page;

        return $this;
    }

// Getters
// =============================================================================

    /**
     * Retrieve the widget factory.
     *
     * @throws Exception If the widget factory was not previously set.
     * @return FactoryInterface
     */
    public function widgetFactory()
    {
        if (!isset($this->widgetFactory)) {
            throw new RuntimeException(
                sprintf('Widget Factory is not defined for "%s"', get_class($this))
            );
        }

        return $this->widgetFactory;
    }

    /**
     * Retrieve the widget's pivot source object type.
     *
     * @return string
     */
    public function sourceObjectType()
    {
        return $this->sourceObjectType;
    }

    /**
     * Retrieve the widget's pivot target object type.
     *
     * @return string
     */
    public function targetObjectType()
    {
        return $this->targetObjectType;
    }

    /**
     * Retrieve the widget's pivot target object type label.
     *
     * @return Translation|string
     */
    public function dialogTitle()
    {
        $targetObjectProto = $this->modelFactory()->create($this->targetObjectType());
        $label = $targetObjectProto->metadata()->get('labels.create_item');

        if (empty($label)) {
            throw new RuntimeException(
                sprintf('create_item label is not defined for "%s"', get_class($targetObjectProto))
            );
        }

        $label = $this->translator()->translation($label);

        return $label;
    }

    /**
     * Retrieve the widget's title.
     *
     * @return Translation|string|null
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * Retrieve the current widget's options as a JSON object.
     *
     * @return string A JSON string.
     */
    public function widgetOptions()
    {
        $options = [
            'title'              => $this->title(),
            'obj_type'           => $this->obj()->objType(),
            'obj_id'             => $this->obj()->id(),
            'target_object_type' => $this->targetObjectType()
        ];

        return json_encode($options, true);
    }


// Utilities
// =============================================================================

    /**
     * Determine if the widget has an object assigned to it.
     *
     * @return boolean
     */
    public function hasObj()
    {
        return !!($this->obj()->id());
    }
}
