<body style="white-space: nowrap;">
<?php
include_once ("salesAutopilot.php");
	
function showStatus($name, $status)
{
	global $result;
	echo "<strong>" . $name . ": " . ($status ? '<span style="color:#0e0;">OK</span>: ' : '<span style="color:#e00;">ERROR</span>: ') . "</strong>"; echo var_dump($result) . "<br/>";
}
	
define('SA_USERNAME','phpapi');
define('SA_PASSWORD','7a467f0b91a54bc1eaa50fbc81e643c1');
define('SA_CRM_LIST_ID',68991);
define('SA_SING_UP_FORM_ID',123955);
define('SA_MODIFY_FORM_ID',123956);
define('SA_LANDING_PAGE_ID',22230);
define('SA_ORDER_LIST_ID',68993);
define('SA_ORDER_WITH_1_PROD_FORM_ID',124052);
define('SA_ORDER_WITH_MORE_PROD_FORM_ID',123957);	
define('SA_SEGMENT_ID',111809);
define('SA_SCHEDULE_ID',341733);
define('SA_PROD_1_ID',595327);
define('SA_PROD_1_SKU',"P1");
define('SA_PROD_2_ID',595759);
define('SA_PROD_2_SKU',"P2");
define('SA_SHIPPING_METHOD',33714);
	
	
$salesAutopilot = new salesAutopilot(SA_CRM_LIST_ID, SA_SING_UP_FORM_ID, SA_USERNAME, SA_PASSWORD, false); // SSL:false

echo "<h2>Handling subscribers</h2>";
	
$id = $result = $salesAutopilot->subscribe(array(
	"email" => "john.doo@salesautopilot.com",
    "mssys_firstname" => "John",
    "mssys_lastname" => "Doo"));
showStatus("subscribe", $result>0 && !$salesAutopilot->get_error());

$result = $salesAutopilot->updateById($id, array(
	"email" => "doo.john@salesautopilot.com"));
showStatus("update (updateById)", !$salesAutopilot->get_error());

$result = $salesAutopilot->updateByField("email", "doo.john@salesautopilot.com", array(
	"email" => "john.doo@salesautopilot.com"));
showStatus("update (updateByField)", !$salesAutopilot->get_error());

$result = $salesAutopilot->listAll();
showStatus("list (listAll)", !$salesAutopilot->get_error());
	
$result = $salesAutopilot->listTotalCount();
showStatus("listTotalCount", !$salesAutopilot->get_error());

$result = $salesAutopilot->listById($id);
showStatus("list (listId)", !$salesAutopilot->get_error());

$result = $salesAutopilot->listByField("mssys_firstname","John");
showStatus("list (listByField)", !$salesAutopilot->get_error());

$result = $salesAutopilot->listByEmail("john.doo@salesautopilot.com");
showStatus("list (listByEmail)", !$salesAutopilot->get_error());
	
$result = $salesAutopilot->updateFormLink(SA_MODIFY_FORM_ID,6);
showStatus("updateFormLink", !$salesAutopilot->get_error());
	
$result = $salesAutopilot->landingPageLink(SA_LANDING_PAGE_ID,6);
showStatus("landingPageLink", !$salesAutopilot->get_error());

$result = $salesAutopilot->filteredListWithValue(SA_SEGMENT_ID);
showStatus("filteredList", !$salesAutopilot->get_error());

$result = $salesAutopilot->filteredListOrderWithValue("subdate", "desc", 1, SA_SEGMENT_ID);
showStatus("filteredListOrder", !$salesAutopilot->get_error());
	
$result = $salesAutopilot->getSegmentNum(SA_SEGMENT_ID);
showStatus("getSegmentNum", !$salesAutopilot->get_error());

$result = $salesAutopilot->unsubscribeById($id);
showStatus("unsubscribe (unsubscribeById)", $result>0 && !$salesAutopilot->get_error());

$result = $salesAutopilot->unsubscribeByEmail("john.doo@salesautopilot.com");
showStatus("unsubscribe (unsubscribeByEmail)", $result>0 && !$salesAutopilot->get_error());

$result = $salesAutopilot->unsubscribeByField("mssys_firstname","John");
showStatus("unsubscribe (unsubscribeByField)", $result>0 && !$salesAutopilot->get_error());
	
$result = $salesAutopilot->deleteById($id);
showStatus("delete (deleteById)", $result>0 && !$salesAutopilot->get_error());

echo "<h2>List handling</h2>";
	
$result = $salesAutopilot->getLists();
showStatus("getLists", !$salesAutopilot->get_error());

$result = $salesAutopilot->listFields();
showStatus("listFields", !$salesAutopilot->get_error());

$result = $salesAutopilot->getForms();
showStatus("getForms", !$salesAutopilot->get_error());
	
$result = $salesAutopilot->fieldOptionAddWithValue("mssys_crm_status","A100","New");
showStatus("fieldOptionAddWithValue", $result===1 && !$salesAutopilot->get_error());

$result = $salesAutopilot->fieldOptionEditWithValue("mssys_crm_status","A100","A101","Updated");
showStatus("fieldOptionEditWithValue", $result===1 && !$salesAutopilot->get_error());

$result = $salesAutopilot->getFieldOptions("mssys_crm_status");
showStatus("getFieldOptions", !$salesAutopilot->get_error());
	

echo "<h2>eCommerce</h2>";
	
$salesAutopilot = new salesAutopilot(SA_ORDER_LIST_ID, SA_ORDER_WITH_1_PROD_FORM_ID, SA_USERNAME, SA_PASSWORD, false); // SSL:false

$result = $salesAutopilot->saveOrder(array(
	"email" => "john.doo@salesautopilot.com",
    "mssys_firstname" => "John",
	"mssys_lastname" => "Doo",
	"mssys_company" => "SalesAutopilot Kft.",  
	"shipping_method" => SA_SHIPPING_METHOD,
	"prod_id" => SA_PROD_1_ID));
showStatus("saveOrder (with 1 product form)", $result>0 && !$salesAutopilot->get_error());

$salesAutopilot = new salesAutopilot(SA_ORDER_LIST_ID, SA_ORDER_WITH_MORE_PROD_FORM_ID, SA_USERNAME, SA_PASSWORD, false); // SSL:false

$id = $result = $salesAutopilot->saveOrder(array(
	"email" => "john.doo@salesautopilot.com",
    "mssys_firstname" => "John",
	"mssys_lastname" => "Doo",
	"mssys_company" => "SalesAutopilot Kft.",  
	"shipping_method" => SA_SHIPPING_METHOD,
	"products" => array(
		array(
			"prod_id" => SA_PROD_1_ID,
			"qty" => 2),
		array(
			"prod_id" => SA_PROD_2_ID,
			"qty" => 1,
			"prod_price" => 20,
			"prod_name" => "Modified name of Product2"))));
showStatus("saveOrder (with more product form)", $result>0 && !$salesAutopilot->get_error());

$result = $salesAutopilot->orderAddProduct($id, array(
	"products" => array(array(
		"prod_id" => SA_PROD_2_ID,
		"qty" => 2,
		"prod_price" => 15,
		"prod_name" => "API added Product2"))));
showStatus("orderAddProduct", $result>0 && !$salesAutopilot->get_error());	
	
$result = $salesAutopilot->orderModProductByProdId($id, SA_PROD_1_ID, 5, 3);
showStatus("orderModProductByProdId", $result>0 && !$salesAutopilot->get_error());

$result = $salesAutopilot->orderModProductByProdSKU($id, SA_PROD_1_SKU, 4, 1);
showStatus("orderModProductByProdSKU", $result>0 && !$salesAutopilot->get_error());

$products = $result = $salesAutopilot->orderListProducts($id);
showStatus("orderListProducts", !$salesAutopilot->get_error());
	
//$salesAutopilot->orderDelProductByProdId($id, SA_PROD_2_ID);	

$product = $result = $salesAutopilot->orderListProduct($result[0]->oi_id);
showStatus("orderListProduct", !$salesAutopilot->get_error());

$result = $salesAutopilot->orderDelProductByProdId($id, SA_PROD_1_ID);
showStatus("orderDelProductByProdId", $result>0 && !$salesAutopilot->get_error());
	
define('SA_WEB_PROD_1_ID',595769);
define('SA_WEB_PROD_1_SKU',"WEB_P1");
define('SA_WEB_ORDER_ID',"WEB" . ($id+1));
	
$result = $salesAutopilot->processWebshopOrder(array(
	"email" => "john.doo@salesautopilot.com",
    "mssys_firstname" => "John",
	"mssys_lastname" => "Doo",
	"mssys_company" => "SalesAutopilot Kft.",  
	"shipping_method" => "Webshop shipping method",
	"payment_method" => "Webshop payment method",
	"currency" => "HUF",
	"netshippingcost" => "20",
	"grossshippingcost" => "25.2",
	"order_id" => SA_WEB_ORDER_ID,
	"products" => array(
		array(
			"prod_id" => SA_WEB_PROD_1_SKU,
			"prod_name" => "Webshop product 1",
			"category_id" => "WEB_CAT1",
			"category_name" => "Webshop Category 1",
			"prod_price" => 20,
			"tax" => 27,
			"qty" => 2))));
showStatus("processWebshopOrder", !$salesAutopilot->get_error());
	
$result = $salesAutopilot->webshopOrderModProductByProdId(SA_WEB_ORDER_ID, SA_WEB_PROD_1_ID, 5, 3);
showStatus("webshopOrderModProductByProdId", $result>0 && !$salesAutopilot->get_error());

$result = $salesAutopilot->webshopOrderModProductByProdSKU(SA_WEB_ORDER_ID, SA_WEB_PROD_1_SKU, 4, 2);
showStatus("webshopOrderModProductByProdSKU", $result>0 && !$salesAutopilot->get_error());

echo "<h2>Handling Product Categories</h2>";

$prodcat_id = $result = $salesAutopilot->createProdCategoryWithValue("Test ProductCategory");
showStatus("createProdCategoryWithValue", !$salesAutopilot->get_error());
	
$result = $salesAutopilot->modProdCategoryWithValue($prodcat_id, "Updated Test ProductCategory");
showStatus("modProdCategoryWithValue", !$salesAutopilot->get_error());

$result = $salesAutopilot->getProdCategory($prodcat_id);
showStatus("getProdCategory", !$salesAutopilot->get_error());
	
$result = $salesAutopilot->delProdCategory($prodcat_id);
showStatus("delProdCategory", $result===1 && !$salesAutopilot->get_error());

$result = $salesAutopilot->listProdCategories();
showStatus("listProdCategories", !$salesAutopilot->get_error());

echo "<h2>Handling Products</h2>";

$prod_id = $result = $salesAutopilot->createProductWithValue("TestProd", "100", "21.0", "EUR", "TP999", []);
showStatus("createProduct (createProductWithValue)", $result>0 && !$salesAutopilot->get_error());

$result = $salesAutopilot->modifyProductByIdWithValue($prod_id, "TestProd-mod", "200", "20.1", "EUR", "TP1999", []);
showStatus("modifyProduct (modifyProductByIdWithValue)", $result>0 && !$salesAutopilot->get_error());

$result = $salesAutopilot->getProduct($prod_id);
showStatus("getProduct", !$salesAutopilot->get_error());

$result = $salesAutopilot->listProducts();
showStatus("listProducts", !$salesAutopilot->get_error());

$result = $salesAutopilot->deleteProduct($prod_id);
showStatus("deleteProduct", !$salesAutopilot->get_error());
	
echo "<h2>Handling coupons</h2>";

$result = $salesAutopilot->couponCheck(array(
	"coupon_code" => "Coupon1",
	"products" => array(array(
		"prod_id" => SA_PROD_1_ID,
		"quantity" => 1,
		"price" => 1000))));
showStatus("couponCheck", !$salesAutopilot->get_error());

$result = $salesAutopilot->couponCheckWithProduct("Coupon1", $product);
showStatus("couponCheck (couponCheckWithProduct)", !$salesAutopilot->get_error());

$result = $salesAutopilot->couponCheckWithProducts("Coupon1", $products);
showStatus("couponCheck (couponCheckWithProducts)", !$salesAutopilot->get_error());
	
$result = $salesAutopilot->couponCheckWithValue("Coupon1", SA_PROD_1_ID, 1, 1000);
showStatus("couponCheck (couponCheckWithValue)", !$salesAutopilot->get_error());

echo "<h2>Handling mails</h2>";

$result = $salesAutopilot->sendMailWithValue(SA_SCHEDULE_ID, 6, "CUSTOM_TEXT", "CUSTOM_HTML");
showStatus("sendMailWithValue", !$salesAutopilot->get_error());

$letterids = $result = $salesAutopilot->sentEmailIds('2017-07-01','2017-07-31');
showStatus("sentEmailIds", !$salesAutopilot->get_error());

$result = $salesAutopilot->sendLogSummary($letterids[0]);
showStatus("sendLogSummary", !$salesAutopilot->get_error());

$result = $salesAutopilot->sendLogList($letterids[0]);
showStatus("sendLogList", !$salesAutopilot->get_error());

echo "<h2>Handling Global variables</h2>";
	
$name = "TestGlobalVar" . $id;
$result = $salesAutopilot->createGlobalVarWithValue($name, "html value", "text value");
showStatus("createGlobalVar (createGlobalVarWithValue)", $result!=NULL && !$salesAutopilot->get_error());

$result = $salesAutopilot->updateGlobalVarWithValue($name, "updated html value", "updated text value");
showStatus("updateGlobalVar (updateGlobalVarWithValue)", $result!=NULL && !$salesAutopilot->get_error());

$result = $salesAutopilot->getGlobalVar($name);
showStatus("getGlobalVar", $result!=NULL && !$salesAutopilot->get_error());

echo "<h2>CRM</h2>";

$events = $result = $salesAutopilot->listTaskByStatus(0);
showStatus("listTaskByStatus", !$salesAutopilot->get_error());

$result = $salesAutopilot->closeEvent($events[0]->event_id);
showStatus("closeEvent", !$salesAutopilot->get_error());

?>
</body>