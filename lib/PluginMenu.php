<?php
namespace LvdWcMc {

    use InvalidArgumentException;

    class PluginMenu {
        const WOOCOMMERCE_ENTRY = 'woocommerce';

        const MAIN_ENTRY = 'lvdwcmc-plugin-settings';

        const SETTINGS_ENTRY = 'lvdwcmc-plugin-settings';

        const GATEWAY_SETTINGS_ENTRY = 'lvdwcmc-gateway-settigns';

        const DIAGNOSTICS_ENTRY = 'lvdwcmc-plugin-diagnostics';

        const CARD_TRANSACTIONS_LISTING_ENTRY = 'lvdwcmc-card-transactions-listing';

        private static $_allEntriesMetadata = null;

        private static function _getAllEntriesMetadata() {
            if (self::$_allEntriesMetadata === null) {
                self::$_allEntriesMetadata = array(
                    self::WOOCOMMERCE_ENTRY => array(
                        'external' => true,
                        'entries' => array(
                            self::CARD_TRANSACTIONS_LISTING_ENTRY => array(
                                'label' => __('LivePayments Card Transactions', 'livepayments-mp-wc'),
                                'page_title' => __('LivePayments Card Transactions', 'livepayments-mp-wc'),
                                'capability' => 'manage_woocommerce'
                            )
                        )
                    ),
                    self::MAIN_ENTRY => array(
                        'label' => __('Livepayments-MP-WC', 'livepayments-mp-wc'),
                        'page_title' => __('LivePayments - mobilPay Card WooCommerce Payment Gateway - Plugin Settings', 'livepayments-mp-wc'),
                        'capability' => 'manage_options',
                        'icon' => 'dashicons-money-alt',
                        'position' => 60,

                        'entries' => array(
                            self::SETTINGS_ENTRY => array(
                                'label' => __('Plugin Settings', 'livepayments-mp-wc'),
                                'page_title' => __('LivePayments - mobilPay Card WooCommerce Payment Gateway - Plugin Settings', 'livepayments-mp-wc'),
                                'capability' => 'manage_options'
                            ),
                            self::GATEWAY_SETTINGS_ENTRY => array(
                                'label' => __('Gateway Settings', 'livepayments-mp-wc'),
                                'page_title' => __('LivePayments - mobilPay Card WooCommerce Payment Gateway - Gateway Settings', 'livepayments-mp-wc'),
                                'capability' => 'manage_options'
                            ),
                            self::DIAGNOSTICS_ENTRY => array(
                                'label' => __('Plugin Diagnostics', 'livepayments-mp-wc'),
                                'page_title' => __('LivePayments - mobilPay Card WooCommerce Payment Gateway - Plugin Diagnostics', 'livepayments-mp-wc'),
                                'capability' => 'manage_options'
                            )
                        )
                    )
                );
            }
            return self::$_allEntriesMetadata;
        }

        private static function _getEntryMetadata($entrySlug) {
            $allEntries = self::_getAllEntriesMetadata();
            return isset($allEntries[$entrySlug]) 
                ? $allEntries[$entrySlug] 
                : null;
        }

        private static function _getSubEntryMetadata($parentSlug, $entrySlug) {
            $entry = null;
            $parent = self::_getEntryMetadata($parentSlug);
            
            if (!empty($parent) && !empty($parent['entries'])) {
                $entry = isset($parent['entries'][$entrySlug]) 
                    ? $parent['entries'][$entrySlug] 
                    : null;
            }

            return $entry;
        }

        public static function registerMenuEntryWithCallback($entrySlug, $callback) {
            if (empty($entrySlug)) {
                throw new InvalidArgumentException('Entry slug must not be empty');
            }

            $entry = self::_getEntryMetadata($entrySlug);
            if (!empty($entry)) {
                add_menu_page($entry['page_title'], 
                    $entry['label'], 
                    $entry['capability'], 
                    $entrySlug, 
                    $callback, 
                    $entry['icon'], 
                    $entry['position']);
            }
        }

        public static function registerSubMenuEntryWithCallback($parentSlug, $entrySlug, $callback) {
            if (empty($parentSlug)) {
                throw new InvalidArgumentException('Parent slug must not be empty');
            }

            if (empty($entrySlug)) {
                throw new InvalidArgumentException('Entry slug must not be empty');
            }

            $entry = self::_getSubEntryMetadata($parentSlug, $entrySlug);
            if (!empty($entry)) {
                add_submenu_page($parentSlug, 
                    $entry['page_title'], 
                    $entry['label'], 
                    $entry['capability'], 
                    $entrySlug, 
                    $callback);
            }
        }
    }
}