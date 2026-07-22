"use strict";
(function ($) {

    var pluginName = 'KTDatatable';
    var pfx = '';

    $.fn[pluginName] = $.fn[pluginName] || {};

    /**
     * @param datatable Main datatable plugin instance
     * @param options Extension options
     * @returns {*}
     */
    $.fn[pluginName].checkbox = function (datatable, options) {
        var Extension = {
            selectedAllRows: false,
            selectedRows: [],
            unselectedRows: [],

            init: function () {
                if (Extension.selectorEnabled()) {
                    // reset
                    datatable.setDataSourceParam(options.vars.selectedAllRows, false);
                    datatable.stateRemove('checkbox');

                    // requestIds is not null
                    if (options.vars.requestIds) {
                        // request ids in response
                        datatable.setDataSourceParam(options.vars.requestIds, true);
                    }

                    // remove selected checkbox on datatable reload
                    $(datatable).on(pfx + 'datatable-on-reloaded', function () {
                        datatable.stateRemove('checkbox');
                        datatable.setDataSourceParam(options.vars.selectedAllRows, false);
                        Extension.selectedAllRows = false;
                        Extension.selectedRows = [];
                        Extension.unselectedRows = [];
                    });

                    // select all on extension init
                    Extension.selectedAllRows = datatable.getDataSourceParam(options.vars.selectedAllRows);

                    $(datatable).on(pfx + 'datatable-on-layout-updated', function (e, args) {
                        if (args.table != $(datatable.wrap).attr('id')) {
                            return;
                        }
                        datatable.ready(function () {
                            Extension.initVars();
                            Extension.initEvent();
                            Extension.initSelect();
                        });
                    });

                    $(datatable).on(pfx + 'datatable-on-check', function (e, ids) {
                        ids.forEach(function (id) {
                            Extension.selectedRows.push(id);
                            // // remove from unselected rows
                            Extension.unselectedRows = Extension.remove(Extension.unselectedRows, id);
                        });
                        var storage = {};
                        storage['selectedRows'] = unique(Extension.selectedRows);
                        storage['unselectedRows'] = unique(Extension.unselectedRows);
                        datatable.stateKeep('checkbox', storage);
                    });
                    $(datatable).on(pfx + 'datatable-on-uncheck', function (e, ids) {

                        ids.forEach(function (id) {
                            Extension.unselectedRows.push(id);
                            // // remove from selected rows
                            Extension.selectedRows = Extension.remove(Extension.selectedRows, id);
                        });
                        var storage = {};
                        storage['selectedRows'] = unique(Extension.selectedRows);
                        storage['unselectedRows'] = unique(Extension.unselectedRows);
                        datatable.stateKeep('checkbox', storage);
                    });
                }
            },

            /**
             * Init checkbox clicks event
             */
            initEvent: function () {
                // select all checkbox click
                $(datatable.tableHead).find('.' + pfx + 'checkbox-all > [type="checkbox"]').click(function (e) {

                    // select all rows
                    Extension.selectedAllRows = !!$(this).is(':checked');

                    if ($(this).is(':checked')) {
                        Extension.selectedRows = Extension.selectedRows.concat(Extension.pageRecords());

                        Extension.unselectedRows = Extension.unselectedRows.filter(function (el) {
                            return Extension.pageRecords().indexOf(el) < 0;
                        });
                    } else {
                        Extension.unselectedRows = Extension.unselectedRows.concat(Extension.pageRecords());

                        Extension.selectedRows = Extension.selectedRows.filter(function (el) {
                            return Extension.pageRecords().indexOf(el) < 0;
                        });
                    }

                    Extension.selectedRows.forEach(function (id) {
                        datatable.setActive(id);
                    });

                    Extension.unselectedRows.forEach(function (id) {
                        datatable.setInactive(id);
                    });

                    $(datatable).trigger(pfx + 'datatable-on-click-checkbox', [$(this)]);
                });

                // single row checkbox click
                $(datatable.tableBody).find('.' + pfx + 'checkbox-single > [type="checkbox"]').click(function (e) {
                    var id = $(this).val();
                    if ($(this).is(':checked')) {
                        Extension.selectedRows.push(id);
                        // remove from unselected rows
                        Extension.unselectedRows = Extension.remove(Extension.unselectedRows, id);
                    }
                    else {
                        Extension.unselectedRows.push(id);
                        // remove from selected rows
                        Extension.selectedRows = Extension.remove(Extension.selectedRows, id);
                    }

                    // local checkbox header check
                    if (!options.vars.requestIds && Extension.selectedRows.length < 1) {
                        // remove select all checkbox, if there is no checked checkbox left
                        $(datatable.tableHead).find('.' + pfx + 'checkbox-all > [type="checkbox"]').prop('checked', false);
                    }

                    var storage = {};
                    storage['selectedRows'] = Extension.selectedRows.filter(Extension.unique);
                    storage['unselectedRows'] = Extension.unselectedRows.filter(Extension.unique);
                    datatable.stateKeep('checkbox', storage);

                    $(datatable).trigger(pfx + 'datatable-on-click-checkbox', [$(this)]);

                    // local checkbox; check if all checkboxes of currect page are checked
                    if (!datatable.hasClass(pfx + 'datatable-error')) {
                        if ($(datatable.tableBody).find('.' + pfx + 'checkbox-single > [type="checkbox"]').not(':checked').length < 1) {
                            // set header select all checkbox checked
                            $(datatable.tableHead).find('.' + pfx + 'checkbox-all > [type="checkbox"]').prop('checked', true);
                        } else {
                            // set header select all checkbox checked
                            $(datatable.tableHead).find('.' + pfx + 'checkbox-all > [type="checkbox"]').prop('checked', false);
                        }
                    }
                });
            },

            unique: function (value, index, self) {
                return self.indexOf(value) === index;
            },

            initSelect: function () {
                // selected all rows from server
                if (Extension.selectedAllRows && options.vars.requestIds) {

                    if (!datatable.hasClass(pfx + 'datatable-error')) {
                        // set header select all checkbox checked
                        $(datatable.tableHead).find('.' + pfx + 'checkbox-all [type="checkbox"]').prop('checked', false);
                    }

                    Extension.selectedRows.forEach(function (id) {
                        datatable.setActive(id);
                    });

                    // remove unselected rows
                    Extension.unselectedRows.forEach(function (id) {
                        datatable.setInactive(id);
                    });

                    // local checkbox; check if all checkboxes of currect page are checked
                    if (!datatable.hasClass(pfx + 'datatable-error') && $(datatable.tableBody).find('.' + pfx + 'checkbox-single > [type="checkbox"]').not(':checked').length < 1) {
                        // set header select all checkbox checked
                        $(datatable.tableHead).find('.' + pfx + 'checkbox-all > [type="checkbox"]').prop('checked', true);
                    }
                }
                else {
                    // single check for server and local
                    Extension.selectedRows.forEach(function (id) {
                        datatable.setActive(id);
                    });

                    // local checkbox; check if all checkboxes of currect page are checked
                    if (!datatable.hasClass(pfx + 'datatable-error') && $(datatable.tableBody).find('.' + pfx + 'checkbox-single > [type="checkbox"]').not(':checked').length < 1) {
                        // set header select all checkbox checked
                        $(datatable.tableHead).find('.' + pfx + 'checkbox-all > [type="checkbox"]').prop('checked', true);
                    }
                }
            },

            /**
             * Check if selector is enabled from options
             */
            selectorEnabled: function () {
                return $.grep(datatable.options.columns, function (n, i) {
                    return n.selector || false;
                })[0];
            },

            initVars: function () {
                // get single select/unselect from localstorage
                var storage = datatable.stateGet('checkbox');
                if (typeof storage !== 'undefined') {
                    Extension.selectedRows = storage['selectedRows'] || [];
                    Extension.unselectedRows = storage['unselectedRows'] || [];
                }
            },

            getSelectedId: function (path) {
                Extension.initVars();

                // else return single checked selected rows
                return Extension.selectedRows;
            },

            remove: function (array, element) {
                return array.filter(function (e) {
                    return e !== element;
                });
            },

            pageRecords: function() {
                return $.makeArray($(datatable.tableBody).find('.' + pfx + 'checkbox-single > [type="checkbox"]').map(function (i, chk) {
                    return $(chk).val();
                }));
            }
        };

        // make the extension accessible from datatable init
        datatable.checkbox = function () {
            return Extension;
        };

        if (typeof options === 'object') {
            options = $.extend(true, {}, $.fn[pluginName].checkbox.default, options);
            Extension.init.apply(this, [options]);
        }

        return datatable;
    };

    $.fn[pluginName].checkbox.default = {
        vars: {
            // select all rows flag to be sent to the server
            selectedAllRows: 'selectedAllRows',
            // request id parameter's name
            requestIds: 'requestIds',
            // response path to all rows id
            rowIds: 'meta.rowIds',
        },
    };

}(jQuery));


function unique(a) {
    var unique = a.filter(function (itm, i, a) {
        return i == a.indexOf(itm);
    });

    return unique;
}
