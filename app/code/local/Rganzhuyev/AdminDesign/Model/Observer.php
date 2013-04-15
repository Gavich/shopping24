<?php
/*
 * @todo Move buttons from template to $_additionalButtons property of Adminhtml_Block_Catalog_Category_Edit_Form.php
 */
class Rganzhuyev_AdminDesign_Model_Observer {
    public function onControllerLoad() {
        Mage::getDesign()->setTheme('adminDesign');
    }
}