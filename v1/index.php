<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';
include_once dirname(__FILE__) . '/../include/Config.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;

/**
* Adding Middle Layer to authenticate every request
* Checking if the request has valid api key in the 'Authorization' header
*/
function authenticate(\Slim\Route $route) {
  // Getting request headers
  $headers = apache_request_headers();
  $response = array();
  $app = \Slim\Slim::getInstance();

  // Verifying Authorization Header
  if (isset($headers['Authorization'])) {
    $db = new DbHandler();

    // get the api key
    $api_key = $headers['Authorization'];
    // validating api key
    if (!$db->isValidApiKey($api_key)) {
      // api key is not present in users table
      $response["error"] = true;
      $response["message"] = "Access Denied. Invalid Api key";
      echoRespnse(401, $response);
      $app->stop();
    } else {
      global $user_id;
      // get user primary key id
      $user_id = $db->getUserId($api_key);
    }
  } else {
    // api key is missing in header
    $response["error"] = true;
    $response["message"] = "Api key is misssing";
    echoRespnse(400, $response);
    $app->stop();
  }
}

/**
* ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
*/
/**
* User Registration
* url - /register
* method - POST
* params - name, email, password
*/
$app->post('/register', function() use ($app) {
  // check for required params
  verifyRequiredParams(array('name', 'email', 'password'));

  $response = array();

  // reading post params
  $name = $app->request->post('name');
  $email = $app->request->post('email');
  $password = $app->request->post('password');

  // validating email address
  validateEmail($email);

  $db = new DbHandler();
  $res = $db->createUser($name, $email, $password);

  if ($res == USER_CREATED_SUCCESSFULLY) {
    $response["error"] = false;
    $response["message"] = "You are successfully registered";
  } else if ($res == USER_CREATE_FAILED) {
    $response["error"] = true;
    $response["message"] = "Oops! An error occurred while registereing";
  } else if ($res == USER_ALREADY_EXISTED) {
    $response["error"] = true;
    $response["message"] = "Sorry, this email already existed";
  }
  // echo json response
  echoRespnse(201, $response);
});

/**
* User Login
* url - /login
* method - POST
* params - email, password
*/
$app->post('/login', function() use ($app) {
  // check for required params
  verifyRequiredParams(array('email', 'password'));

  // reading post params
  $email = $app->request()->post('email');
  $password = $app->request()->post('password');
  $response = array();

  $db = new DbHandler();
  // check for correct email and password
  if ($db->checkLogin($email, $password)) {
    // get the user by email
    $user = $db->getUserByEmail($email);

    if ($user != NULL) {
      $response["error"] = false;
      $response['id'] = $user['id'];
      $response['name'] = $user['name'];
      $response['email'] = $user['email'];
      $response['apiKey'] = $user['api_key'];
      $response['createdAt'] = $user['created_at'];
    } else {
      // unknown error occurred
      $response['error'] = true;
      $response['message'] = "An error occurred. Please try again";
    }
  } else {
    // user credentials are wrong
    $response['error'] = true;
    $response['message'] = 'Login failed. Incorrect credentials';
  }

  echoRespnse(200, $response);
});


/*
* -------------------------- Category  & Menu --------------------------------
*/

/**
* Listing all Category
* method GET
*/
$app->get('/promo', function() {
  global $user_id;
  $response = array();
  $db = new DbHandler();

  // fetching all user tasks
  $result = $db->getPromo();

  $response["error"] = false;
  $response["promotions"] = array();

  // looping through result and preparing tasks array
  while ($task = $result->fetch_assoc()) {
    $tmp = array();
    $tmp["id"] = $task["id"];
    $tmp["menu_id"] = $task["menu_id"];
    $tmp["image"] = URL_HOST .'/images/' . $task["image"];
    $tmp["created_at"] = $task["created_at"];
    $tmp["updated_at"] = $task["updated_at"];
    array_push($response["promotions"], $tmp);
  }

  echoRespnse(200, $response);
});

/**
* Listing all Category
* method GET
*/
$app->get('/categories', function() {
  global $user_id;
  $response = array();
  $db = new DbHandler();

  // fetching all user tasks
  $result = $db->getAllCategory();

  $response["error"] = false;
  $response["categories"] = array();

  // looping through result and preparing tasks array
  while ($task = $result->fetch_assoc()) {
    $tmp = array();
    $tmp["id"] = $task["id"];
    $tmp["name"] = $task["name"];
    $tmp["created_at"] = $task["created_at"];
    $tmp["updated_at"] = $task["updated_at"];
    array_push($response["categories"], $tmp);
  }

  echoRespnse(200, $response);
});

/**
* Listing all Menu
* method GET
*/
$app->get('/menus', function() {
  global $user_id;
  $response = array();
  $db = new DbHandler();

  // fetching all user tasks
  $result = $db->getAllMenu();

  $response["error"] = false;
  $response["menus"] = array();

  // looping through result and preparing tasks array
  while ($task = $result->fetch_assoc()) {
    $tmp = array();
    $tmp["id"] = $task["id"];
    $tmp["name"] = $task["name"];
    $tmp["description"] = $task["description"];
    $tmp["category_id"] = $task["category_id"];
    $tmp["price"] = $task["price"];
    $tmp["image"] = URL_HOST .'/images/' . $task["image"];
    $tmp["created_at"] = $task["created_at"];
    $tmp["updated_at"] = $task["updated_at"];
    array_push($response["menus"], $tmp);
  }

  echoRespnse(200, $response);
});

/**
* Listing all foods
* method GET
*/
$app->get('/foods', function() {
  global $user_id;
  $response = array();
  $db = new DbHandler();

  // fetching all user tasks
  $result = $db->getFoods();

  $response["error"] = false;
  $response["menus"] = array();

  // looping through result and preparing tasks array
  while ($task = $result->fetch_assoc()) {
    $tmp = array();
    $tmp["id"] = $task["id"];
    $tmp["name"] = $task["name"];
    $tmp["description"] = $task["description"];
    $tmp["category_id"] = $task["category_id"];
    $tmp["price"] = $task["price"];
    $tmp["image"] = URL_HOST .'/images/' . $task["image"];
    $tmp["created_at"] = $task["created_at"];
    $tmp["updated_at"] = $task["updated_at"];
    array_push($response["menus"], $tmp);
  }

  echoRespnse(200, $response);
});

/**
* Listing all foods
* method GET
*/
$app->get('/beverages', function() {
  global $user_id;
  $response = array();
  $db = new DbHandler();

  // fetching all user tasks
  $result = $db->getBeverages();

  $response["error"] = false;
  $response["menus"] = array();

  // looping through result and preparing tasks array
  while ($task = $result->fetch_assoc()) {
    $tmp = array();
    $tmp["id"] = $task["id"];
    $tmp["name"] = $task["name"];
    $tmp["description"] = $task["description"];
    $tmp["category_id"] = $task["category_id"];
    $tmp["price"] = $task["price"];
    $tmp["image"] = URL_HOST .'/images/' . $task["image"];
    $tmp["created_at"] = $task["created_at"];
    $tmp["updated_at"] = $task["updated_at"];
    array_push($response["menus"], $tmp);
  }

  echoRespnse(200, $response);
});

/*
* ------------------------ METHODS WITH AUTHENTICATION ------------------------
*/

/* ------------- `desk` table method ------------------ */

/**
* Listing all Table
* method GET
*/
$app->get('/tables', 'authenticate', function() use ($app) {
  global $user_id;
  $response = array();
  $db = new DbHandler();

  // fetching all user tasks
  $result = $db->getAllTable();

  $response["error"] = false;
  $response["tables"] = array();

  // looping through result and preparing tasks array
  while ($task = $result->fetch_assoc()) {
    $tmp = array();
    $tmp["id"] = $task["id"];
    $tmp["name"] = $task["name"];
    $tmp["capacity"] = $task["capacity"];
    $tmp["available"] = $task["available"];
    $tmp["created_at"] = $task["created_at"];
    $tmp["updated_at"] = $task["updated_at"];
    array_push($response["tables"], $tmp);
  }

  echoRespnse(200, $response);
});


/**
* Listing all Category
* method GET
*/
$app->get('/availableTable', 'authenticate', function() use ($app) {
  global $user_id;
  $response = array();
  $db = new DbHandler();

  // fetching all user tasks
  $result = $db->getAvailableTable();

  $response["error"] = false;
  $response["tables"] = array();

  // looping through result and preparing tasks array
  while ($task = $result->fetch_assoc()) {
    $tmp = array();
    $tmp["id"] = $task["id"];
    $tmp["name"] = $task["name"];
    $tmp["capacity"] = $task["capacity"];
    $tmp["available"] = $task["available"];
    $tmp["created_at"] = $task["created_at"];
    $tmp["updated_at"] = $task["updated_at"];
    array_push($response["tables"], $tmp);
  }

  echoRespnse(200, $response);
});

/* ------------- `order` table method ------------------ */


/**
* Creating new order in db
* method POST
* params - name
* url - /initOrder/
*/
$app->post('/initOrder', 'authenticate', function() use ($app) {
  // check for required params
  verifyRequiredParams(array('customer_id'));

  $response = array();
  $order = $app->request->post('customer_id');

  global $user_id;
  $db = new DbHandler();

  // creating new order
  $order_id = $db->createOrder($user_id);
  if ($order_id != NULL) {
    $response["error"] = false;
    $response["message"] = "Order created successfully";
    $response["order_id"] = $order_id;
    echoRespnse(201, $response);
  } else {
    $response["message"] = "Failed to create Order. Please try again";
    echoRespnse(200, $response);
  }
});


/**
* Creating new order details in db
* method POST
* params - name
* url - /storeOrder/
*/
$app->post('/storePlaceOrder', 'authenticate', function() use ($app) {
  // check for required params
  verifyRequiredParams(array('order_id'));
  verifyRequiredParams(array('desk_id'));
  verifyRequiredParams(array('menu_id'));
  verifyRequiredParams(array('qty'));
  verifyRequiredParams(array('price_total'));

  $response = array();
  $order_id = $app->request->post('order_id');
  $desk_id = $app->request->post('desk_id');
  $menu_id = $app->request->post('menu_id');
  $qty = $app->request->post('qty');
  $price_total = $app->request->post('price_total');

  global $user_id;
  $db = new DbHandler();

  // creating new order
  $order = $db->createPlaceOrder($order_id, $desk_id, $menu_id, $qty, $price_total);
  if ($order != NULL) {
    $response["error"] = false;
    $response["message"] = "Order stored successfully";
    $response["details_order_id"] = $order;
    echoRespnse(201, $response);
  } else {
    $response["message"] = "Failed to store Order. Please try again";
    echoRespnse(200, $response);
  }
});

/**
* Creating new order details in db
* method POST
* params - name
* url - /storeOrder/
*/
$app->post('/storeDeliveryOrder', 'authenticate', function() use ($app) {
  // check for required params
  verifyRequiredParams(array('order_id'));
  verifyRequiredParams(array('menu_id'));
  verifyRequiredParams(array('qty'));
  verifyRequiredParams(array('address'));
  verifyRequiredParams(array('price_total'));

  $response = array();
  $order_id = $app->request->post('order_id');
  $menu_id = $app->request->post('menu_id');
  $qty = $app->request->post('qty');
  $address = $app->request->post('address');
  $price_total = $app->request->post('price_total');

  global $user_id;
  $db = new DbHandler();

  // creating new order
  $order = $db->createDeliveryOrder($order_id, $menu_id, $qty, $address, $phone, $notes, $price_total);
  if ($order != NULL) {
    $response["error"] = false;
    $response["message"] = "Order stored successfully";
    $response["details_order_id"] = $order;
    echoRespnse(201, $response);
  } else {
    $response["message"] = "Failed to store Order. Please try again";
    echoRespnse(200, $response);
  }
});


/**
 *
 */
 $app->get('/order/:id', 'authenticate', function($oder_id) {
   global $user_id;
   $response = array();
   $db = new DbHandler();

   // fetch task
   $result = $db->getOrderByID($oder_id);

   if ($result != NULL) {
     $response["error"] = false;
     $response["id"] = $result["id"];
     $response["customer_id"] = $result["customer_id"];
     $response["status"] = $result["status"];
     $response["created_at"] = $result["created_at"];
     echoRespnse(200, $response);
   } else {
     $response["error"] = true;
     $response["message"] = "The requested resource doesn't exists";
     echoRespnse(404, $response);
   }
 });


 /**
 * Updating place order
 * method PUT
 * params $order_id
 * url - /order/:id
 */
 $app->put('/order/:id', 'authenticate', function($order_id) use($app) {
   // check for required params
  //  verifyRequiredParams(array('order_id'));

   global $user_id;
  //  $order_id = $app->request->put('order_id');

   $db = new DbHandler();
   $response = array();

   // updating task
   $result = $db->updatePlaceOrder($order_id);
   if ($result) {
     // task updated successfully
     $response["error"] = false;
     $response["message"] = "Task updated successfully";
   } else {
     // task failed to update
     $response["error"] = true;
     $response["message"] = "Task failed to update. Please try again!";
   }
   echoRespnse(200, $response);
 });

 /**
 * Listing complete order || history
 * method GET
 */
 $app->get('/completeOrder/:customer_id', 'authenticate', function($customer_id) use ($app) {
   global $user_id;
   $response = array();
   $db = new DbHandler();

   // fetching all user tasks
   $result = $db->getCompleteHistoryByCustomerId($customer_id);

   $response["error"] = false;
   $response["history"] = array();

   // looping through result and preparing tasks array
   while ($task = $result->fetch_assoc()) {
     $tmp = array();
     $tmp["id"] = $task["id"];
     $tmp["customer_id"] = $task["customer_id"];
     $tmp["status"] = $task["status"];
     $tmp["created_at"] = $task["created_at"];
     array_push($response["history"], $tmp);
   }

   echoRespnse(200, $response);
 });

 /**
 * Deleting a menu from place order
 * method DELETE
 * url /tasks
 */
 $app->delete('/p/delete/:id', 'authenticate', function($id) use($app) {
   global $user_id;

   $db = new DbHandler();
   $response = array();
   $result = $db->deleteMenuPlaceOrder($id);
   if ($result) {
     // task deleted successfully
     $response["error"] = false;
     $response["message"] = "Menu deleted succesfully";
   } else {
     // task failed to delete
     $response["error"] = true;
     $response["message"] = "Menu failed to delete. Please try again!";
   }
   echoRespnse(200, $response);
 });

 /**
 * Deleting a menu from place order
 * method DELETE
 * url /tasks
 */
 $app->delete('/d/delete/:id', 'authenticate', function($id) use($app) {
   global $user_id;

   $db = new DbHandler();
   $response = array();
   $result = $db->deleteMenuDeliveryeOrder($id);
   if ($result) {
     // task deleted successfully
     $response["error"] = false;
     $response["message"] = "Menu deleted succesfully";
   } else {
     // task failed to delete
     $response["error"] = true;
     $response["message"] = "Menu failed to delete. Please try again!";
   }
   echoRespnse(200, $response);
 });


 /**
 * Listing complete order || history
 * method GET
 */
 $app->get('/cart/place/:id', 'authenticate', function($order_id) use ($app) {
   global $user_id;
   $response = array();
   $db = new DbHandler();

   // fetching all user tasks
   $result = $db->getPlaceOrderDetails($order_id);

   $response["error"] = false;
   $response["cart"] = array();

   // looping through result and preparing tasks array
   while ($task = $result->fetch_assoc()) {
     $tmp = array();
     $tmp["id"] = $task["id"];
     $tmp["order_id"] = $task["order_id"];
     $tmp["desk_id"] = $task["desk_id"];
     $tmp["menu_id"] = $task["menu_id"];
     $tmp["qty"] = $task["qty"];
     $tmp["price_total"] = $task["price_total"];
     $tmp["status"] = $task["status"];
     $tmp["created_at"] = $task["created_at"];
     $tmp["updated_at"] = $task["updated_at"];
     array_push($response["cart"], $tmp);
   }

   echoRespnse(200, $response);
 });

 /**
 * Listing complete order || history
 * method GET
 */
 $app->get('/cart/delivery/:id', 'authenticate', function($order_id) use ($app) {
   global $user_id;
   $response = array();
   $db = new DbHandler();

   // fetching all user tasks
   $result = $db->getPlaceOrderDetails($order_id);

   $response["error"] = false;
   $response["cart"] = array();

   // looping through result and preparing tasks array
   while ($task = $result->fetch_assoc()) {
     $tmp = array();
     $tmp["id"] = $task["id"];
     $tmp["order_id"] = $task["order_id"];
     $tmp["desk_id"] = $task["desk_id"];
     $tmp["menu_id"] = $task["menu_id"];
     $tmp["qty"] = $task["qty"];
     $tmp["address"] = $task["address"];
     $tmp["phone"] = $task["phone"];
     $tmp["notes"] = $task["notes"];
     $tmp["price_total"] = $task["price_total"];
     $tmp["status"] = $task["status"];
     $tmp["created_at"] = $task["created_at"];
     $tmp["updated_at"] = $task["updated_at"];
     array_push($response["cart"], $tmp);
   }

   echoRespnse(200, $response);
 });

/**
* Verifying required params posted or not
*/
function verifyRequiredParams($required_fields) {
  $error = false;
  $error_fields = "";
  $request_params = array();
  $request_params = $_REQUEST;
  // Handling PUT request params
  if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $app = \Slim\Slim::getInstance();
    parse_str($app->request()->getBody(), $request_params);
  }
  foreach ($required_fields as $field) {
    if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
      $error = true;
      $error_fields .= $field . ', ';
    }
  }

  if ($error) {
    // Required field(s) are missing or empty
    // echo error json and stop the app
    $response = array();
    $app = \Slim\Slim::getInstance();
    $response["error"] = true;
    $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
    echoRespnse(400, $response);
    $app->stop();
  }
}

/**
* Validating email address
*/
function validateEmail($email) {
  $app = \Slim\Slim::getInstance();
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response["error"] = true;
    $response["message"] = 'Email address is not valid';
    echoRespnse(400, $response);
    $app->stop();
  }
}

/**
* Echoing json response to client
* @param String $status_code Http response code
* @param Int $response Json response
*/
function echoRespnse($status_code, $response) {
  $app = \Slim\Slim::getInstance();
  // Http response code
  $app->status($status_code);

  // setting response content type to json
  $app->contentType('application/json');

  echo json_encode($response);
}

$app->run();
?>
