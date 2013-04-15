#!/bin/bash

DIR="$(dirname $0)"
HEADER_FILE="$DIR/app/code/local/TC/Import/data/header.csv"
IMPORT_DIR="$DIR/var/tcimport/new/"
TMP_FILE="file_`date +%Y%m%d_%H%M%S`.csv"
cp $HEADER_FILE $DIR/$TMP_FILE

for file in $(find $IMPORT_DIR -type f -name "*.csv")
do
	sed '1'd $file >> $DIR/$TMP_FILE
	rm -rf $file
done

mv $DIR/$TMP_FILE $IMPORT_DIR/