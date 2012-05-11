<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
 
// Import library dependencies
jimport('joomla.plugin.plugin');

require_once(JPATH_ROOT.DS.'plugins'.DS.'system'.DS.'iex-api'.DS.'iex_client_api.php');
 
/*
function delete_product($data){

				//$api = $this->iexClient();

				$products = $_POST['product_id']; // get products

				foreach ( $products as $product ) : // loop products

					// SELECT productdata from db

					$productdata = array();
	
					//$api->addTransfer('product',IEX_DELETE,$productdata);	// add delete

				endforeach;

					//$api->doTransfer();	

}
*/

class plgSystemIex extends JPlugin
{

 	function plgSystemIex( &$subject, $config )
 	{
    	parent::__construct($subject, $config);
 	}

 	function iexClient()
 	{
		$customer = $this->params->get( 'customer' );
		$link = $this->params->get( 'link' );
		$secret = $this->params->get( 'secret' );
    	return new IexClientApi( $customer, $link, $secret );
	}

	/**
 	* Plugin method with the same name as the event will be called automatically.
 	*/
 	function onAfterInitialise()
 	{
   		$app = &JFactory::getApplication();
 
		$option = JRequest::getVar( 'option' );
		if ( $option != 'com_virtuemart' ) return true;

		$func = JRequest::getVar( 'func' );		
    $db = JFactory::getDBO();

    $data = $_POST;
      switch($func){
        case 'productUpdate':
        case 'productAdd':
          $this->transfer('product',$data);
        break;

        case 'productDelete': 
          $this->delete('product', $data);
        break;

        case 'userUpdate':
        case 'userAdd':
          $this->transfer('customer',$data);
        break;
        case 'userAddressAdd':
        case 'userAddressUpdate':

//          $this->transfer('customer',$data);
        break;

        case 'checkoutProcess':
          if(array_shift($data['checkout_this_step']) !=
            'CHECK_OUT_GET_FINAL_CONFIRMATION'){
            //find the last order created by the user
            break;
          }
        case 'orderStatusSet':
          $sql = "SELECT * FROM #__vm_orders WHERE user_id=" .
              $data['user_id'] . " ORDER BY cdate DESC LIMIT 0,1";
          $db->setQuery($sql);
          $db->query();
          if($db->getNumRows()){
            $order = $db->loadAssoc();
            //get order address info from #__vm_order_user_info
           $sql = "SELECT * FROM #__vm_order_user_info WHERE order_id=" .
              $order['order_id'] . " AND address_type='BT'";
            $db->setQuery($sql);
            $db->query();
            $address = $db->loadAssoc();
            foreach($address as $key=>$value){
              $order['billing_' . $key] = $value;
            }
            $sql = "SELECT * FROM #__vm_order_user_info WHERE order_id=" .
              $order['order_id'] . " AND address_type='ST'";
            $db->setQuery($sql);
            $db->query();
            $address = $db->loadAssoc();
            foreach($address as $key=>$value){
              $order['shipping_' . $key] = $value;
            }
             
            $this->transfer('order',$order);
          }
          break; 
      }
 	}

  function transfer($type,$data){
    $api = $this->iexClient();
    $db = JFactory::getDBO();
	  switch($type){
      case 'product':
				$api->addTransfer('product',IEX_TRANSFER,$data);
      break;
      case 'customer':
        $api->addTransfer('customer',IEX_TRANSFER,$data);
        break;
      case 'order':
	      $api->addTransfer('order',IEX_TRANSFER,$data);
        $sql = "SELECT * FROM #__vm_order_item WHERE order_id=" . $data['order_id'];
        
        $db->setQuery($sql);
        $db->query();
        $orderlines = $db->loadAssocList();
        foreach($orderlines as $orderline){
            $api->addTransfer('orderline',IEX_TRANSFER,$orderline);
        }
        break;
    }
    $api->doTransfer();
  }

  function delete($type,$data){
/*    $api = $this->iexClient();
    $api->addTransfer($type,IEX_DELETE,$data);
    $api->doTransfer();*/
  }
}

