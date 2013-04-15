<?php
	/**
	 * @category TC
	 * @package TC_Import
	 * @author Aleksandr Smaga <smagaan@gmail.com>
	 */

class TC_Import_Model_Updater{

	/**
	 * Parse dimensions array by url
	 * @param $id
	 * @param $storeId
	 * @return array
	 * @throws Exception
	 */
	public function getDimensionsArray($id, $storeId){
		$result = array();
		$url = Mage::getResourceModel('catalog/product')->getAttributeRawValue($id, 'original_url', $storeId);
        //удаляем  лишнее с урл 
		Mage::log($url."    ", 1, 'gavich.log');	
		$url=preg_replace("/;[a-zA-Z0-9\-\=\_]*/","",$url);
		Mage::log($url."    ", 1, 'gavich.log');	
		$config = array(
			'adapter'   => 'Zend_Http_Client_Adapter_Curl',
			'curloptions' => array(CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 2),
			);
		$client = new Zend_Http_Client($url, $config);
        $response = $client->request('GET');
		/*
		Mage::log($response->getStatus()."==status    ", 1, 'gavich.log');	
		$redi=$response->isRedirect();
		if ($redi===true) {Mage::log("==poslali    ", 1, 'gavich.log');	}
		 else {Mage::log("==neposlali    ", 1, 'gavich.log');	}
		 $redi1=(string)$redi;
		Mage::log($redi1."==redir    ", 1, 'gavich.log');	
	
		$hedi=$response->getHeadersAsString();
		Mage::log($hedi."==header  ssss  ", 1, 'gavich.log');	
			Mage::log($client->getUri()."== ", 1, 'gavich.log');	
		Mage::log($client->getRedirectionsCount ()."==count ", 1, 'gavich.log');	*/
	
		if ($response->getStatus() != 200){
			throw new Exception('Bad url for id: ' . $id);
		}

		if (preg_match('#var dimensions\s*=\s*({.*});\s+#Usi', $response->getBody(), $matches)){
	        $matches[1]=mb_convert_encoding($matches[1],"UTF-8");
			$dimensions = Zend_Json::decode($matches[1]); 	

		}else{
			throw new Exception('Dimensions array doesn\'t found id: ' . $id);
		}

		if (!isset($dimensions['articles'])){
			throw new Exception('Articles key doesn\'t exist id: ' . $id);
		}

		$multiplier = 1;
		if (Mage::getStoreConfig('tcimport_database/tcimport_import_group/multiplier') > 1){
			$multiplier = (float)Mage::getStoreConfig('tcimport_database/tcimport_import_group/multiplier');
		}
        
		foreach((array) $dimensions['articles'] as $article){Mage::log("start", 1, 'gavich.log');
			if (isset($article['variations'])){
				foreach((array)$article['variations'] as $variation){
				    /// обрезаем sku
					Mage::log(" sku  ".$variation['sku'], 1, 'gavich.log');	
				    $my_sku_internal=$variation['sku'];
			    	$my_sku_internal= substr($my_sku_internal,0,strlen($my_sku_internal)-5);
					$result[$my_sku_internal] = array();
					if (isset($variation['oldPrice'])){
						$variation['oldPrice'] = number_format((float)$variation['oldPrice'] * $multiplier, 2);
						$result[$my_sku_internal]['oldPrice'] = $variation['oldPrice'];
					}
					$variation['price'] = number_format((float)$variation['price'] * $multiplier, 2);
					$result[$my_sku_internal]['price'] = $variation['price'];
					$result[$my_sku_internal]['availabilityCode'] = $variation['availabilityCode'];
					Mage::log($my_sku_internal."   ".$result[$my_sku_internal]['availabilityCode'], 1, 'gavich.log');	
					if (isset($variation['availabilityText'])){
						$result[$my_sku_internal]['availabilityText'] = $variation['availabilityText'];
						
					}

					$result[$my_sku_internal]['availability'] = $variation['availabilityCode'] == 'lieferbar' ? true : false;
				}
			}
		}
	
		return $result;
	}
}