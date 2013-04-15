<?php
/**
 * @category TC
 * @package TC_Import
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Import_Model_Cron{

	/**
	 * Start import categories process
	 * @return bool (is successful)
	 */
	public function importCategories(){
		$factory = Mage::getModel('tcimport/import');

		return $factory::init('category')->run();
	}

}