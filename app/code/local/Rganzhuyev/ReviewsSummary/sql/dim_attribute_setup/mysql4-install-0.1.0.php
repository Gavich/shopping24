<?php
$installer = $this;
if($installer->getAttributeId('catalog_product', 'dim1')) {
    $installer->updateAttribute('catalog_product', 'dim1', 'frontend_label', 'Цвет');
}
if($installer->getAttributeId('catalog_product', 'dim2')) {
    $installer->updateAttribute('catalog_product', 'dim2', 'frontend_label', 'Размер');
}