<?php
/**
 * Copyright (c) 2019-2021 Alexandru Boia
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

namespace LvdWcMc\PluginModules {

	use LvdWcMc\MobilpayTransaction;
	use LvdWcMc\Plugin;
	use LvdWcMc\PluginMenu;

	class AdminTransactionDetailsModule extends PluginModule {
		const WEB_PAGE_ASSETS_HOOKS_ORDER = 99998;

		const ACTION_GET_ADMIN_TRANSACTION_DETAILS = 'lvdwcmc_get_admin_transaction_details';

		/**
		 * @var \LvdWcMc\WordPressAdminAjaxAction
		 */
		private $_getAdminTransactionDetailsAction;

		/**
		 * @var \LvdWcMc\Formatters
		 */
		private $_formatters;

		/**
		 * @var \LvdWcMc\MobilpayTransactionFactory
		 */
		private $_transactionFactory;

		public function __construct(Plugin $plugin) {
			parent::__construct($plugin);

			$this->_formatters = $plugin
				->getFormatters();
			$this->_transactionFactory = $plugin
				->getTransactionFactory();

			$this->_getAdminTransactionDetailsAction = $this
				->_createAdminAjaxAction(self::ACTION_GET_ADMIN_TRANSACTION_DETAILS, 
					array($this, 'getAdminTransactionDetails'), 
					true, 
					'manage_woocommerce');
		}
		
		public function load() {
			$this->_registerWebPageAssets();
			$this->_registerMenuHooks();

			$this->_getAdminTransactionDetailsAction
				->register();
		}

		private function _registerWebPageAssets() {
			add_action('admin_enqueue_scripts', 
				array($this, 'onAdminEnqueueScripts'), 
				self::WEB_PAGE_ASSETS_HOOKS_ORDER);

			add_action('admin_enqueue_scripts', 
				array($this, 'onAdminEnqueueStyles'), 
				self::WEB_PAGE_ASSETS_HOOKS_ORDER);
		}

		public function onAdminEnqueueScripts() {
			if ($this->_env->isViewingAdminTransactionListing()) {
				$this->_mediaIncludes
					->includeScriptTransactionListing(
						$this->_plugin->getTransactionsListingScriptTranslations(), 
						$this->_plugin->getCommonScriptTranslations()
					);
			}
		}

		public function onAdminEnqueueStyles() {
			if ($this->_env->isViewingAdminTransactionListing()) {
				$this->_mediaIncludes
					->includeStyleAdminTransactionListing();
			}
		}

		private function _registerMenuHooks() {
			add_action('admin_menu', array($this, 'onAddAdminMenuEntries'));
		}

		public function onAddAdminMenuEntries() {
			PluginMenu::registerSubMenuEntryWithCallback(PluginMenu::WOOCOMMERCE_ENTRY, 
				PluginMenu::CARD_TRANSACTIONS_LISTING_ENTRY, 
				array($this, 'showAdminTransactionsListing'));
		}

		public function showAdminTransactionsListing() {
			if (!$this->_currentUserCanManageWooCommerce()) {
				die;
			}

			$currentPage = $this->_getTransactionsListingCurrentPageFromUrl();
			$totalRecords = $this->_countTotalTransactions();
			$totalPages = $this->_calculateTotalTransactionsListingPageCount($totalRecords);
			$offsetAndLimit = $this->_getTransactionsListingOffsetAndLimit($currentPage);

			$transactions = $this->_getTransactionsFromDb($offsetAndLimit);
			$listingContext = array(
				'pageNum' => $currentPage,
				'totalRecords' => $totalRecords,
				'totalPages' => $totalPages
			);

			foreach ($transactions as $key => $transactionItem) {
				$transactions[$key] = $this->_prepareTransactionItemForDisplay($transactionItem, 
					$listingContext);
			}

			$data = new \stdClass();
			$data->additionalColumns = array();
			$data->pageTitle = get_admin_page_title();

			$data->transactions = $transactions;
			$data->hasTransactions = !empty($transactions);

			$data->ajaxBaseUrl = $this->_getAjaxBaseUrl();
			$data->transactionDetailsAction = self::ACTION_GET_ADMIN_TRANSACTION_DETAILS;
			$data->transactionDetailsNonce = $this->_getAdminTransactionDetailsAction
				->generateNonce();

			$data->totalPages = $totalPages;
			$data->totalRecords = $totalRecords;
			$data->currentPage = $currentPage;
			$data->paginateLinksArgs = $this->_getTransactionListingLinkPaginationArgs($totalPages, 
				$currentPage);

			/**
			 * Filters the view model of the admin transactions listing page, 
			 *  thus allowing additional data to be added to it.
			 * 
			 * @hook lvdwcmc_get_admin_transansactions_listing_data
			 * 
			 * @param \stdClass $data The view model, initially provided by LivePayments-MP-WC
			 * @param array $args Additional arguments to establish the context of the operation
			 * 
			 * @return \stdClass The view model, as returned by the registered filters
			 */
			$data = apply_filters('lvdwcmc_get_admin_transansactions_listing_data', 
				$data, 
				$listingContext);

			echo $this->_viewEngine->renderView('lvdwcmc-admin-transactions-listing.php', 
				$data);
		}

		private function _getTransactionsListingCurrentPageFromUrl() {
			return max(isset($_GET['page_num']) 
				? intval($_GET['page_num']) 
				: 1, 1);
		}

		private function _countTotalTransactions() {
			return $this->_getDb()->getValue(
				$this->_env->getPaymentTransactionsTableName(), 
				'COUNT(tx_id)'
			);
		}

		private function _getTransactionsListingOffsetAndLimit($currentPage) {
			$limit = LVD_WCMC_RECORDS_PER_PAGE;
			$offset = ($currentPage - 1) * $limit;
			return array(
				$offset,
				$limit
			);
		}

		private function _calculateTotalTransactionsListingPageCount($totalRecords) {
			return ceil($totalRecords / LVD_WCMC_RECORDS_PER_PAGE);
		}

		private function _getTransactionsFromDb(array $offsetAndLimit) {
			$db = $this->_getDb();
			$db->join($this->_env->getPostsTableName() . ' wp', 
				'wp.ID = tx.tx_order_id', 
				'LEFT');

			$db->orderBy('tx.tx_timestamp_last_updated', 'DESC');
			$db->orderBy('tx.tx_timestamp_initiated', 'DESC');

			return $db->get($this->_env->getPaymentTransactionsTableName() . ' tx', 
				$offsetAndLimit, 
				'tx.*, wp.post_title tx_title');
		}

		private function _prepareTransactionItemForDisplay(array $transactionItem, array $listingContext) {
			$transactionItem['tx_title_full'] = 
				$this->_getTransactionFullTitle($transactionItem);
			$transactionItem['tx_admin_details_link'] = 
				$this->_getTransactionOrderDetailsLink($transactionItem);

			$transactionItem['tx_timestamp_initiated_formatted'] = 
				$this->_formatTransactionTimestamp($transactionItem['tx_timestamp_initiated']);
			$transactionItem['tx_timestamp_last_updated_formatted'] = 
				$this->_formatTransactionTimestamp($transactionItem['tx_timestamp_last_updated']);
			$transactionItem['tx_amount_formatted'] = 
				$this->_formatTransactionAmount($transactionItem['tx_amount']) . ' ' . $transactionItem['tx_currency'];
			$transactionItem['tx_processed_amount_formatted'] = 
				$this->_formatTransactionAmount($transactionItem['tx_processed_amount']) . ' ' . $transactionItem['tx_currency'];
			$transactionItem['tx_status_formatted'] = 
				$this->_getTransactionStatusLabel($transactionItem['tx_status']);

			/**
			 * Filters a transaction listing item, represented as an array, 
			 *  after the formatted data has been added to it.
			 * The view model is a plain stdClass and contains 
			 *  any data required to correctly render the template.
			 * 
			 * @hook lvdwcmc_get_admin_transansactions_listing_item
			 * 
			 * @param array $tx The view model, initially provided by LivePayments-MP-WC
			 * @param array $args Additional arguments to establish the context of the operation
			 * 
			 * @return array The view model, as returned by the registered filters
			 */
			$transactionItem = apply_filters('lvdwcmc_get_admin_transansactions_listing_item', 
				$transactionItem, 
				$listingContext);

			return $transactionItem;
		}

		private function _getTransactionFullTitle(array $transactionItem) {
			return '#' . $transactionItem['tx_order_id'] . ' ' . $transactionItem['tx_title'];
		}

		private function _getTransactionOrderDetailsLink(array $transactionItem) {
			return get_edit_post_link($transactionItem['tx_order_id']);
		}

		private function _getTransactionListingLinkPaginationArgs($totalPages, $currentPage) {
			return array(
				'base' => add_query_arg('page_num', '%#%'),
				'format' => '',
				'prev_text' => __('&laquo;', 'livepayments-mp-wc'),
				'next_text' => __('&raquo;', 'livepayments-mp-wc'),
				'total' => $totalPages,
				'current' => $currentPage
			);
		}

		public function getAdminTransactionDetails() {
			if (!$this->_env->isHttpGet()) {
				die;
			}

			$transactionId = $this->_getTransactionIdFromUrl();
			if ($transactionId <= 0) {
				die;
			}

			$response = lvdwcmc_get_ajax_response(array(
				'transaction' => null
			));

			$transaction = $this->_getTransactionById($transactionId);
			if ($transaction != null) {
				$response->transaction = $this->_getDisplayableTransactionDetails($transaction);
				$response->success = true;
			}

			return $response;
		}

		private function _getTransactionIdFromUrl() {
			return isset($_GET['transaction_id']) 
				? intval($_GET['transaction_id']) 
				: 0;
		}

		private function _getTransactionById($transactionId) {
			return $this->_transactionFactory
				->fromTransactionId($transactionId);
		}

		private function _getDisplayableTransactionDetails(MobilpayTransaction $transaction) {
			return $this->_formatters
				->getDisplayableTransactionDetails($transaction);
		}

		private function _formatTransactionAmount($amount) {
			return $this->_formatters
				->formatTransactionAmount($amount);
		}

		private function _formatTransactionTimestamp($strTimestamp) {
			return $this->_formatters
				->formatTransactionTimestamp($strTimestamp);
		}

		private function _getTransactionStatusLabel($status) {
			return MobilpayTransaction::getStatusLabel($status);
		}
	}
}