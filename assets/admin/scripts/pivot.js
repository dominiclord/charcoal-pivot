/* globals commonL10n,pivotWidgetL10n */
/**
 * Pivot widget
 * You can associate a specific object to another
 * using this widget.
 *
 * @see widget.js (Charcoal.Admin.Widget)
 */
Charcoal.Admin.Widget_Pivot = function ()
{
    this.dirty = false;
    return this;
};

Charcoal.Admin.Widget_Pivot.prototype = Object.create(Charcoal.Admin.Widget.prototype);
Charcoal.Admin.Widget_Pivot.prototype.constructor = Charcoal.Admin.Widget_Pivot;
Charcoal.Admin.Widget_Pivot.prototype.parent = Charcoal.Admin.Widget.prototype;

/**
 * Called upon creation
 * Use as constructor
 * Access available configurations with `this.opts()`
 * Encapsulate all events within the current widget
 * element: `this.element()`.
 *
 *
 * @see Component_Manager.render()
 * @return {thisArg} Chainable
 */
Charcoal.Admin.Widget_Pivot.prototype.init = function ()
{
    // Necessary assets.
    if (typeof $.fn.sortable !== 'function') {
        var url = 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js';
        Charcoal.Admin.loadScript(url, this.init.bind(this));

        return this;
    }
    // var config = this.opts();
    var $container = this.element().find('.js-pivot-sortable .js-grid-container');

    this.element().on('hidden.bs.collapse', '[data-toggle="collapse"]', function () {
        $container.sortable('refreshPositions');
    });

    $container.sortable({
        handle:      '[draggable="true"]',
        placeholder: 'panel js-pivot-placeholder',
        start:       function (event, ui) {
            var $heading     = ui.item.children('.panel-heading'),
                $collapsible = $heading.find('[data-toggle="collapse"]');

            if (!$collapsible.hasClass('collapsed')) {
                ui.item.children('.panel-collapse').collapse('hide');
            }
        }
    }).disableSelection();

    this.listeners();
    return this;
};

/**
 * Check if the widget has something a dirty state that needs to be saved.
 * @return Boolean     Widget dirty of not.
 */
Charcoal.Admin.Widget_Pivot.prototype.is_dirty = function ()
{
    return this.dirty;
};

/**
 * Set the widget to dirty or not to prevent unnecessary save
 * action.
 * @param Boolean bool Self explanatory.
 * @return Add_Pivot_Widget Chainable.
 */
Charcoal.Admin.Widget_Pivot.prototype.set_dirty_state = function (bool)
{
    this.dirty = bool;
    return this;
};

/**
 * Bind listeners
 *
 * @return {thisArg} Chainable
 */
Charcoal.Admin.Widget_Pivot.prototype.listeners = function ()
{
    // Scope
    var that = this;

    // Prevent multiple binds
    this.element()
        .off('click')
        .on('click.charcoal.pivots', '.js-add-pivot', function (e) {
            e.preventDefault();
            var type = $(this).data('type');
            if (!type) {
                return false;
            }
            var title = $(this).data('title') || pivotWidgetL10n.editObject;
            that.create_pivot_dialog(type, title, 0, function (response) {
                if (response.success) {
                    response.obj.id = response.obj_id;

                    that.add(response.obj);
                    that.create_pivot(function () {
                        that.reload();
                    });
                }
            });
        })
        .on('click.charcoal.pivots', '.js-pivot-actions a', function (event) {
            var $obj   = $(event.currentTarget),
                action = $obj.data('action');

            if (!action) {
                return;
            }

            event.preventDefault();

            switch (action) {
                case 'delete':
                    return that.delete_obj($obj);
                case 'unlink':
                    return that.unlink_obj($obj);
            }
        });
};

/**
 * Confirm object deletion.
 */
Charcoal.Admin.Widget_Pivot.prototype.delete_obj = function ($obj)
{
    var that    = this,
        pivotId = $obj.data('pivot-id');

    if (!pivotId) {
        return;
    }

    var $item    = $obj.closest('.js-pivot');
    var $widget  = $obj.closest('.js-grid-container');
    var template = {
        '[[ childName ]]':  $item.data('name') || $item.data('type'),
        '[[ parentName ]]': $widget.data('parent-name') || $widget.data('parent-type')
    };

    that.confirm({
        title:      pivotWidgetL10n.relationship,
        message:    pivotWidgetL10n.confirmDeletion.replaceMap(template),
        btnOKLabel: pivotWidgetL10n.delete,
        callback:   function (result) {
            if (result) {
                that.remove_pivot(pivotId, true, function () {
                    that.reload();
                });
            }
        }
    });
};

/**
 * Confirm object unlinking.
 */
Charcoal.Admin.Widget_Pivot.prototype.unlink_obj = function ($obj)
{
    var that = this,
        pivotId = $obj.data('pivot-id');

    if (!pivotId) {
        return;
    }

    var $item    = $obj.closest('.js-pivot');
    var $widget  = $obj.closest('.js-grid-container');
    var template = {
        '[[ childName ]]':  $item.data('name') || $item.data('type'),
        '[[ parentName ]]': $widget.data('parent-name') || $widget.data('parent-type')
    };

    that.confirm({
        title:      pivotWidgetL10n.relationship,
        message:    pivotWidgetL10n.confirmDetach.replaceMap(template),
        btnOKLabel: pivotWidgetL10n.detach,
        callback:   function (result) {
            if (result) {
                that.remove_pivot(pivotId, false, function () {
                    that.reload();
                });
            }
        }
    });
};

Charcoal.Admin.Widget_Pivot.prototype.create_pivot_dialog = function (type, title, id, cb)
{
    // Id = EDIT mod.
    if (!id) {
        id = 0;
    }

    var data = {
        title:          title,
        size:           BootstrapDialog.SIZE_WIDE,
        cssClass:       '-quick-form',
        widget_type:    'charcoal/admin/widget/quickForm',
        widget_options: {
            obj_type: type,
            obj_id:   id
        }
    };
    this.dialog(data, function (response) {
        if (response.success) {
            // Call the quickForm widget js.
            // Really not a good place to do that.
            if (!response.widget_id) {
                return false;
            }

            Charcoal.Admin.manager().add_widget({
                id:   response.widget_id,
                type: 'charcoal/admin/widget/quick-form',
                data: {
                    obj_type: type
                },
                obj_id: id,
                save_callback: function (response) {
                    cb(response);
                    BootstrapDialog.closeAll();
                }
            });

            // Re render.
            // This is not good.
            Charcoal.Admin.manager().render();
        }
    });
};

/**
 * This should use mustache templating. That'd be great.
 * @return {[type]} [description]
 */
Charcoal.Admin.Widget_Pivot.prototype.add = function (obj)
{
    if (!obj) {
        return false;
    }

    // There is something to save.
    this.set_dirty_state(true);
    var $template = this.element().find('.js-pivot-template').clone();
    $template.find('.js-pivot').data('id', obj.id).data('type', obj.type);
    this.element().find('.js-pivot-sortable').find('.js-grid-container').append($template);

    return this;
};

/**
 * [save description]
 * @return {[type]} [description]
 */
Charcoal.Admin.Widget_Pivot.prototype.save = function ()
{
    if (this.is_dirty()) {
        return false;
    }

    var that = this;

    // Create create_pivot from current list.
    this.create_pivot(function () {
        that.reload();
    });
};

Charcoal.Admin.Widget_Pivot.prototype.create_pivot = function (cb)
{
    // Scope
    var that = this;

    var opts = that.opts();
    var data = {
        obj_type: opts.data.obj_type,
        obj_id: opts.data.obj_id,
        target_object_type: opts.data.target_object_type,
        pivots: []
    };

    this.element().find('.js-pivot-container').find('.js-pivot').each(function (i) {
        var $this = $(this);
        var id = $this.data('id');

        data.pivots.push({
            target_object_id: id,
            position: i
        });
    });

    var xhr = $.post('pivot/create', data, function (response) {
        if ($.type(response) === 'object' && $.type(response.feedbacks) === 'array') {
            Charcoal.Admin.feedback(response.feedbacks).dispatch();
        }

        if (typeof cb === 'function') {
            cb();
        }

        that.set_dirty_state(false);
    }, 'json');

    xhr.fail(function (jqXHR, textStatus, errorThrown) {
        if (jqXHR.responseJSON && jqXHR.responseJSON.feedbacks) {
            Charcoal.Admin.feedback(jqXHR.responseJSON.feedbacks);
        } else {
            var message = pivotWidgetL10n.syncFailed;
            var error   = errorThrown || commonL10n.errorOccurred;

            Charcoal.Admin.feedback([{
                message: commonL10n.errorTemplate.replaceMap({
                    '[[ errorMessage ]]': message,
                    '[[ errorThrown ]]':  error
                }),
                level:   'error'
            }]);
        }

        Charcoal.Admin.feedback().dispatch();
    });
};

/**
 * Detach relationship.
 *
 * @param  {string|integer} pivotId    - The pivot ID.
 * @param  {boolean}        [remove]   - Whether to delete the related object.
 * @param  {function}       [callback] - Routine to be called after detaching the object.
 * @return {boolean}
 */
Charcoal.Admin.Widget_Pivot.prototype.remove_pivot = function (pivotId, remove, callback)
{
    if (!pivotId) {
        return false;
    }

    if ($.type(remove) === 'function') {
        callback = remove;
    }

    if ($.type(remove) !== 'boolean') {
        remove = false;
    }

    if ($.type(callback) !== 'function') {
        callback = $.noop;
    }

    // Scope
    var that = this;
    var data = {
        pivot_id:   pivotId,
        delete_obj: (remove & 1)
    };

    var xhr = $.post('pivot/remove', data, function (response) {
        if ($.type(response) === 'object' && $.type(response.feedbacks) === 'array') {
            Charcoal.Admin.feedback(response.feedbacks).dispatch();
        }

        if (typeof callback === 'function') {
            callback();
        }

        that.set_dirty_state(false);
    }, 'json');

    xhr.fail(function (jqXHR, textStatus, errorThrown) {
        if (jqXHR.responseJSON && jqXHR.responseJSON.feedbacks) {
            Charcoal.Admin.feedback(jqXHR.responseJSON.feedbacks);
        } else {
            var message = pivotWidgetL10n.detachFailed;
            var error   = errorThrown || commonL10n.errorOccurred;

            Charcoal.Admin.feedback([{
                message: commonL10n.errorTemplate.replaceMap({
                    '[[ errorMessage ]]': message,
                    '[[ errorThrown ]]':  error
                }),
                level:   'error'
            }]);
        }

        Charcoal.Admin.feedback().dispatch();
    });
};

/**
 * Widget options as output by the widget itself.
 * @return {[type]} [description]
 */
Charcoal.Admin.Widget_Pivot.prototype.widget_options = function ()
{
    return this.opts('widget_options');
};
