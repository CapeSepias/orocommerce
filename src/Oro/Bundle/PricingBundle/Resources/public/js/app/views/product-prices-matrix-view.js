define(function(require) {
    'use strict';

    var ProductPricesMatrixView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var PricesHelper = require('oropricing/js/app/prices-helper');
    var ScrollView = require('orofrontend/js/app/views/scroll-view');
    var $ = require('jquery');
    var _ = require('underscore');

    ProductPricesMatrixView = BaseView.extend(_.extend({}, ElementsHelper, {
        autoRender: true,

        elements: {
            fields: '[data-name="field__quantity"]:enabled',
            fieldsColumn: '[data-name="field__quantity"]:enabled',
            totalQty: '[data-role="total-quantity"]',
            totalPrice: '[data-role="total-price"]',
            submitButtons: '[data-shoppingList],[data-toggle="dropdown"]'
        },

        elementsEvents: {
            'fields': ['input', '_onQuantityChange']
        },

        total: {
            price: 0,
            quantity: 0,
            rows: {},
            columns: {},
            cells: {}
        },

        prices: null,

        unit: null,

        minValue: 1,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ProductPricesMatrixView.__super__.initialize.apply(this, arguments);
            this.setPrices(options);
            this.initializeElements(options);
            if (_.isDesktop()) {
                this.subview('scrollView', new ScrollView({
                    el: this.el
                }));
            }
            this.updateTotals();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            delete this.prices;
            delete this.total;
            delete this.unit;
            delete this.minValue;

            this.disposeElements();
            ProductPricesMatrixView.__super__.dispose.apply(this, arguments);
        },

        /**
         * Refactoring prices object model
         */
        setPrices: function(options) {
            this.unit = options.unit;
            this.prices = {};

            _.each(options.prices, function(unitPrices, productId) {
                this.prices[productId] = PricesHelper.preparePrices(unitPrices);
            }, this);
        },

        /**
         * Listen input event
         *
         * @param {Event} event
         */
        _onQuantityChange: _.debounce(function(event) {
            this.updateTotal($(event.currentTarget));
            this.render();
        }, 150),

        /**
         * Update all totals
         */
        updateTotals: function() {
            _.each(this.getElement('fields'), function(element) {
                this.updateTotal($(element));
            }, this);
        },

        /**
         * Calculate totals for individual field
         *
         * @param {jQuery} $element
         */
        updateTotal: function($element) {
            var $cell = $element.closest('[data-index]');
            var index = $cell.data('index');
            var productId = $cell.data('product-id');
            var indexKey = index.row + '.' + index.column;

            var cells = this.total.cells;
            var columns = this.total.columns;
            var rows = this.total.rows;

            var cell = cells[indexKey] = this.getTotal(cells, indexKey);
            var column = columns[index.column] = this.getTotal(columns, index.column);
            var row = rows[index.row] = this.getTotal(rows, index.row);

            //remove old values
            this.changeTotal(this.total, cell, -1);
            this.changeTotal(column, cell, -1);
            this.changeTotal(row, cell, -1);

            //recalculate cell total
            cell.quantity = this.getValidQuantity($element.val());
            var quantity = cell.quantity > 0 ? cell.quantity.toString() : '';
            cell.price = PricesHelper.calcTotalPrice(this.prices[productId], this.unit, quantity);
            $element.val(quantity);

            //add new values
            this.changeTotal(this.total, cell);
            this.changeTotal(column, cell);
            this.changeTotal(row, cell);
        },

        /**
         * Get total by key
         *
         * @param {Object} totals
         * @param {String} key
         * @return {Object}
         */
        getTotal: function(totals, key) {
            return totals[key] || {
                quantity: 0,
                price: 0
            };
        },

        /**
         * Change totals by subtotals using modifier
         *
         * @param {Object} totals
         * @param {Object} subtotals
         * @param {Number|null} modifier
         */
        changeTotal: function(totals, subtotals, modifier) {
            modifier = modifier || 1;
            totals.quantity += subtotals.quantity * modifier;
            totals.price += subtotals.price * modifier;
            if (NumberFormatter.formatDecimal(totals.price) === 'NaN') {
                totals.price = 0;
            }
        },

        /**
         * Validate quantity value
         *
         * @param {String} quantity
         * @return {Number}
         */
        getValidQuantity: function(quantity) {
            var val = parseInt(quantity, 10) || 0;

            if (_.isEmpty(quantity)) {
                return 0;
            } else {
                return val < this.minValue ? this.minValue : val;
            }
        },

        /**
         * Update totals
         */
        render: function() {
            this.getElement('totalQty').text(this.total.quantity);
            this.getElement('totalPrice').text(
                NumberFormatter.formatCurrency(this.total.price)
            );

            _.each(_.pick(this.total, 'rows', 'columns'), this.renderSubTotals, this);
        },

        /**
         * Update subtotals
         *
         * @param {Object} totals
         * @param {String} key
         */
        renderSubTotals: function(totals, key) {
            _.each(totals, function(total, index) {
                var $quantity = this.$el.find('[data-' + key + '-quantity="' + index + '"]');
                var $price = this.$el.find('[data-' + key + '-price="' + index + '"]');

                $quantity.toggleClass('valid', total.quantity > 0).html(total.quantity);
                $price.toggleClass('valid', total.price > 0).html(NumberFormatter.formatCurrency(total.price));
            }, this);
        }
    }));
    return ProductPricesMatrixView;
});
