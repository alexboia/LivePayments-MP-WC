#!/usr/bin/env bash

# Move to plug-in root
if [[ `pwd` == */bin ]]
then
	pushd ../ > /dev/null
	LVDWCMC_EXPORT_RESTORE_DIR=true
else
	LVDWCMC_EXPORT_RESTORE_DIR=false
fi

# Store some stuff for later use
LVDWCMC_CDIR=$(pwd)
LVDWCMC_VERSION=$(awk '{IGNORECASE=1}/Version:/{print $NF}' ./abp01-plugin-main.php)

LVDWCMC_EXPORT_ROOT="$LVDWCMC_CDIR/build/wp-plugin-dir-svn"
LVDWCMC_EXPORT_TRUNK_DIR="$LVDWCMC_EXPORT_ROOT/trunk"
LVDWCMC_EXPORT_ASSETS_DIR="$LVDWCMC_EXPORT_ROOT/assets"
LVDWCMC_EXPORT_TAGS_DIR="$LVDWCMC_EXPORT_ROOT/tags"
LVDWCMC_EXPORT_CURRENT_TAG_DIR="$LVDWCMC_EXPORT_TAGS_DIR/$LVDWCMC_VERSION"

ensure_root_dir() {
	echo "Ensuring root directory structure and checking out if needed..."
	if [ ! -d $LVDWCMC_EXPORT_ROOT ]
	then
		mkdir $LVDWCMC_EXPORT_ROOT
		svn co https://plugins.svn.wordpress.org/wc-mobilpayments-card/ $LVDWCMC_EXPORT_ROOT
	fi
}

ensure_tag_dir() {
    echo "Ensuring tag directory structure..."
	if [ ! -d $LVDWCMC_EXPORT_CURRENT_TAG_DIR ] 
	then
		mkdir $LVDWCMC_EXPORT_CURRENT_TAG_DIR
	fi
}

clean_trunk_dir() {
	echo "Ensuring trunk directory is clean..."
	rm -rf $LVDWCMC_EXPORT_TRUNK_DIR/* > /dev/null
    rm -rf $LVDWCMC_EXPORT_TRUNK_DIR/.htaccess > /dev/null
}

regenerate_help() {
	echo "Re-generating help contents..."
	php ./help/tools/make-help.php
}

copy_source_files() {
    echo "Copying all source files to $1..."
	cp ./LICENSE.md "$1/license.txt"
	cp ./README.txt "$1/readme.txt"
	cp ./index.php "$1"
	cp ./abp01-plugin-main.php "$1"
	cp ./.htaccess "$1"

	mkdir "$1/media" && cp -r ./media/* "$1/media"
	mkdir "$1/views" && cp -r ./views/* "$1/views"
	mkdir "$1/lib" && cp -r ./lib/* "$1/lib"
	mkdir "$1/lang" && cp -r ./lang/* "$1/lang"

	mkdir "$1/data"
	mkdir "$1/data/cache" && mkdir "$1/data/storage"
	mkdir "$1/data/help" && mkdir "$1/data/setup"

	cp -r ./data/help/* "$1/data/help" > /dev/null
	cp -r ./data/dev/setup/* "$1/data/setup" > /dev/null
}

copy_asset_files() {
    echo "Copying all asset files to $LVDWCMC_EXPORT_ASSETS_DIR..."

    cp ./assets/en_US/viewer-summary.png    "$LVDWCMC_EXPORT_ASSETS_DIR/screenshot-1.png" > /dev/null
    cp ./assets/en_US/viewer-map.png	"$LVDWCMC_EXPORT_ASSETS_DIR/screenshot-2.png" > /dev/null
    cp ./assets/en_US/viewer-teaser-top.png	"$LVDWCMC_EXPORT_ASSETS_DIR/screenshot-3.png" > /dev/null
    cp ./assets/en_US/admin-edit-map.png	"$LVDWCMC_EXPORT_ASSETS_DIR/screenshot-4.png" > /dev/null
    cp ./assets/en_US/admin-edit-summary-bike.png	"$LVDWCMC_EXPORT_ASSETS_DIR/screenshot-5.png" > /dev/null
    cp ./assets/en_US/admin-edit-summary-empty.png  "$LVDWCMC_EXPORT_ASSETS_DIR/screenshot-6.png" > /dev/null
    cp ./assets/en_US/admin-settings.png    "$LVDWCMC_EXPORT_ASSETS_DIR/screenshot-7.png" > /dev/null

    cp ./assets/banner-772x250.jpg    "$LVDWCMC_EXPORT_ASSETS_DIR/banner-772x250.jpg" > /dev/null
    cp ./assets/banner-1544x500.jpg    "$LVDWCMC_EXPORT_ASSETS_DIR/banner-1544x500.jpg" > /dev/null
    cp ./assets/icon-128x128.png    "$LVDWCMC_EXPORT_ASSETS_DIR/icon-128x128.png" > /dev/null
    cp ./assets/icon-256x256.png    "$LVDWCMC_EXPORT_ASSETS_DIR/icon-256x256.png" > /dev/null
}

echo "Using version: $LVDWCMC_VERSION"

ensure_root_dir
clean_trunk_dir
regenerate_help
copy_source_files "$LVDWCMC_EXPORT_TRUNK_DIR"

if [ $# -eq 1 ] && [ "$1" = "--export-tag=true" ]
then
    ensure_tag_dir
    copy_source_files "$LVDWCMC_EXPORT_CURRENT_TAG_DIR"
fi

copy_asset_files

echo "DONE!"

if [ "$LVDWCMC_EXPORT_RESTORE_DIR" = true ]
then
	popd > /dev/null
fi