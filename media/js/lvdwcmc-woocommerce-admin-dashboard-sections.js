/**
 * Copyright (c) 2019-2020 Alexandru Boia
 *
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 * 
 *	1. Redistributions of source code must retain the above copyright notice, 
 *		this list of conditions and the following disclaimer.
 *
 * 	2. Redistributions in binary form must reproduce the above copyright notice, 
 *		this list of conditions and the following disclaimer in the documentation 
 *		and/or other materials provided with the distribution.
 *
 *	3. Neither the name of the copyright holder nor the names of its contributors 
 *		may be used to endorse or promote products derived from this software without 
 *		specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, 
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY 
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES 
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, 
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED 
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 */

(function($, wp, wc) {
    "use strict";

    var STORE_KEY = 'lvd/livepayments-mp-wc/transaction-reporting';

    var e = wp.element.createElement;
    var apiFetch = wp.apiFetch;
    
    var WpComponent = wp.element.Component;
    var WcSpinner = wc.components.Spinner;
    var WcEmptyContent = wc.components.EmptyContent;
    var WcCard = wc.components.Card;
    var WcSectionHeader = wc.components.SectionHeader;

    var initialState = {
        transactionsStatusCounts: _getEmptyData(),
        lastTransctionDetails: _getEmptyData()
    };

    var actionCodes = {
        LOAD_TRANSACTIONS_STATUS_COUNTS: 'LVDWCMC_LOAD_TRANSACTIONS_STATUS_COUNTS',
        SET_TRANSACTIONS_STATUS_COUNTS: 'LVDWCMC_SET_TRANSACTIONS_STATUS_COUNTS',
        LOAD_LAST_TRANSACTION_DETAILS: 'LVDWCMC_LOAD_LAST_TRANSACTION_DETAILS',
        SET_LAST_TRANSACTION_DETAILS: 'LVDWCMC_SET_LAST_TRANSACTION_DETAILS'
    };

    var storeActions = {
        setTransactionsStatusCounts: function(countsData) {
            return {
                type: actionCodes.SET_TRANSACTIONS_STATUS_COUNTS,
                countsData: countsData
            };
        },
        loadTransactionsStatusCounts: function(fromUrl) {
            return {
                type: actionCodes.LOAD_TRANSACTIONS_STATUS_COUNTS,
                path: fromUrl
            };
        },
        setLastTransactionDetails: function(txDetailsData) {
            return {
                type: actionCodes.SET_LAST_TRANSACTION_DETAILS,
                txDetailsData: txDetailsData
            };
        },
        loadLastTransactionDetails: function(fromUrl) {
            return {
                type: actionCodes.LOAD_LAST_TRANSACTION_DETAILS,
                path: fromUrl
            };
        }
    };

    var storeSelectors = {
        getTransactionsStatusCounts: function(state) {
            return $.extend({}, state.transactionsStatusCounts);
        },
        getLastTransactionDetails: function(state) {
            return $.extend({}, state.lastTransctionDetails);
        }
    };

    var storeResolvers = {
        getTransactionsStatusCounts: function() {
            _setStartedResolutionIfNeeded('getTransactionsStatusCounts');
            _dispatch().loadTransactionsStatusCounts();
            apiFetch({ path: '/livepayments-mp-wc/reports/transctions-status-counts' })
                .then(function(result) {
                    _dispatch().setTransactionsStatusCounts(_getDataWithResult(result));
                    _setFinishedResolution('getTransactionsStatusCounts');
                }).catch(function(reason) {
                    _dispatch().setTransactionsStatusCounts(_getFailedLoadedData());
                    _setFinishedResolution('getTransactionsStatusCounts');
                });
        },

        getLastTransactionDetails: function() {
            _setStartedResolutionIfNeeded('getLastTransactionDetails');
            _dispatch().loadLastTransactionDetails();
            apiFetch({path: '/livepayments-mp-wc/reports/last-transaction-details'})
                .then(function(result) {
                    _dispatch().setLastTransactionDetails(_getDataWithResult(result));
                    _setFinishedResolution('getLastTransactionDetails');
                }).catch(function(reason) {
                    _dispatch().setTransactionsStatusCounts(_getFailedLoadedData());
                    _setFinishedResolution('getTransactionsStatusCounts');
                });
        }
    };

    function _dispatch() {
        return wp.data.dispatch(STORE_KEY);
    };

    function _select() {
        return wp.data.select(STORE_KEY);
    }

    function _setStartedResolutionIfNeeded(selector) {
        if (!_select().hasStartedResolution(selector)) {
            _dispatch().startResolution(selector);
        }
    }

    function _setFinishedResolution(selector) {
        _dispatch().finishResolution(selector);
    }

    function _stateReducer(state, action) {
        if (!state) {
            state = initialState;
        }

        switch (action.type) {
            case actionCodes.LOAD_TRANSACTIONS_STATUS_COUNTS:
                state = $.extend(state, {
                    transactionsStatusCounts:_getDataLoading()
                });
                break;
            case actionCodes.LOAD_LAST_TRANSACTION_DETAILS:
                state = $.extend(state, {
                    lastTransctionDetails: _getDataLoading()
                });
                break;
            case actionCodes.SET_TRANSACTIONS_STATUS_COUNTS:
                state = $.extend(state, {
                    transactionsStatusCounts: action.countsData
                });
                break;
            case actionCodes.SET_LAST_TRANSACTION_DETAILS:
                state = $.extend(state, {
                    lastTransctionDetails: action.txDetailsData
                });
                break;
        }

        return state;
    }

    function _getEmptyData() {
        return {
            items: null,
            loaded: false,
            loading: false,
            success: false
        };
    }

    function _getDataLoading() {
        return $.extend(_getEmptyData(), {
            loading: true
        });
    }

    function _getDataWithResult(result) {
        return {
            items: result.data,
            loaded: true,
            loading: false,
            success: !!result && !!result.success
        };
    }

    function _getFailedLoadedData() {
        return {
            loaded: true,
            loading: false,
            success: false,
            items: null
        };
    }

    function _createComponent(spec) {
        function LvdWcMcComponent(props) {
            wp.element.Component.call(this, props);
            this.props = props;
        }

        LvdWcMcComponent.prototype = WpComponent.prototype;
        LvdWcMcComponent.prototype.constructor = LvdWcMcComponent;

        for (var key in spec) {
            if (spec.hasOwnProperty(key)) {
                LvdWcMcComponent.prototype[key] = spec[key];
            }
        }

        return LvdWcMcComponent;
    }

    function _renderLoadingIndicator() {
        return e('div', {
            className: 'lvdwcmc-dashboard-panel-loading-container'
        }, e(WcSpinner, {
            className: 'lvdwcmc-dashboard-panel-loading'
        }, null));
    }

    function _renderEmptyContent(title, message) {
        return e(WcEmptyContent, {
            title: title,
            message: message,
            actionLabel: lvdwcmcWooAdminDashboardSectionsL10n.lblReloadPageBtn,
            illustrationWidth: 180,
            actionCallback: function() {
                window.location.reload();
            }
        });
    }

    function _renderSectionHeader() {
        return e(WcSectionHeader, {
            title: lvdwcmcWooAdminDashboardSectionsL10n.lblSectionTitle
        }, null);
    }

    function _renderCard(title, content) {
        return e(WcCard, {
            className: 'woocommerce-dashboard__lvdwcmc-dashboard-card',
            title: title
        }, content);
    }

    function _renderTransactionStatusCountsCard(countsData) {
        var items = [];
        var content = null;

        if (countsData.loaded) {
            if (countsData.items) {
                for (status in countsData.items) {
                    if (countsData.items.hasOwnProperty(status)) {
                        var countItem = countsData.items[status];
                        items.push(e('li', { className: status },
                            e('span', { className: 'lvdwcmc-status-count' }, countItem.count),
                            e('h5', { className: 'lvdwcmc-status-label' }, countItem.label),
                            e('div', { className: 'lvdwcmc-clear' }, null)
                        ));
                    }
                }
            }

            if(items.length > 0) {
                content = e('ul', { 
                    className:'lvdwcmc-dashboard-transaction-status' 
                }, items);
            } else {
                content = _renderEmptyContent(
                    lvdwcmcWooAdminDashboardSectionsL10n.warnDataNotFoundTitle, 
                    lvdwcmcWooAdminDashboardSectionsL10n.warnDataNotFoundTransactionsStatusCounts
                );
            }
        } else if (countsData.loading) {
            content = _renderLoadingIndicator();
        }

        return _renderCard(lvdwcmcWooAdminDashboardSectionsL10n.lblTitleTransactionsStatusCounts, 
            content);
    }

    function _renderLastTransDetailsCard(txDetailsData) {
        var items = [];
        var content = null;

        if (txDetailsData.loaded) {
            if (txDetailsData.items) {
                for (var i = 0; i < txDetailsData.items.length; i ++) {
                    var txDataItem = txDetailsData.items[i];
                    items.push(e('tr', {}, 
                        e('th', { scope: 'row' }, txDataItem.label),
                        e('td', { scope: 'row' }, txDataItem.value || '-')
                    ));
                }
            }

            if(items.length > 0) {
                content = e('table', { 
                    className:'lvdwcmc-admin-transaction-details-list' 
                }, e('tbody', {}, items));
            } else {
                content = _renderEmptyContent(
                    lvdwcmcWooAdminDashboardSectionsL10n.warnDataNotFoundTitle, 
                    lvdwcmcWooAdminDashboardSectionsL10n.warnDataNotFoundLastTransactionDetails
                );
            }
        } else {
            content = _renderLoadingIndicator();
        }

        return _renderCard(lvdwcmcWooAdminDashboardSectionsL10n.lblTitleLastTransactionDetails, 
            content);
    }

    function _renderSectionContents(sectionData) {
         return e('div', {
            className: 'woocommerce-dashboard__columns'
        }, 
            _renderTransactionStatusCountsCard(sectionData.transactionsStatusCounts),
            _renderLastTransDetailsCard(sectionData.lastTransctionDetails));
    }

    var TransactionsStatusDashboardSection = _createComponent({
        render: function() {
            return e('div', null, 
                _renderSectionHeader(),
                _renderSectionContents({
                    transactionsStatusCounts: this.props.transactionsStatusCounts,
                    lastTransctionDetails: this.props.lastTransctionDetails
                }));
        }
    });

    wp.data.registerStore(STORE_KEY, {
        reducer: _stateReducer,
        actions: storeActions,
        selectors: storeSelectors,
        controls: null,
        resolvers: storeResolvers
    });

    wp.hooks.addFilter('woocommerce_dashboard_default_sections', 'lvdwcmc/add-tx-status-dashoard-section', function(sections) {
        sections.unshift({
            key: 'lvdwcmc-transactions-status-dashboard-section',
			component: wp.data.withSelect(function(select, ownProps) {
                var store = select(STORE_KEY);
                return {
                    transactionsStatusCounts: store.getTransactionsStatusCounts(),
                    lastTransctionDetails: store.getLastTransactionDetails()
                };
            })(TransactionsStatusDashboardSection),
			title: lvdwcmcWooAdminDashboardSectionsL10n.lblSectionTitle,
			isVisible: true,
			icon: 'arrow-right-alt',
			hiddenBlocks: []
        });

        return sections;
    });
})(jQuery, wp, wc);