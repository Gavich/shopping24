<?php


require 'app/Mage.php';
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

//Mage::app();
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

        for ($ind=617467;$ind<=617467;$ind++)
		{
		$product = Mage::getModel('catalog/product')->load($ind); //
		$productmagentoattributes = $product->getAttributes();
		$type=$productmagentoattributes['type_id']->getFrontend()->getValue($product);
		$sku=$productmagentoattributes['sku']->getFrontend()->getValue($product);
		$initimag=$productmagentoattributes['init_image']->getFrontend()->getValue($product);
		echo $initimag.'<BR>';
		$yImages=$product->getMediaGalleryImages();
		foreach($yImages as $image)
		{
        echo $image["id"].'  '.$image["url"].'  '.$image["path"].' ';
		preg_match('/[\.a-zA-z0-9]*$/',$image["path"],$image_name_ar);
		$image_name=$image_name_ar[0];
		echo $image_name.'<BR>';
        }
		if ($type!='simple')
				{
$childProducts = Mage::getModel('catalog/product_type_configurable')
                    ->getUsedProducts(null,$product);   
		foreach($childProducts as $child){
				echo $child->getName().' ';  
				echo $child->getId().'<BR>'; 
			$childattributes = $child->getAttributes();
			/*
			foreach ($childattributes as $childattribute)
			{
			$childattributescode = $childattribute->getAttributeCode();
			echo $childattributescode.' ';
			}*/
		$ima_child=$childattributes['init_image']->getFrontend()->getValue($child);	
		echo 'immm===='.$ima_child.' ';
		$childyImages=$child->getMediaGalleryImages();
		if (isset($childyImages))
		foreach($childyImages as $childimage)
		{
        echo $childimage["id"].'  '.$childimage["url"].'  '.$childimage["path"].'<BR>';
        }
		
		$child_images_arr=explode(',',$ima_child);
		foreach ($child_images_arr as $child_images_el)
			{
			$child_images_el=str_replace(' ','',$child_images_el);
		    foreach($yImages as $image)
				{
			
				preg_match('/[\.a-zA-z0-9]*$/',$image["path"],$image_name_ar);
				$image_name=$image_name_ar[0];
				if ($child_images_el==$image_name)
				   {
				   $child_else=Mage::getModel('catalog/product')->load($child->getId());
				   $child_else->addImageToMediaGallery($image["path"],
				    array('image', 'thumbnail', 'small_image'),
					false,
					false);
				/*   $child->addImageToMediaGallery($image["path"], 
				    array('image', 'thumbnail', 'small_image'),
					false,
					false);*/
				   		try {
							$child_else->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID)->save();
							/*$child->save();*/
							echo "Success! Id: ".$child->getId();
							}
							catch (Exception $e)
									{
									echo "------------------------------------------------------------------------------------------------------";
									echo "------------------------------------------------------------------------------------------------------";
									echo $e->getMessage();
									}
				    
				echo '<BR>eee='.$image_name.'<BR>';
					}
				}
			} 

		}

				}

		}

?>