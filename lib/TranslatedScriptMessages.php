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

namespace LvdWcMc {
    class TranslatedScriptMessages {
        public static function getTransactionsListingScriptTranslations() {
            return array(
                'errCannotLoadTransactionDetails' 
                    => __('Could not load transaction details data', 'livepayments-mp-wc'),
                'errCannotLoadTransactionDetailsNetwork' 
                    => __('Could not load transaction details data due to a possible network issue', 'livepayments-mp-wc')
            );
        }

        public static function getWooAdminDashboardSectionsScriptTranslations() {
            return array(
                'lblReloadPageBtn' 
                    => __('Reload page', 'livepayments-mp-wc'),
                'lblSectionTitle' 
                    => __('LivePayments for Mobilpay - Transaction Reporting', 'livepayments-mp-wc'),
                'lblTitleTransactionsStatusCounts' 
                    => __('Transactions Status Counts', 'livepayments-mp-wc'),
                'lblTitleLastTransactionDetails' 
                    => __('Last Transaction', 'livepayments-mp-wc'),
                'warnDataNotFoundTitle' 
                    => __('Data not found!', 'livepayments-mp-wc'),
                'warnDataNotFoundLastTransactionDetails' 
                    => __('No transactions data found', 'livepayments-mp-wc'),
                'warnDataNotFoundTransactionsStatusCounts' 
                    => __('No transactions status counts data found', 'livepayments-mp-wc'),
                'errDataLoadingErrorTitle' 
                    => __('Error loading data', 'livepayments-mp-wc'),
                'errDataLoadingErrorLastTransactionDetails' 
                    => __('The last transaction details data could not be loaded due to an internal server issue. Please try again.', 'livepayments-mp-wc'),
                'errDataLoadingErrorTransactionsStatusCounts' 
                    => __('The transactions status counts data could not be loaded due to an internal server issue. Please try again.', 'livepayments-mp-wc'),
            );
        }

        public static function getGatewaySettingsScriptTranslations() {
            return array(
                'errPluploadTooLarge' 
                    => __('The selected file is too large. Maximum allowed size is 10MB', 'livepayments-mp-wc'), 
                'errPluploadFileType' 
                    => __('The selected file type is not valid.', 'livepayments-mp-wc'), 
                'errPluploadIoError' 
                    => __('The file could not be read', 'livepayments-mp-wc'), 
                'errPluploadSecurityError' 
                    => __('The file could not be read', 'livepayments-mp-wc'), 
                'errPluploadInitError' 
                    => __('The uploader could not be initialized', 'livepayments-mp-wc'), 
                'errPluploadHttp' 
                    => __('The file could not be uploaded', 'livepayments-mp-wc'), 
                'errServerUploadFileType' 
                    => __('The selected file type is not valid.', 'livepayments-mp-wc'), 
                'errServerUploadTooLarge' 
                    => __('The selected file is too large. Maximum allowed size is 10MB', 'livepayments-mp-wc'), 
                'errServerUploadNoFile' 
                    => __('No file was uploaded', 'livepayments-mp-wc'), 
                'errServerUploadInternal' 
                    => __('The file could not be uploaded due to a possible internal server issue', 'livepayments-mp-wc'), 
                'errServerUploadFail' 
                    => __('The file could not be uploaded', 'livepayments-mp-wc'),
                'warnRemoveAssetFile' 
                    => __('Remove asset file? This action cannot be undone and you will have to re-upload the asset again!', 'livepayments-mp-wc'),
                'errAssetFileCannotBeRemoved' 
                    => __('The asset file could not be removed', 'livepayments-mp-wc'),
                'errAssetFileCannotBeRemovedNetwork' 
                    => __('The asset file could not be removed due to a possible network issue', 'livepayments-mp-wc'),
                'assetUploadOk' 
                    => __('The file has been successfully uploaded', 'livepayments-mp-wc'),
                'assetRemovalOk' 
                    => __('The file has been successfulyl removed', 'livepayments-mp-wc'),
                'returnURLGenerationOk'
                    => __('The return URL has been successfully generated.','livepayments-mp-wc'),
                'errReturnURLCannotBeGenerated'
                    => __('The return URL could not generated.', 'livepayments-mp-wc'),
                'errReturnURLCannotBeGeneratedNetwork'
                    => __('The return URL could not be generated due to a possible network issue', 'livepayments-mp-wc')
            );
        }

        public static function getPluginSettingsScriptTranslations() {
            return array(
                'msgSaveOk' => __('The settings have been successfully saved.', 'livepayments-mp-wc'),
                'errSaveFailGeneric' => __('The settings could not be saved. Please try again.', 'livepayments-mp-wc'),
                'errSaveFailNetwork' => __('The settings could not be saved. Please try again.', 'livepayments-mp-wc')
            );
        }

        public static function getCommonScriptTranslations() {
            return array(
                'lblLoading' => __('Please wait...', 'livepayments-mp-wc')
            );
        }
    }
}