<?php 
defined('_JEXEC') or die('Restricted access');

error_reporting(E_ALL ^ E_NOTICE);
$db = JFactory::getDBO();

jimport('joomla.environment.request');
$key = JRequest::getString('key');
$type = JRequest::getString('type');
$action = JRequest::getString('action');
$data = JRequest::getVar('data');

//now figure out what method to call
$method = $type . '_' . $action;

function prepareObject($db, $table, $data){
  $types = $db->getTableFields($table);
  $obj = new StdClass();
  foreach($data as $key => $value){
    if(array_key_exists($key,$types[$table])){
      $obj->$key = $value;
    }
  }
  return $obj;
}

function product_find($db,$data){
  $sql = "SELECT * FROM #__vm_product WHERE product_sku='" . $data['product_sku'] . "'";
  $db->setQuery($sql);
  $db->query();
  if($db->getNumRows()){
    return (object) $db->loadAssoc();
  }
  return false;
}

function product_update($db, $data){
  $table = '#__vm_product';
  $obj = prepareObject($db, $table, $data);
  $result = $db->updateObject($table,$obj,'product_sku');
  if($result){
    $product = product_find($db,$data);
    $data['product_id'] = $product->product_id;
    $table = '#__vm_product_price';
    $obj = prepareObject($db, $table, $data);
    $result = $db->updateObject($table, $obj, 'product_id'); 
    
    return $result;
  }
  return false;
}

function product_create($db, $data){
  $table = '#__vm_product';
  $obj = prepareObject($db, $table, $data);
  //insert the product data
  $result = $db->insertObject($table, $obj, 'product_id');
  //insert the product price, this is nasty
  if($result){
    $product = product_find($db,$data);
    $data['product_id'] = $product->product_id;
    $table = '#__vm_product_price';
    $obj = prepareObject($db, $table, $data);
    print 'preparing product price ' . print_r($obj,true);
    $result = $db->insertObject($table, $obj, 'product_id'); 
    return $result;
  }
  return false;
}

function product_delete($db,$data){
  if(isset($data['product_sku'])){
    $sql = "DELETE FROM #__vm_product WHERE product_sku='" .
    $data['product_sku'] . "'";
    $db->setQuery($sql);
    return ($db->query() !== false);
  }
  return false;
}

function customer_find($db, $data){
  $sql = "SELECT * FROM #__vm_user_info WHERE user_id=" . $data['user_id'];
  $db->setQuery($sql);
  $db->query();
  if($db->getNumRows()){
    return (object) $db->loadAssoc();
  }
  return false;
  //
}

function customer_create($db,$data){

  print_r($data);
/**
 * 1. Create the joomla user in #__user
 * 2. Create the customer in #__vm_user_info
 */
//Create the user properly

  $usertable = '#__users';
  $userdata = $data;
  $userdata['id'] = $data['user_id'];
  $userdata['email'] = $data['user_email'];
  $userdata['username'] = $data['user_email'];
  $userdata['block'] = 1;
  $userdata['gid'] = 18;
  $userdata['usertype'] = 'Registered';
  $userobj = prepareObject($db, $usertable,$userdata);
  $result = $db->insertObject($usertable, $userobj, 'id');
  
  $arotable = '#__core_acl_aro';
  $arodata['section_value'] = 'users';
  $arodata['value'] = $data['user_id'];
  $arodata['order_value'] = 0;
  $arodata['name'] = $data['name'];
  $arodata['hidden'] = 0;
  $aroobj = prepareObject($db, $arotable,$arodata);
  $result = $db->insertObject($arotable, $aroobj, 'id');
  
  $arogrouptable = '#__core_acl_groups_aro_map';
  $arogroupdata['group_id'] = 18;
  $arogroupdata['aro_id'] = $db->insertId();
  $arogroupobj = prepareObject($db, $arogrouptable, $arogroupdata);
  $result = $db->insertObject($arogrouptable,$arogroupobj);

//Create the customer with the data received
  $table = '#__vm_user_info';
  $data['address_type'] = 'BT';
  $data['cdate'] = time();
  $data['mdate'] = time();
  $data['user_info_id'] = md5($data['user_id']);
  $obj = prepareObject($db, $table, $data);
  //insert the product data
  $result = $db->insertObject($table, $obj, 'user_info_id');
  //insert the product price, this is nasty
  print_r($result);
  return ($result !== false);
 
}

function customer_update($db,$data){
//Create the customer with the data received
  $table = '#__vm_user_info';

  //find the user_info_id
  $sql = "SELECT * FROM " . $table . " WHERE user_id=" . $data['user_id'] . " AND address_type='BT'";
  $db->setQuery($sql);
  $db->query();
  if($user = $db->loadAssoc()){
    $data['mdate'] = time();
    $data['user_info_id'] = $user['user_info_id'];
    $obj = prepareObject($db, $table, $data);
    //update the product data
    $result = $db->updateObject($table, $obj, 'user_info_id');
  } else {
    $data['address_type'] = 'BT';
    $data['cdate'] = time();
    $data['mdate'] = time();
    $data['user_info_id'] = md5($data['user_id']);
    $obj = prepareObject($db, $table, $data);
    //insert the product data
    $result = $db->insertObject($table, $obj, 'user_info_id');
  }
  return ($result !== false);
 }

function customer_delete($db, $data){
  //also delete the user as well!?
  if(isset($data['user_id'])){
    $sql = "DELETE FROM #__vm_user_info WHERE user_id=" .
    $data['user_id'];
    $db->setQuery($sql);
    return ($db->query() !== false);
  }
  return false;
}

function order_find($db, $data){
  $sql = "SELECT * FROM #__vm_order WHERE order_id=" . $data['order_id'];
  $db->setQuery($sql);
  $db->query();
  if($db->getNumRows()){
    return (object) $db->loadAssoc();
  }
  return false;
}

function order_create($db, $data){
  $table = '#__vm_order';
  $obj = prepareObject($db, $table, $data);
  //insert the product data
  $result = $db->insertObject($table, $obj, 'order_id');
  //insert the product price, this is nasty
  return ($result !== false);
}

function order_update($db, $data){
  $table = '#__vm_order';
  $obj = prepareObject($db, $table, $data);
  //insert the product data
  $result = $db->insertObject($table, $obj, 'order_id');
  //insert the product price, this is nasty
  return ($result !== false);
}

function order_delete($db, $data){
  if(isset($data['order_id'])){
    $sql = "DELETE FROM #__vm_order WHERE order_id=" .
    $data['order_id'];
    $db->setQuery($sql);
    return ($db->query() !== false);
  }
  return false;
}

function orderline_find($db, $data){
  $sql = "SELECT * FROM #__vm_order_item WHERE order_item_sku='" .
  $data['order_item_sku'] . "'";
  $db->setQuery($sql);
  $db->query();
  if($db->getNumRows()){
    return (object) $db->loadAssoc();
  }
  return false;
}

function orderline_create($db, $data){
  $table = '#__vm_order_item';
  $obj = prepareObject($db, $table, $data);
  //insert the product data
  $result = $db->insertObject($table, $obj);
  //insert the product price, this is nasty
  return ($result !== false);
}

function orderline_update($db, $data){
  $table = '#__vm_order_item';
  $sql = "UPDATE #__vm_order_item SET ";
  $obj = prepareObject($db, $table, $data);
  $types = $db->getTableFields($table);
  $unquoted = array('int','decimal');
  foreach($obj as $key=>$value){
    $type = $types[$key];

    $fields[] = $key . '=' . (in_array($type,$unquoted) ? $value :
    $db->Quote($value));
  }
  $sql .= implode(', ',$fields);
  $sql .= " WHERE order_id='" . $data['order_id'] . " AND
  order_item_sku='" . $data['order_item_sku'] . "'";
  $db->setQuery($sql);
  $result = $db->query();
  //insert the product price, this is nasty
  return ($result !== false);
}

function orderline_delete($db, $data){
  if(isset($data['order_id'],$data['order_item_sku'])){
    $sql = "DELETE FROM #__vm_order_item WHERE order_id=" .
    $data['order_id'] . " AND order_item_sku='" .
    $data['order_item_sku'];
    $db->setQuery($sql);
    return ($db->query() !== false);
  }
  return false;
}

//throw in some security that is checking the key...
if(function_exists($method)){
  $response = call_user_func($method,$db,$data);
  die(json_encode($response,true));
} else {
  die('some error happened');
}
