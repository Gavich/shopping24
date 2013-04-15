<?php
/**
 * @category TC
 * @package TC_Varnish
 * @author Aleksandr Smaga <smagaan@gmail.com>
 */

class TC_Varnish_Helper_Data extends Mage_Core_Helper_Abstract{

	/**
	 * Varnish servers list
	 * @var array
	 */
	protected $_servers = null;

	/**
	 * Flag that indicates if the module is active
	 * @var bool
	 */
	protected $_isActive = null;

	/**
	 * Check if model is active
	 * @return bool
	 */
	public function isActive(){
		if (is_null($this->_isActive)){
			$servers = $this->getServers();
			$this->_isActive = (bool)(Mage::getStoreConfig('tc_varnish/general/active') && !empty($servers));
		}
		return $this->_isActive;
	}

	/**
	 * Get varnish servers
	 * @return array
	 */
	public function getServers(){
		if (is_null($this->_servers)){
			$this->_servers = array();
			$servers = trim(Mage::getStoreConfig('tc_varnish/general/servers'), ' ');
			if (!empty($servers)){
				$servers = explode(';', $servers);
				foreach ($servers as $server) {
					$parts = explode(':', $server);
					$result = array(
						'host' => $parts[0],
						'port' => $parts[1]
					);
					if (!empty($parts[2])){
						$result['secret'] = $parts[2];
					}
					$this->_servers[] = $result;
				}
			}
		}
		return $this->_servers;
	}
}