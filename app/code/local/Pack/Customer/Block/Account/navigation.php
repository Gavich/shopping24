<?php

/**
 * Customer account navigation sidebar
 * Class for removing links from navigation sidebar
 * 
 * @category   Local Packets
 * @package    Examples
 * @author     Dmitiy Seryogin
 */

class Pack_Customer_Block_Account_Navigation extends Mage_Customer_Block_Account_Navigation
{

    protected $_links = array();

    protected $_activeLink = false;

    public function removeLinkByName($name)
    {
        foreach($this->_links as $k => $v) {
            if($v->getName() == $name) {
                unset($this->_links[$k]);
            }
        }

        return $this;
    }  
    
    public function removeLinkByLabel($label)
    {
        foreach($this->_links as $k => $v) {
            if($v->getLabel() == $label) {
                unset($this->_links[$k]);
            }
        }

        return $this;
    }  
    
    public function orderLinksByArray($order_array=null)    
    {
        $order_array = array(
            0 => 'Кабинет пользователя',
            1 => 'Мои заказы',            
            2 => 'Мой профиль (контакты)',            
            3 => 'Мой профиль (контакты)1',             
            4 => 'Понравившиеся товары',
            5 => 'Последние просмотренные товары',
            6 => 'Рассылка',
            7 => 'Мои отзывы о товарах',
            8 => 'Мои заметки о товарах',
            9 => 'Мои заметки по товарам'            
        );
        
        $o_links = array();
        
        foreach ($order_array as $k => $v)
        {
           foreach($this->_links as $k1 => $v1) {
              if($v1->getLabel() == $v) $o_links[$k] = $v1;    
           }           
        }            
        $this->_links = $o_links;
        
        return $this;
    }     
}
