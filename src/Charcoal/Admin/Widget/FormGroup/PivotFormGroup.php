<?php

namespace Charcoal\Admin\Widget\FormGroup;

// From 'charcoal-ui'
use Charcoal\Ui\FormGroup\FormGroupInterface;
use Charcoal\Ui\FormGroup\FormGroupTrait;
use Charcoal\Ui\Layout\LayoutAwareInterface;
use Charcoal\Ui\Layout\LayoutAwareTrait;
use Charcoal\Ui\UiItemInterface;
use Charcoal\Ui\UiItemTrait;

// From 'charcoal-pivot'
use Charcoal\Admin\Widget\PivotWidget;

/**
 * Pivot Widget, as form group.
 */
class PivotFormGroup extends PivotWidget implements
    FormGroupInterface,
    UiItemInterface
{
    use FormGroupTrait;
    use UiItemTrait;
}
