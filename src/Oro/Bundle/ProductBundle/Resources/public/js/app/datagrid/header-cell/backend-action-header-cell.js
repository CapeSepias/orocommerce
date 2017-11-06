define(function(require) {
    'use strict';

    var BackendSelectHeaderCell;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var template = require('tpl!oroproduct/templates/datagrid/backend-action-header-cell.html');
    var SelectHeaderCell = require('orodatagrid/js/datagrid/header-cell/action-header-cell');
    var ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');
    var ActionsPanel = require('oroproduct/js/app/datagrid/backend-actions-panel');

    BackendSelectHeaderCell = SelectHeaderCell.extend({
        /** @property */
        autoRender: true,

        /** @property */
        className: 'product-action',

        /** @property */
        tagName: 'div',

        /** @property */
        template: template,

        /**
         * @inheritDoc
         */
        actionsPanel: ActionsPanel,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            BackendSelectHeaderCell.__super__.initialize.apply(this, arguments);
            this.selectState = options.selectState;
            this.massActionsInSticky = options.massActionsInSticky;
            this.listenTo(this.selectState, 'change', _.bind(_.debounce(this.canUse, 50), this));

            ShoppingListCollectionService.shoppingListCollection.done(_.bind(function(collection) {
                this.listenTo(collection, 'change', _.bind(_.debounce(this._onShoppingListsRefresh, 100), this));
            }, this));
        },

        canUse: function(selectState) {
            this[(selectState.isEmpty() && selectState.get('inset')) ? 'disable' : 'enable' ]();
        },

        _onShoppingListsRefresh: function() {
            this.collection.trigger('backgrid:selectNone');
            mediator.trigger('datagrid:doRefresh:' + this.collection.inputName);

            this.render();
        },

        getTemplateData: function() {
            var data = BackendSelectHeaderCell.__super__.getTemplateData.call(this);

            data.massActionsInSticky = this.massActionsInSticky;
            data.actionsLength = this.subview('actionsPanel').actions.length;
            return data;
        },

        render: function() {
            this.$el.empty();
            this.renderActionsPanel();
            return this;
        },

        renderActionsPanel: function() {
            var panel = this.subview('actionsPanel');

            panel.massActionsInSticky =  this.massActionsInSticky;
            if (panel.haveActions()) {
                this.$el.append(this.getTemplateFunction()(this.getTemplateData()));
                panel.setElement(this.$('[data-action-panel]'));
                panel.render();
            }
        }
    });

    return BackendSelectHeaderCell;
});
