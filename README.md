Charcoal Pivot
====================

Adds support for working with relationships between models using an intermediate object.

## Concept

A **source** model has one or many relationships with a **target** model. These relationships are stored in an intermediate **pivot** model.

## Models

The `Pivot` Model extends AbstractModel (`charcoal-base`) and implements some new properties:
    - `source_object_id`
    - `source_object_type`
    - `target_object_id`
    - `target_object_type`

## Widgets

The module provides its own Admin widgets namespaced as Charcoal\Admin.

## Configuration

Add the View and Metadata paths in `config/config.json`.
```json
"metadata": {
    "paths": [
        "...",
        "vendor/dominiclord/charcoal-pivot/metadata/"
    ]
},
"view": {
    "paths": [
        "...",
        "vendor/dominiclord/charcoal-pivot/templates/"
    ]
}
```

Add the necessary Action routes in `config/admin.json` config file.
```json
"routes": {
    "actions": {
        "pivot/join": {
            "ident": "charcoal/admin/action/pivot/join",
            "methods": [ "POST" ]
        },
        "pivot/add-join": {
            "ident": "charcoal/admin/action/pivot/add-join",
            "methods": [ "POST" ]
        },
        "pivot/remove-join": {
            "ident": "charcoal/admin/action/pivot/remove-join",
            "methods": [ "POST" ]
        }
    }
}
```

### Usage

Your models need to know that they may have relationships to other models. To do that, use and implement the _PivotAware_ concept:
```php
use \Charcoal\Pivot\Traits\PivotAwareTrait;
use \Charcoal\Pivot\Interfaces\PivotAwareInterface;
```

In your **source** model metadata, add the widget configuration in the default form group (see example below).
```json
"forms": {
    "default": {
        "groups": {
            "[...]",
            "target_object_pivot_group": {
                "priority": 10,
                "show_header": false,
                "title": "Target Objects",
                "type": "charcoal/admin/widget/form-group/pivot",
                "template": "charcoal/admin/widget/form-group/pivot",
                "group": "my/namespace/target-object-type",
                "pivotable_objects": {
                    "my/namespace/target-object-type": {
                        "label": "Target Object Name"
                    }
                }
            },
            "[...]"
        }
    }
}
```

To create a new **pivot** model, your **target** model needs to provide a quick form.
```json
"forms": {
    "project_name.quick": {
        "group_display_mode": "tab",
        "groups": {
            "body": {
                "title": "Target Object Information",
                "show_header": false,
                "properties": [
                    "title"
                ],
                "layout": {
                    "structure": [
                        { "columns": [ 1 ] }
                    ]
                }
            }
        }
    }
},
"default_quick_form": "project_name.quick",
```

Hooks allow the **source** model to remove unnecessary relationships when deleted.
```php
public function preDelete()
{
    // PivotAwareTrait
    $this->removeJoins();
    return parent::preDelete();
}
```

## Dependencies

-   [`PHP 5.5+`](http://php.net)
-   [`locomotivemtl/charcoal-core`](https://github.com/locomotivemtl/charcoal-core)
-   [`locomotivemtl/charcoal-base`](https://github.com/locomotivemtl/charcoal-base)
-   [`locomotivemtl/charcoal-admin`](https://github.com/locomotivemtl/charcoal-admin)
-   [`locomotivemtl/charcoal-ui`](https://github.com/locomotivemtl/charcoal-ui)
-   [`locomotivemtl/charcoal-translation`](https://github.com/locomotivemtl/charcoal-translation)
