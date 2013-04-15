<?php

/**
 * Additional process, used for console removing products
 */
class TC_Import_Model_Additional_Processor extends TC_Import_Model_Processor
{
    /** @var From date param */
    protected $_date         = null;

    const TABLE_NAME         = 'product_info';
    const DATE_COLUMN        = 'update_date';
    const ORIGINAL_ID_COLUMN = 'id_product';

    /**
     * Log prefix
     *
     * @return string
     */
    public function getLogPrefix()
    {
        return 'additional';
    }

    /**
     * Runs process
     *
     * @return void
     */
    public function run()
    {
        $date = new Zend_Date($this->_date, 'ru_RU');

        if ($this->_date == null){
            $date->subDay(1);
        }

        $date = $date->get('Y-MM-dd');
        $this->log('Process started: from date is:' . $date);

        fwrite(STDOUT, ">>>> WARNING:\n\t\tThis call will delete products\n\t\t"
                . "Please check \"from date\"\n\t\t{$date}  -  format is YEAR-MONTH-DAY\n\t\t"
                . "Please enter Y if it's OK:  "
        );
        $result = strtolower(chop(fgets(STDIN)));

        if ($result == 'y') {
            $select = $this->getAdapter()->select();

            $select->from(array('pi' => self::TABLE_NAME), array(self::ORIGINAL_ID_COLUMN))
                   ->where(self::DATE_COLUMN . ' < ?', $date)
                   ->order(self::DATE_COLUMN  . ' ASC');

            $products = $this->getAdapter()->fetchCol($select);

            $count = count($products);

            $this->log('Found products: ' . $count);
            echo ('Found products: ' . $count) . PHP_EOL;

            if ($count) {
                /* @var $resource Mage_Core_Model_Resource */
                $resource = Mage::getSingleton('core/resource');
                $connection = $resource->getConnection('core_write');

                /* @var $config Mage_Eav_Model_Config */
                $config = Mage::getModel('eav/config');
                /** @var $attribute Mage_Eav_Model_Entity_Attribute_Abstract */
                $attribute = $config->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'original_id');

                $select = $connection->select()
                                ->from(array('e' => $resource->getTableName('catalog/product')), array())
                                ->join(
                                    array('a' => $attribute->getBackendTable()),
                                    'a.entity_id = e.entity_id AND a.store_id = 0 AND a.attribute_id = ' . $attribute->getId(),
                                    array())
                                ->where('a.value IN(?)', $products);

                $this->log('Deleting products: ' . PHP_EOL . "\t" . join(PHP_EOL . "\t", $products));

                $query = $connection->query($select->deleteFromSelect('e'));
                $rows = $query->rowCount();

                echo 'Affected products: ' . $rows . PHP_EOL;
                $this->log('Affected products: ' . $rows);

                $select = $connection->select()
                                ->from(array('e' => $resource->getTableName('catalog/product')), array())
                                ->join(
                                    array('l' => $resource->getTableName('catalog/product_relation')),
                                    'l.child_id = e.entity_id',
                                array())
                                ->where("e.type_id = 'simple' AND l.parent_id IS NULL");


                $query = $connection->query($select->deleteFromSelect('e'));
                $rows = $query->rowCount();

                $select = $connection->select()
                    ->from(array('e' => $resource->getTableName('catalog/product')), array())
                    ->joinLeft(
                        array('l' => $resource->getTableName('catalog/product_relation')),
                        'e.entity_id = l.child_id',
                        array())
                    ->where("e.type_id = 'simple' AND l.child_id IS NULL");

                $query = $connection->query($select->deleteFromSelect('e'));
                $rows = $rows + $query->rowCount();

                echo 'Removed products without parent: ' . $rows . PHP_EOL;
                $this->log('Removed products without parent: ' . $rows);
            }

            echo PHP_EOL . 'Done' . PHP_EOL;
            $this->log('Done');
        }
    }

    /**
     * Log message to loger's storage
     * @param  string $message
     * @param  integer $level
     * @return void(0)
     */
    protected function log($message, $level = 6)
    {
        if (!Mage::getStoreConfigFlag('tcimport_database/tcimport_additional_group/logs'))
            return false;

        $this->getLoger()->log($message, $level);
    }

    /**
     * Set from date
     *
     * @param $date
     * @return TC_Import_Model_Additional_Processor
     */
    public function setFrom($date)
    {
        $this->_date = $date;

        return $this;
    }
}
