define(function(require) {
    'use strict';

    var GrapesjsEditorView;
    var BaseView = require('oroui/js/app/views/base/view');
    var GrapesJS = require('grapesjs');
    var $ = require('jquery');
    var _ = require('underscore');
    var GrapesJSModules = require('orocms/js/app/views/grapesjs-modules/grapesjs-modules');
    var mediator = require('oroui/js/mediator');
    var BaseModel = require('oroui/js/app/models/base/model');
    var Backbone = require('backbone');

    require('grapesjs-preset-webpage');

    /**
     * Create GrapesJS content builder
     * @type {*|void}
     */
    GrapesjsEditorView = BaseView.extend({
        /**
         * @inheritDoc
         */
        optionNames: BaseView.prototype.optionNames.concat([
            'builderOptions', 'storageManager', 'builderPlugins', 'storagePrefix',
            'currentTheme', 'contextClass', 'canvasConfig'
        ]),

        /**
         * @inheritDoc
         */
        autoRender: true,

        /**
         * @property {GrapesJS.Instance}
         */
        builder: null,

        $builderIframe: null,

        /**
         * Page context class
         * @property {String}
         */
        contextClass: 'body cms-page',

        /**
         * Main builder options
         * @property {Object}
         */
        builderOptions: {
            fromElement: true,
            height: '2000px',
            avoidInlineStyle: true,

            /**
             * Color picker options
             * @property {Object}
             */
            colorPicker: {
                appendTo: 'body',
                showPalette: false
            }
        },

        /**
         * Storage prefix
         * @property {String}
         */
        storagePrefix: 'gjs-',

        /**
         * Storage options
         * @property {Object}
         */
        storageManager: {
            autosave: false,
            autoload: false
        },

        /**
         * Canvas options
         * @property {Object}
         */
        canvasConfig: {},

        /**
         * Style manager options
         * @property {Object}
         */
        styleManager: {
            clearProperties: 1
        },

        /**
         * Asset manager settings
         * @property {Object}
         */
        assetManagerConfig: {
            embedAsBase64: 1
        },

        /**
         * Themes list
         * @property {Array}
         */
        themes: [],
        /**
         * List of grapesjs plugins
         * @property {Object}
         */
        builderPlugins: {
            'gjs-preset-webpage': {
                aviaryOpts: false,
                filestackOpts: null,
                blocksBasicOpts: {
                    flexGrid: 1
                },
                customStyleManager: GrapesJSModules.getModule('style-manager'),
                modalImportContent: function(editor) {
                    return editor.getHtml() + '<style>' + editor.getCss() + '</style>';
                }
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function GrapesjsEditorView() {
            GrapesjsEditorView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         * @param options
         */
        initialize: function(options) {
            GrapesjsEditorView.__super__.initialize.apply(this, arguments);
            this.themes = options.themes;
        },

        /**
         * @inheritDoc
         */
        render: function() {
            this.initBuilder();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.builderUndelegateEvents();

            GrapesjsEditorView.__super__.dispose.call(this);
        },

        /**
         * @TODO Should refactored
         */
        getContainer: function() {
            var $editor = $('<div id="grapesjs" />');
            $editor.html(
                this.$el.val().replace(/(\[component-id-view([\d]*)\])/g, '')
            );
            this.$el.parent().append($editor);

            this.$el.hide();

            this.$container = $editor;

            return $editor.get(0);
        },

        /**
         * Initialize builder instance
         */
        initBuilder: function() {
            this.builder = GrapesJS.init(_.extend(
                {}
                , {
                    avoidInlineStyle: 1,
                    container: this.getContainer()
                }
                , this._prepareBuilderOptions()));

            mediator.trigger('grapesjs:created', this.builder);

            this.builderDelegateEvents();

            GrapesJSModules.call('components', {
                builder: this.builder
            });

            GrapesJSModules.call('extension', {
                view: this,
                builder: this.builder
            });
        },

        /**
         * Add builder event listeners
         */
        builderDelegateEvents: function() {
            this.$el.closest('form').on(
                'keyup' + this.eventNamespace() + ' keypress' + this.eventNamespace()
                , _.bind(function(e) {
                    var keyCode = e.keyCode || e.which;
                    if (keyCode === 13 && this.$container.get(0).contains(e.target)) {
                        e.preventDefault();
                        return false;
                    }
                }, this));

            this.builder.on('load', _.bind(this._onLoadBuilder, this));
            this.builder.on('update', _.bind(this._onUpdatedBuilder, this));
            this.builder.on('component:update', _.bind(this._onComponentUpdatedBuilder, this));
            this.builder.on('changeTheme', _.bind(this._updateTheme, this));

            // Fix reload form when click export to zip dialog
            this.builder.on('run:export-template', _.bind(function() {
                $(this.builder.Modal.getContentEl())
                    .find('.gjs-btn-prim').bind('click', _.bind(function(e) {
                        e.preventDefault();
                    }, this));
            }, this));
        },

        /**
         * Remove builder event listeners
         */
        builderUndelegateEvents: function() {
            this.$el.closest('form').off(this.eventNamespace());
            this.builder.off();
        },

        /**
         * Get current theme
         * @returns {Object}
         */
        getCurrentTheme: function() {
            return _.find(this.themes, function(theme) {
                return theme.active;
            });
        },

        /**
         * Set active state for button
         * @param panel {String}
         * @param name {String}
         */
        setActiveButton: function(panel, name) {
            this.builder.Commands.run(name);
            var button = this.builder.Panels.getButton(panel, name);

            button.set('active', true);
        },

        /**
         * Get editor content
         * @returns {String}
         */
        getEditorContent: function() {
            return this.builder.getHtml() + '<style>' + this.builder.getCss() + '</style>';
        },

        /**
         * Add wrapper classes for iframe with content
         */
        _addClassForFrameWrapper: function() {
            $(this.builder.Canvas.getFrameEl().contentDocument).find('#wrapper').addClass(this.contextClass);
        },

        /**
         * Onload builder handler
         * @private
         */
        _onLoadBuilder: function() {
            GrapesJSModules.call('panel-manager', {
                builder: this.builder,
                themes: this.themes
            });

            GrapesJSModules.call('devices', {
                builder: this.builder
            });

            this.setActiveButton('options', 'sw-visibility');
            this._addClassForFrameWrapper();

            mediator.trigger('grapesjs:loaded', this.builder);
        },

        /**
         * Update builder handler
         * @private
         */
        _onUpdatedBuilder: function() {
            this._getCSSBreakpoint();
            mediator.trigger('grapesjs:updated', this.builder);
        },

        /**
         * Update components builder handler
         * @param state
         * @private
         */
        _onComponentUpdatedBuilder: function(state) {
            this._updateInitialField();
            mediator.trigger('grapesjs:components:updated', state);
        },

        /**
         * Update theme view in grapes iframe
         * @param selected {String}
         * @private
         */
        _updateTheme: function(selected) {
            _.each(this.themes, function(theme) {
                theme.active = theme.name === selected;
            });

            var theme = _.find(this.themes, function(theme) {
                return theme.active;
            });

            var style = this.builder.Canvas.getFrameEl().contentDocument.head.querySelector('link');

            style.href = theme.stylesheet;
        },

        /**
         * Update source textarea
         * @private
         */
        _updateInitialField: function() {
            this.$el.val(this.getEditorContent()).trigger('change');
        },

        /**
         * Collect and compare builder options
         * @returns {GrapesjsEditorView.builderOptions|{fromElement}}
         * @private
         */
        _prepareBuilderOptions: function() {
            _.extend(this.builderOptions
                , this._getPlugins()
                , this._getStorageManagerConfig()
                , this._getCanvasConfig()
                , this._getStyleManagerConfig()
                , this._getAssetConfig()
            );

            return this.builderOptions;
        },

        /**
         * Get extended Storage Manager config
         * @returns {{storageManager: (*|void)}}
         * @private
         */
        _getStorageManagerConfig: function() {
            return {
                storageManager: _.extend({}, this.storageManager, {
                    id: this.storagePrefix
                })
            };
        },

        /**
         * Get extended Style Manager config
         * @returns {{styleManager: *}}
         * @private
         */
        _getStyleManagerConfig: function() {
            return {
                styleManager: this.styleManager
            };
        },

        /**
         * Get extended Canvas config
         * @returns {{canvasCss: string, canvas: {styles: (*|string)[]}}}
         * @private
         */
        _getCanvasConfig: function() {
            var theme = this.getCurrentTheme();
            return _.extend({}, this.canvasConfig, {
                canvasCss: '.gjs-comp-selected { outline: 3px solid #0c809e !important; ' +
                    'outline-offset: 0 !important; }' +
                    '#wrapper { padding: 3px; }' +
                    '* ::-webkit-scrollbar { width: 5px}' +
                    '::-webkit-scrollbar-track { background: #f3f3f3 }' +
                    '::-webkit-scrollbar-thumb { background: #e3e3e4 }',
                canvas: {
                    styles: [theme.stylesheet]
                },
                protectedCss: []
            });
        },

        /**
         * Get asset manager configuration
         * @returns {*|void}
         * @private
         */
        _getAssetConfig: function() {
            return {
                assetManager: this.assetManagerConfig
            };
        },

        /**
         * Get plugins list with options
         * @returns {{plugins: *, pluginsOpts: (GrapesjsEditorView.builderPlugins|{"gjs-preset-webpage"})}}
         * @private
         */
        _getPlugins: function() {
            return {
                plugins: _.keys(this.builderPlugins),
                pluginsOpts: this.builderPlugins
            };
        }
    });

    return GrapesjsEditorView;
});
