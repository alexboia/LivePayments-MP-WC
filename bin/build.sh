#!/usr/bin/env bash

# Move to plug-in root
if [[ `pwd` == */bin ]]
then
	pushd ../ > /dev/null
	LVDWCMC_RESTORE_DIR=true
else
	LVDWCMC_RESTORE_DIR=false
fi

# Store some stuff for later use
LVDWCMC_CDIR=$(pwd)

LVDWCMC_BUILD_ROOTDIR="$LVDWCMC_CDIR/build"
LVDWCMC_BUILD_OUTDIR="$LVDWCMC_BUILD_ROOTDIR/output"
LVDWCMC_BUILD_COMPATDIR="$LVDWCMC_BUILD_ROOTDIR/compat-info"
LVDWCMC_BUILD_TMPDIR="$LVDWCMC_BUILD_ROOTDIR/tmp"

LVDWCMC_VERSION=$(awk '{IGNORECASE=1}/Version:/{print $NF}' ./wc-mobilpayments-card-plugin-main.php | awk '{gsub(/\s+/,""); print $0}')
LVDWCMC_BUILD_NAME="wc-mobilpayments-card.$LVDWCMC_VERSION.zip"

# Ensure all output directories exist
ensure_out_dirs() {
	echo "Ensuring output directory structure..."

	if [ ! -d $LVDWCMC_BUILD_ROOTDIR ]
	then
		mkdir $LVDWCMC_BUILD_ROOTDIR
	fi

	if [ ! -d $LVDWCMC_BUILD_OUTDIR ] 
	then
		mkdir $LVDWCMC_BUILD_OUTDIR
	fi

	if [ ! -d $LVDWCMC_BUILD_COMPATDIR ] 
	then
		mkdir $LVDWCMC_BUILD_COMPATDIR
	fi

	if [ ! -d $LVDWCMC_BUILD_TMPDIR ] 
	then
		mkdir $LVDWCMC_BUILD_TMPDIR
	fi
}

# Regenerate compatibility info
make_compat_info() {
	echo "Building compatibility information files..."
	./bin/detect-compat-info.sh
}

# Ensure help contents is up to date
regenerate_help() {
	echo "No help contents to generate..."
}

clean_tmp_dir() {
	echo "Cleaning up temporary directory..."
	rm -rf $LVDWCMC_BUILD_TMPDIR/*
	rm -rf $LVDWCMC_BUILD_TMPDIR/.htaccess
}

# Clean output directories
clean_out_dirs() {
	echo "Ensuring output directories are clean..."
	rm -rf $LVDWCMC_BUILD_OUTDIR/* > /dev/null
	rm -rf $LVDWCMC_BUILD_TMPDIR/* > /dev/null
	rm -rf $LVDWCMC_BUILD_TMPDIR/.htaccess > /dev/null
}

# Copy over all files
copy_source_files() {
	echo "Copying all files..."
	cp ./LICENSE "$LVDWCMC_BUILD_TMPDIR/license.txt"
	cp ./README.txt "$LVDWCMC_BUILD_TMPDIR/readme.txt"
	cp ./index.php "$LVDWCMC_BUILD_TMPDIR"
	cp ./wc-mobilpayments-card-plugin-header.php "$LVDWCMC_BUILD_TMPDIR"
	cp ./wc-mobilpayments-card-plugin-functions.php "$LVDWCMC_BUILD_TMPDIR"
	cp ./wc-mobilpayments-card-plugin-main.php "$LVDWCMC_BUILD_TMPDIR"
	cp ./.htaccess "$LVDWCMC_BUILD_TMPDIR"

	mkdir "$LVDWCMC_BUILD_TMPDIR/media" && cp -r ./media/* "$LVDWCMC_BUILD_TMPDIR/media"
	mkdir "$LVDWCMC_BUILD_TMPDIR/views" && cp -r ./views/* "$LVDWCMC_BUILD_TMPDIR/views"
	mkdir "$LVDWCMC_BUILD_TMPDIR/lib" && cp -r ./lib/* "$LVDWCMC_BUILD_TMPDIR/lib"
	mkdir "$LVDWCMC_BUILD_TMPDIR/lang" && cp -r ./lang/* "$LVDWCMC_BUILD_TMPDIR/lang"

	mkdir "$LVDWCMC_BUILD_TMPDIR/data"
	mkdir "$LVDWCMC_BUILD_TMPDIR/data/cache"
	mkdir "$LVDWCMC_BUILD_TMPDIR/data/help"
	mkdir "$LVDWCMC_BUILD_TMPDIR/data/setup"

	cp -r ./data/help/* "$LVDWCMC_BUILD_TMPDIR/data/help" > /dev/null
	cp -r ./data/setup/* "$LVDWCMC_BUILD_TMPDIR/data/setup" > /dev/null
}

generate_package() {
	echo "Generating archive..."
	pushd $LVDWCMC_BUILD_TMPDIR > /dev/null
	zip -rT $LVDWCMC_BUILD_OUTDIR/$LVDWCMC_BUILD_NAME ./ > /dev/null
	popd > /dev/null
}

echo "Using version: ${LVDWCMC_VERSION}"

ensure_out_dirs
clean_out_dirs
regenerate_help
make_compat_info
copy_source_files
generate_package
clean_tmp_dir

echo "DONE!"

if [ "$LVDWCMC_RESTORE_DIR" = true ]
then
	popd > /dev/null
fi