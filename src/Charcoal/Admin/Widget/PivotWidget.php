<?php

namespace Charcoal\Admin\Widget;

use \ArrayIterator;
use \RuntimeException;
use \InvalidArgumentException;

use \Pimple\Container;

// From 'bobthecow/mustache.php'
use \Mustache_LambdaHelper as LambdaHelper;

// From 'charcoal-factory'
use \Charcoal\Factory\FactoryInterface;

// From 'charcoal-core'
use \Charcoal\Loader\CollectionLoader;
use \Charcoal\Model\ModelFactory;

// From 'charcoal-admin'
use \Charcoal\Admin\AdminWidget;
use \Charcoal\Admin\Ui\ObjectContainerInterface;
use \Charcoal\Admin\Ui\ObjectContainerTrait;

// From 'charcoal-translation'
use \Charcoal\Translation\TranslationString;

// From 'charcoal-pivot'
use \Charcoal\Pivot\Interfaces\PivotContainerInterface;

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
     * @var TranslationString|string[]
     */
    private $title;

    /**
     * The object type identifier.
     *
     * @var string
     */
    protected $pivotObjType;

    /**
     * The pivot heading (property or template).
     *
     * @var string[]|string
     */
    protected $pivotHeading;

    /**
     * The pivot preview (property or template).
     *
     * @var string[]|string
     */
    protected $pivotPreview;

    /**
     * Flag wether the pivot heading should be displayed.
     *
     * @var boolean
     */
    private $showPivotHeading = true;

    /**
     * Flag wether the pivot preview should be displayed.
     *
     * @var boolean
     */
    private $showPivotPreview = false;

    /**
     * The widgets's available pivot types.
     *
     * @var array
     */
    protected $pivotableObjects;

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
     * Pivot types with their collections.
     *
     * @return array
     */
    public function pivotTypes()
    {
        $pivotableObjects = $this->pivotableObjects();
        $out = [];

        if (!$pivotableObjects) {
            return $out;
        }

        $i = 0;
        foreach ($pivotableObjects as $pivotType => $pivotMeta) {
            $i++;
            $label = $pivotMeta['label'];

            $out[] = [
                'ident'  => $this->createIdent($pivotType),
                'label'  => $label,
                'val'    => $pivotType,
                'active' => ($i == 1)
            ];
        }

        return $out;
    }

    /**
     * Pivot by groups.
     *
     * @return Collection
     */
    public function pivots()
    {
        $pivots = $this->obj()->pivots($this->group());

        foreach ($pivots as $pivot) {
            // $GLOBALS['widget_template'] = (string)$pivot->rawPreview();

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
     * @param array|Traversable $data The widget data.
     * @return self
     */
    public function setData($data)
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
     * Set the pivot's default heading.
     *
     * @param  string $heading The pivot heading template.
     * @throws InvalidArgumentException If provided argument is not of type 'string'.
     * @return string[]|string
     */
    public function setPivotHeading($heading)
    {
        $this->pivotHeading = $heading;

        return $this;
    }

    /**
     * Set the pivot's default preview.
     *
     * @param  string $preview The pivot preview template.
     * @throws InvalidArgumentException If provided argument is not of type 'string'.
     * @return string[]|string
     */
    public function setPivotPreview($preview)
    {
        $this->pivotPreview = $preview;

        return $this;
    }

    /**
     * Set whether to show a heading for each related object.
     *
     * @param boolean $show The show heading flag.
     * @return string
     */
    public function setShowPivotHeading($show)
    {
        $this->showPivotHeading = !!$show;

        return $this;
    }

    /**
     * Set whether to show a preview for each related object.
     *
     * @param boolean $show The show preview flag.
     * @return string
     */
    public function setShowPivotPreview($show)
    {
        $this->showPivotPreview = !!$show;

        return $this;
    }

    /**
     * Set the widget's pivot grouping.
     *
     * Prevents the pivot from deleting all non related pivots.
     *
     * @param string $id The group identifier.
     * @return self
     */
    public function setGroup($id)
    {
        $this->group = $id;

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
        if (TranslationString::isTranslatable($title)) {
            $this->title = new TranslationString($title);
        } else {
            $this->title = null;
        }

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

    /**
     * Set the widget's available pivot types.
     *
     * Specificy the object as a KEY (ident) to whom you
     * can add filters, label and orders.
     *
     * @param array|PivotableInterface[] $pivotableObjects A list of available pivot types.
     * @return self
     */
    public function setPivotableObjects($pivotableObjects)
    {
        if (!$pivotableObjects) {
            return false;
        }

        $out = [];
        foreach ($pivotableObjects as $pivotType => $pivotMeta) {
            $label = '';
            $filters = [];
            $orders = [];
            $numPerPage = 0;
            $page = 1;
            $pivotOption = [ 'label', 'filters', 'orders', 'num_per_page', 'page' ];
            $pivotData = array_diff_key($pivotMeta, $pivotOption);

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

            if (isset($pivotMeta['label'])) {
                if (TranslationString::isTranslatable($pivotMeta['label'])) {
                    $label = new TranslationString($pivotMeta['label']);
                }
            }

            if (isset($pivotMeta['filters'])) {
                $filters = $pivotMeta['filters'];
            }

            if (isset($pivotMeta['orders'])) {
                $orders = $pivotMeta['orders'];
            }

            if (isset($pivotMeta['num_per_page'])) {
                $numPerPage = $pivotMeta['num_per_page'];
            }

            if (isset($pivotMeta['page'])) {
                $page = $pivotMeta['page'];
            }

            $out[$pivotType] = [
                'label'      => $label,
                'filters'    => $filters,
                'orders'     => $orders,
                'page'       => $page,
                'numPerPage' => $numPerPage,
                'data'       => $pivotData
            ];
        }

        $this->pivotableObjects = $out;

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
     * Retrieve the pivot's default heading.
     *
     * @return string|null
     */
    public function pivotHeading()
    {
        return $this->pivotHeading;
    }

    /**
     * Retrieve the pivot's default preview.
     *
     * @return string|null
     */
    public function pivotPreview()
    {
        return $this->pivotPreview;
    }

    /**
     * Determine if the widget displays a heading for each related objects.
     *
     * @return boolean
     */
    public function showPivotHeading()
    {
        if (!$this->showPivotHeading && !$this->showPivotPreview()) {
            return true;
        }

        return $this->showPivotHeading;
    }

    /**
     * Determine if the widget displays a preview for each related objects.
     *
     * @return boolean
     */
    public function showPivotPreview()
    {
        return $this->showPivotPreview;
    }

    /**
     * Retrieve the widget's pivot grouping.
     *
     * @return string
     */
    public function group()
    {
        return $this->group;
    }

    /**
     * Retrieve the widget's title.
     *
     * @return TranslationString|string[]
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * Retrieve the widget's available pivot types.
     *
     * @return array
     */
    public function pivotableObjects()
    {
        if ($this->pivotableObjects === null) {
            $metadata = $this->obj()->metadata();

            if (isset($metadata['pivots']['pivotable_objects'])) {
                $this->setPivotableObjects($metadata['pivots']['pivotable_objects']);
            } else {
                $this->pivotableObjects = [];
            }
        }

        return $this->pivotableObjects;
    }

    /**
     * Retrieve the current widget's options as a JSON object.
     *
     * @return string A JSON string.
     */
    public function widgetOptions()
    {
        $options = [
            'pivotable_objects'      => $this->pivotableObjects(),
            'pivot_heading'      => $this->pivotHeading(),
            'pivot_preview'      => $this->pivotPreview(),
            'show_pivot_heading' => ( $this->showPivotHeading() ? 1 : 0 ),
            'show_pivot_preview' => ( $this->showPivotPreview() ? 1 : 0 ),
            'title'                   => $this->title(),
            'obj_type'                => $this->obj()->objType(),
            'obj_id'                  => $this->obj()->id(),
            'group'                   => $this->group()
        ];

        return json_encode($options, true);
    }


// Utilities
// =============================================================================

    /**
     * Generate an HTML-friendly identifier.
     *
     * @param  string $string A dirty string to filter.
     * @return string
     */
    public function createIdent($string)
    {
        return preg_replace('~/~', '-', $string);
    }

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
