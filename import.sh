#!/bin/bash

DIR="$(dirname $0)"

SHELL="$DIR/shell"
for i in {150000..155000..500};
do
	php -f $SHELL/product_import.php startAccumulating --limit 500,$i;
	sleep 5;
	bash $DIR/merge.sh;
	php -f $SHELL/product_import.php startPopulating;
	sleep 5;
	php -f $SHELL/product_import.php startImagesProcess;
done
php -f $SHELL/indexer.php reindexall