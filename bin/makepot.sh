#!/usr/bin/env bash

# Move to plug-in root
if [[ `pwd` == */bin ]]
then
	pushd ../ > /dev/null
	LVDWCMC_RESTORE_DIR=true
else
	LVDWCMC_RESTORE_DIR=false
fi

if [ ! -z "${WP_I18N_LIB+xxx}" ] || [ ! -d "$WP_I18N_LIB" ]; then
	WP_I18N_LIB="/usr/lib/wpi18n"
fi

if [ $# -lt 1 ]; then
	LVDWCMC_PLUGIN_DIR=`pwd`
else
	LVDWCMC_PLUGIN_DIR="$1"
fi

if [ -z "$2" ]; then
	LVDWCMC_TEXT_DOMAIN=""
else
	LVDWCMC_TEXT_DOMAIN=$2
fi

if [[ ! $LVDWCMC_TEXT_DOMAIN ]]
then
	LVDWCMC_TEXT_DOMAIN="livepayments-mp-wc"
fi

wp i18n make-pot "$LVDWCMC_PLUGIN_DIR" "$LVDWCMC_PLUGIN_DIR/lang/$LVDWCMC_TEXT_DOMAIN.pot" --slug="livepayments-mp-wc" --domain=$LVDWCMC_TEXT_DOMAIN --exclude="build,bin,assets,data,.github,.vscode,help"

if [ "$LVDWCMC_RESTORE_DIR" = true ]
then
	popd > /dev/null
fi