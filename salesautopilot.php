<?php

/**
 * A SalesAutopilot REST API teljes implementációja. - 2018.04.24
 *
 * Használatához szükséges
 *   - legalább PHP5.2 (javasolt: 7.0)
 *   - JSON extension
 *   - cURL extension
 * 
 * Példák a használatra a test.php fájlban.
 *
 * Torma Gábor - torma.gabor@hmail.hu - M-Magnet Studio Bt.
 */
class salesAutopilot
{
	public
		$fields = array();
	protected
		$status = 0,
		$url = '';
	protected
		$username,
		$password,
		$nl_id = 0,
		$ns_id = 0;
	private
		$api_url = '',
		$errors = array(
			0 => 'unhandled error occurred, contact our support',
			401 => 'authentication failed, wrong username or password',
			404 => 'unknown resource',
			405 => 'invalid method',
			406 => 'wrong parameters',
		);
	function __construct($nl_id, $ns_id, $username, $password, $ssl = true)
	{
		$this->username = $username;
		$this->password = $password;

		$this->nl_id = (int)$nl_id;
		$this->ns_id = (int)$ns_id;

		$this->api_url = ($ssl ? "https" : "http") . "://api.salesautopilot.com/";
	}
	function __get($property)
	{
		return @$this->$property;
	}
	function __isset($property)
	{
		return isset($this->$property);
	}
	function __call($name, $args)
	{
		if ($name === 'list') {
			return $this->get(@$args[0], @$args[1], @$args[2]);
		}

		trigger_error('Call to undefined method ' . __class__ . '::' . $name, E_USER_ERROR);
	}

	/**
	 * Kérés küldése.
	 * Kérés küldése a SalesAutopilot szerver felé.
	 * 
	 * @param string Kért erőforrás azonosító.
	 * @param array Küldendő adatok.
	 * @param string A kérés típusa, GET, POST, DELETE stb. NULL küldendő adat esetén mindig GET.
	 * @return mixed A válasz json dekódolt része.
	 * @access protected
	 */
	public function send_request($url, $data = null, $method = 'POST')
	{
		$ch = curl_init($this->url = $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		if (isset($data)) {
			$request = json_encode($this->get_params($data));
//			var_dump($request);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}
		if ($method === 'DELETE')
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

		file_put_contents('sa.log', json_encode($url) . "\n", FILE_APPEND);
		file_put_contents('sa.log', json_encode($request) . "\n", FILE_APPEND);
		$response = curl_exec($ch);
		file_put_contents('sa.log', json_encode($response) . "\n", FILE_APPEND);
//	var_dump($response);
		$this->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($this->status != 200) {
			$this->set_error();
			return null;
		}

		$decoded_response = json_decode($response);
	//	var_dump($decoded_response);
		return $decoded_response === null && $response !== "" ? $response : $decoded_response;
	}

	/**
	 * Hibaüzenet beállítása.
	 * 
	 * @return void
	 * @access private
	 */
	private function set_error()
	{
		//Hívó hely kiderítése
		$debug = debug_backtrace();
		$last = reset($debug);

		foreach ($debug as $caller) {
			if ($last['file'] !== $caller['file']) {
				break;
			}
		}
		
		//Hibaüzenet
		$error = isset($this->errors[$this->status]) ? $this->errors[$this->status] : $this->errors[0];
		trigger_error('SalesAutopilot (' . $this->status . '): ' . $error . ' [' . $this->url . '], hivas helye: [' . $caller['file'] . ' ' . $caller['line'] . ' sor]', E_USER_WARNING);
	}

	/**
	 * Átadott rekordok fordítása mm címlista mezőnevekre.
	 * 
	 * @param array Lekérdezés eredményeként átadott név-érték párok.
	 * @return array SalesAutopilot címlista név-érték párok.
	 * @access private
	 */
	private function get_params($fields)
	{
		if (!$this->fields) {
			return $fields;
		}

		$params = array();
		foreach ($fields as $field_name => $value) {
			$form_name = @$this->fields[$field_name];
			if (isset($form_name) && !$form_name) {
				continue;
			}
			if (!isset($form_name)) {
				$form_name = $field_name;
			}

			$params[$form_name] = $value;
		}
		return $params;
	}

	public function get_error()
	{
		return $this->status == 200 ? false : $this->status;
	}

	protected function typeCastFields($fields)
	{
		$listFields = $this->listFieldsExtended();
		foreach ($fields as $name => &$field) {
			if (array_key_exists($name, $listFields)) {
				$type = $listFields->$name;
				if ($type == "smallint" || $type == "integer")
					$field = intval($field);
				if ($type == "decimal")
					$field = floatval($field);
				if ($type == "checkbox")
					$field = filter_var($field, FILTER_VALIDATE_BOOLEAN) ? "1" : "0";
				if ($type == 'date' || $type == 'datetime') {
					if (gettype($field) != "integer")
						$field = strtotime($field);
					if ($type == 'date')
						$field = date('Y-m-d', $field);
					if ($type == 'datetime')
						$field = date('Y-m-d H:i:s', $field);
				}
				if (empty($field)) {
					switch (strtolower($type)) {
						case "shorttext":
						case "text":
						case "radio":
						case "select":
						case "url":
						case "file":
						case "password":
							$field = "";
							break;
						case "smallint":
						case "integer":
						case "decimal":
						case "checkbox":
							$field = "0";
							break;
						case "date":
							$field = "0000-00-00";
							break;
						case "datetime":
							$field = "0000-00-00 00:00:00";
							break;
					}
				}
			}
		}
		return $fields;
	}

	/**************************/
	/***Handling subscribers***/
	/**************************/

// ADDING NEW SUBSCRIBERS

	public function subscribe($fields, $typeCast = false)
	{ // OK
		$url = $this->api_url . "subscribe/{$this->nl_id}/form/{$this->ns_id}";
		return $this->send_request($url, $typeCast ? $this->typeCastFields($fields) : $fields);
	}

// UPDATING A SUBSCRIBER
	// $id = ID of subscriber whose data should be updated
	public function update($id, $fields = null, $typeCast = false, $applyCoupon = false)
	{
		if ($applyCoupon && array_key_exists("mssys_coupon", $fields)) {
			$order = $this->listById($id);
			if (isset($order->products)) {
				$fields["mssys_order_netto_sum"] = 0;
				$fields["mssys_order_brutto_sum"] = 0;
				$couponCheck = array("coupon_code" => $fields["mssys_coupon"], "products" => array());
				foreach ($order->products as $product) {
					$fields["mssys_order_netto_sum"] += $product->oi_netto_sum;
					$fields["mssys_order_brutto_sum"] += $product->oi_brutto_sum;
					array_push($couponCheck["products"], array(
						"prod_id" => $product->prod_id,
						"quantity" => $product->oi_quantity,
						"price" => $product->prod_price
					));
				}
				$coupon = $this->couponCheck($couponCheck);
				if (is_object($coupon)) {
					$fields["mssys_coupon_id"] = $coupon->id;
					$fields["mssys_coupon_type"] = $coupon->type;
					$fields["mssys_coupon_discount_amount_netto"] = $coupon->netto_discount;
					$fields["mssys_coupon_discount_amount_brutto"] = $coupon->brutto_discount;
					$fields["mssys_order_netto_sum"] -= $coupon->netto_discount;
					$fields["mssys_order_brutto_sum"] -= $coupon->brutto_discount;
				} else {
					$fields["mssys_coupon_id"] = 0;
					$fields["mssys_coupon_type"] = 0;
					$fields["mssys_coupon_discount_amount_netto"] = 0;
					$fields["mssys_coupon_discount_amount_brutto"] = 0;
				}
			}
		}
		$url = $this->api_url . "update/{$this->nl_id}/form/{$this->ns_id}/record/" . (int)$id;
		return $this->send_request($url, $typeCast ? $this->typeCastFields($fields) : $fields, 'PUT');
	}
	public function updateById($id, $fields = null, $typeCast = false, $applyCoupon = false)
	{
		return $this->update($id, $fields, $typeCast, $applyCoupon);
	}

	public function batchUpdate($fields)
	{
		$url = $this->api_url . "batchupdate/{$this->nl_id}";
		return $this->send_request($url, $fields, 'POST');
	}
// $field_name = Name of the field that is used to identify the updated subscribers.
	// $field_value = Value of the field that is used to identify the updated subscribers.
	public function updateByField($field_name, $field_value, $fields = null, $typeCast = false)
	{
		$url = $this->api_url . "update/{$this->nl_id}/form/{$this->ns_id}/field/$field_name/value/$field_value";
		return $this->send_request($url, $typeCast ? $this->typeCastFields($fields) : $fields, 'PUT');
	}

// GETTING SUBSCRIBER'S DETAILS

	public function listAll()
	{
		$url = $this->api_url . "list/{$this->nl_id}";
		return $this->send_request($url);
	}

	public function listTotalCount()
	{
		$url = $this->api_url . "listtotalcount/{$this->nl_id}";
		return $this->send_request($url);
	}	

	// $id = The ID of the subscriber whose details should be retrieved.
	public function listById($id)
	{
		$url = $this->api_url . "list/{$this->nl_id}" . ($id != null ? "/record/$id" : "");
		return $this->send_request($url);
	}

	// $field_name = Name of the field that is used to identify the retrieved subscribers.
	// $field_value = Value of the field that is used to identify the retrieved subscribers.
	public function listByField($field_name, $field_value)
	{
		$url = $this->api_url . "list/{$this->nl_id}/field/$field_name/value/$field_value";
		return $this->send_request($url);
	}
	
	// $email = ???????????????????????????????????????????????????????????????????????
	public function listByEmail($email)
	{
		return $this->listByField("email", $email);
	}
	
// Szegmens(ek) feliratkozóinak lekérdezése

	public function filteredList($fields)
	{
		$url = $this->api_url . "filteredlist/{$this->nl_id}";
		return $this->send_request($url, $fields);
	}

	// $nls_id = The ID of the segment.
	public function filteredListWithValue($nls_id)
	{
		return $this->filteredList(array($nls_id));
	}
	
	// $orderfield = Milyen mező alapján legyen sorbarendezve az eredmény. A mező nevét kell megadni.
	// $erderdir = A sorrend iránya. asc: növekvő sorrend, desc: csökkenő sorrend.
	// $limit = Kilistázandó rekordok száma.
	// Ha pl. csak az időrendben utolsó feliratkozót szeretné listázni, akkor az orderfield értéke legyen subdate, az orderdir értéke desc, a limit értéke pedig 1.
	public function filteredListOrder($orderfield, $order_dir, $limit, $fields)
	{
		$url = $this->api_url . "filteredlist/{$this->nl_id}/order/$orderfield/$order_dir/$limit";
		return $this->send_request($url, $fields);
	}

	// $orderfield = Milyen mező alapján legyen sorbarendezve az eredmény. A mező nevét kell megadni.
	// $erderdir = A sorrend iránya. asc: növekvő sorrend, desc: csökkenő sorrend.
	// $limit = Kilistázandó rekordok száma.
	//   Ha pl. csak az időrendben utolsó feliratkozót szeretné listázni, akkor az orderfield értéke legyen subdate, az orderdir értéke desc, a limit értéke pedig 1.
	// $nls_id = The ID of the segment.
	public function filteredListOrderWithValue($orderfield, $order_dir, $limit, $nls_id)
	{
		return $this->filteredListOrder($orderfield, $order_dir, $limit, array($nls_id));
	}
	
// Szegmensben lévő feliratkozók száma
	
	// $nls_id = The ID of the segment.
	public function getSegmentNum($nls_id)
	{
		$url = $this->api_url . "getsegmentnum/$nls_id";
		return $this->send_request($url);
	}
	
// Adatmódosító űrlap linkje

	// $ns_id = Az űrlap azonosítója, amely linkje megjelenítésre kerül.
	// $id = Feliratkozó azonosítója az email listában, akinek az adatait az űrlap behelyettesíti és módosítja.
	public function updateFormLink($ns_id, $id)
	{
		$url = $this->api_url . "updateformlink/{$this->nl_id}/$ns_id/$id";
		return $this->send_request($url);
	}
	
// Langing oldal linkje

	// $lp_id = A landing oldal azonosítója.
	// $id = Feliratkozó azonosítója az email listában, akinek az adatait a landing oldal behelyettesíti.
	public function landingPageLink($lp_id, $id)
	{
		$url = $this->api_url . "landingpagelink/{$this->nl_id}/$lp_id/$id";
		return $this->send_request($url);
	}
	
// UNSUBSCRIBING A SUBSCRIBER	

	// $id = The ID of the subscriber who should be unsubscribed.
	public function unsubscribe($id)
	{
		$url = $this->api_url . "unsubscribe/{$this->nl_id}/record/$id";
		return $this->send_request($url);
	}
	public function unsubscribeById($id)
	{
		return $this->unsubscribe($id);
	}
	
	// $email = The email address of the subscriber who should be unsubscribed.
	public function unsubscribeByEmail($email)
	{
		$url = $this->api_url . "unsubscribe/{$this->nl_id}/email/$email";
		return $this->send_request($url);
	}

	// $field_name = Name of the field that is used to identify the unsubscribed subscribers.
	// $field_value = Value of the field that is used to identify the unsubscribed subscribers.
	public function unsubscribeByField($field_name, $field_value)
	{ // not in the documentation, but it works! Tested!
		$url = $this->api_url . "unsubscribe/{$this->nl_id}/field/$field_name/value/$field_value";
		return $this->send_request($url);
	}
	
// DELETING A SUBSCRIBER	

	// $id = The ID of the subscriber who should be deleted.
	public function delete($id)
	{
		$url = $this->api_url . "delete/{$this->nl_id}/record/$id";
		return $this->send_request($url, null, 'DELETE');
	}

	public function deleteById($id)
	{
		return $this->delete($id);
	}

	/*******************/
	/***List handling***/
	/*******************/	
	
// Email listák lekérdezése

	public function getLists()
	{
		$url = $this->api_url . "getlists";
		return $this->send_request($url);
	}

// Email listához tartozó űrlapok lekérdezése (az adatmódosító űrlapok nélkül!)

	public function getForms()
	{
		$url = $this->api_url . "getforms/{$this->nl_id}";
		return $this->send_request($url);
	}
	
// Visszaadja az adott lista mezőinek neveit.

	public function listFields()
	{
		$url = $this->api_url . "listfields/{$this->nl_id}";
		return $this->send_request($url);
	}

// Visszaadja az adott lista mezőinek neveit és típusait.

	public function listFieldsExtended()
	{
		$url = $this->api_url . "listfields/{$this->nl_id}/extended";
		return $this->send_request($url);
	}
	
// Visszaadja, hogy létezik-e az adott listán mező ezzel a mezőnévvel.

	// $fieldname = Mező neve, amelynek a létezését le szeretné kérdezni.
	public function checkFieldExists($fieldname)
	{
		$url = $this->api_url . "checkfieldexists/{$this->nl_id}/$fieldname";
		return $this->send_request($url);
	}

// Mező hozzáadása listához	

	public function addListField($fileds)
	{
		$url = $this->api_url . "addlistfield/{$this->nl_id}";
		return $this->send_request($url, $fileds);
	}

	// $field_name = Mező neve, amelyet létre szeretnénk hozni.
	// $field_label = Mező neve, amelyet létre szeretnénk hozni.
	// $field_comment = Mező neve, amelyet létre szeretnénk hozni.
	// $field_type = Mező neve, amelyet létre szeretnénk hozni. Bövebb infó: https://www.salesautopilot.hu/tudasbazis/api/elemek-kezelese#addlistfield
	// $param = Ez a scale vagy az optinons változók értéke. Csak akkor kell megadni, ha a field_type in (decimal, radio, select)
	public function addListFieldWithValue($field_name, $field_label, $field_comment, $field_type, $param = null)
	{
		$fields = array(
			"field_name" => $field_name,
			"field_label" => $field_label,
			"field_comment" => $field_comment,
			"field_type" => $field_type
		);
		if (!empty($param)) {
			if ($field_type == 'decimal')
				$fields["scale"] = $param;
			if ($field_type == 'radio' || $field_type == 'select')
				$fields["options"] = $param;
		}
		return $this->addListField($fields);
	}

// Legördülő vagy rádiógomb típusú mező opciónak lekérdezése	
	
	// $fieldname = Mező neve, amelynek az opcióit le szeretné kérdezni.
	public function getFieldOptions($fieldname)
	{
		$url = $this->api_url . "getfieldoptions/{$this->nl_id}/$fieldname";
		return $this->send_request($url);
	}

// Mező opció hozzáadása
	
	// $fieldname = Mező neve, amelynek az opcióit hozzá szeretnénk adni.
	public function fieldOptionAdd($fieldname, $fileds)
	{
		$url = $this->api_url . "fieldoptionadd/{$this->nl_id}/$fieldname";
		return $this->send_request($url, $fileds);
	}

	// $fieldname = Mező neve, amelynek az opcióit hozzá szeretnénk adni.
	// $value = Az új opció értéke.
	// $text = Az új opció címkéje.
	public function fieldOptionAddWithValue($fieldname, $value, $text)
	{
		return $this->fieldOptionAdd($fieldname, array(
			"value" => $value,
			"text" => $text
		));
	}
	
// Mező opciójának módosítása

	// $fieldname = Mező neve, amelynek az opcióit hozzá szeretnénk adni.
	// $optionvalue = A módosítandó opció értéke.
	public function fieldOptionEdit($fieldname, $optionvalue, $fileds)
	{
		$url = $this->api_url . "fieldoptionedit/{$this->nl_id}/$fieldname/$optionvalue";
		return $this->send_request($url, $fileds);
	}

	// $fieldname = Mező neve, amelynek az opcióit hozzá szeretnénk adni.
	// $optionvalue = A módosítandó opció értéke.
	// $value = Az módosítandó opció új értéke.
	// $text = Az módosítandó opció új címkéje.
	public function fieldOptionEditWithValue($fieldname, $optionvalue, $value, $text)
	{
		return $this->fieldOptionEdit($fieldname, $optionvalue, array(
			"value" => $value,
			"text" => $text
		));
	}
	
// Mező opciójának törlése - fieldoptiondelete/<listid>/<fieldname>/<optionvalue>

	/************************/
	/***eCommerce handling***/
	/************************/

// ADDING A NEW ORDER

	public function saveOrder($fields, $typeCast = false)
	{
		$url = $this->api_url . "saveOrder/{$this->nl_id}/form/{$this->ns_id}";
		return $this->send_request($url, $typeCast ? $this->typeCastFields($fields) : $fields);
	}

// RECEIVING WEBSHOP ORDER

	public function processWebshopOrder($fields, $typeCast = false)
	{
		$url = $this->api_url . "processWebshopOrder/{$this->nl_id}/ns_id/{$this->ns_id}";
		return $this->send_request($url, $typeCast ? $this->typeCastFields($fields) : $fields);
	}

	/*********************************/
	/***Meglévő rendelések kezelése***/
	/*********************************/

// Termék hozzáadása meglévő megrendeléshez

	public function orderAddProduct($id, $fields)
	{
		$url = $this->api_url . "orderaddproduct/{$this->nl_id}/{$id}";
		return $this->send_request($url, $fields);
	}

// Termék módosítása meglévő megrendelésben

	public function orderModProduct($id, $fields)
	{
		$url = $this->api_url . "ordermodproduct/{$this->nl_id}/{$id}";
		return $this->send_request($url, $fields);
	}

	public function orderModProductByProdId($id, $prod_id, $prod_price, $qty)
	{
		return $this->orderModProduct($id, array(
			"products" => array(array(
				"prod_id" => $prod_id,
				"qty" => $qty,
				"prod_price" => $prod_price
			))
		));
	}

	public function orderModProductByProdSKU($id, $prod_sku, $prod_price, $qty)
	{
		return $this->orderModProduct($id, array(
			"products" => array(array(
				"prod_sku" => $prod_sku,
				"qty" => $qty,
				"prod_price" => $prod_price
			))
		));
	}

	public function webshopOrderModProduct($webshop_order_id, $fields)
	{
		$url = $this->api_url . "ordermodproduct/{$this->nl_id}/orderid/$webshop_order_id";
		return $this->send_request($url, $fields);
	}

	public function webshopOrderModProductByProdId($webshop_order_id, $prod_id, $prod_price, $qty)
	{
		return $this->webshopOrderModProduct($webshop_order_id, array(
			"products" => array(array(
				"prod_id" => $prod_id,
				"qty" => $qty,
				"prod_price" => $prod_price
			))
		));
	}

	public function webshopOrderModProductByProdSKU($webshop_order_id, $prod_sku, $prod_price, $qty)
	{
		return $this->webshopOrderModProduct($webshop_order_id, array(
			"products" => array(array(
				"prod_sku" => $prod_sku,
				"qty" => $qty,
				"prod_price" => $prod_price
			))
		));
	}

// Termék törlése meglévő megrendelésből

	public function orderDelProduct($id, $fields)
	{
		$url = $this->api_url . "orderdelproduct/{$this->nl_id}/{$id}";
		return $this->send_request($url, $fields);
	}

	public function orderDelProductByProdId($id, $prod_id)
	{
		return $this->orderDelProduct($id, array($prod_id));
	}

// Rendelt termékek lekérdezése meglévő megrendelésből

	// $id = The ID of the subscriber.
	public function orderListProducts($id)
	{
		$url = $this->api_url . "list/{$this->nl_id}" . ($id != null ? "/record/$id" : "");
		$result = $this->send_request($url);
		return isset($result->products) ? $result->products : null;
	}

// Termék lekérdezése meglévő megrendelésből

	// $id = The ID of the ordered item.
	public function orderListProduct($oi_id)
	{
		$url = $this->api_url . "orderlistproducts/{$this->nl_id}/$oi_id";
		return $this->send_request($url);
	}

	/*******************************/
	/***Product Category Handling***/
	/*******************************/
	
// LIST ALL PRODUCT CATEGORIES

	public function listProdCategories()
	{
		$url = $this->api_url . "listprodcategories";
		return $this->send_request($url);
	}

// GET A SPECIFIC PRODUCT CATEGORY DETAILS

	// $prodcat_id = ID of the product category.
	public function getProdCategory($prodcat_id)
	{
		$url = $this->api_url . "listprodcategories/$prodcat_id";
		return $this->send_request($url);
	}
	
// CREATE A PRODUCT CATEGORY

	public function createProdCategory($fields)
	{
		$url = $this->api_url . "createprodcategory";
		return $this->send_request($url, $fields);
	}

	// $prodcat_name = Name of the global variable.
	public function createProdCategoryWithValue($prodcat_name)
	{
		return $this->createProdCategory(array("prodcat_name" => $prodcat_name));
	}

// MODIFY A PRODUCT CATEGORY

	// $prodcat_id = The ID of the product category that should be modified.
	public function modProdCategory($prodcat_id, $fields)
	{
		$url = $this->api_url . "modprodcategory/$prodcat_id";
		return $this->send_request($url, $fields);
	}

	// $prod_id = The ID of the product category that should be modified.
	// $prodcat_name = Name of the global variable.
	public function modProdCategoryWithValue($prodcat_id, $prodcat_name)
	{
		return $this->modProdCategory($prodcat_id, array("prodcat_name" => $prodcat_name));
	}

// DELETE A PRODUCT CATEGORY

	// $prodcat_id = ID of the product category that you want to delete.
	public function delProdCategory($prodcat_id)
	{
		$url = $this->api_url . "delprodcategory/$prodcat_id";
		return $this->send_request($url);
	}

	/**********************/
	/***Product Handling***/
	/**********************/
	
// ADD PRODUCT TO THE PRODUCT LIST	

	public function createProduct($fields)
	{
		$url = $this->api_url . "createproduct";
		return $this->send_request($url, $fields);
	}

	// $prod_name = Name of the added product.
	// $prod_price = Price of the added product in the defined currency.
	// $prod_vat_percent = The value added tax in percent.
	// $prod_currency = Currency of the added product. Allowed currencies are: EUR, USD, HUF
	// $prod_sku = Stock keeping unit number of the added product.
	// $prodcat_ids = Product category ids as a subarray.
	public function createProductWithValue($prod_name, $prod_price, $prod_vat_percent, $prod_currency, $prod_sku, $prodcat_ids)
	{
		return $this->createProduct(array(
			"prod_name" => $prod_name,
			"prod_price" => $prod_price,
			"prod_vat_percent" => $prod_vat_percent,
			"prod_currency" => $prod_currency,
			"prod_sku" => $prod_sku,
			"prodcat_ids" => $prodcat_ids
		));
	}
	
// MODIFY A PRODUCT IN THE PRODUCT LIST	

	// $prod_id = The ID of the product that should be modified.
	public function modifyProduct($prod_id, $fields)
	{
		$url = $this->api_url . "modifyproduct/" . (int)$prod_id;
		return $this->send_request($url, $fields);
	}
	public function modifyProductByProdId($prod_id, $fields)
	{
		return $this->modifyProduct($prod_id, $fields);
	}	
	
	// $prod_id = ID of the modified product.
	// $prod_name = Name of the modified product.
	// $prod_price = Price of the modified product in the defined currency.
	// $prod_vat_percent = The value modified tax in percent.
	// $prod_currency = Currency of the modified product. Allowed currencies are: EUR, USD, HUF
	// $prod_sku = Stock keeping unit number of the modified product.
	// $prodcat_ids = Product category ids as a subarray.
	public function modifyProductByIdWithValue($prod_id, $prod_name, $prod_price, $prod_vat_percent, $prod_currency, $prod_sku, $prodcat_ids)
	{
		return $this->modifyProduct($prod_id, array(
			"prod_name" => $prod_name,
			"prod_price" => $prod_price,
			"prod_vat_percent" => $prod_vat_percent,
			"prod_currency" => $prod_currency,
			"prod_sku" => $prod_sku,
			"prodcat_ids" => $prodcat_ids
		));
	}
	

	// $prod_sku = The SKU of the product that should be modified.
	public function modifyProductBySKU($prod_sku, $fields)
	{
		$url = $this->api_url . "modifyproduct/sku/" . (int)$prod_sku;
		return $this->send_request($url, $fields);
	}	
	
// DELETE PRODUCT FROM THE PRODUCT LIST	
	
	// $prod_id = ID of the product that you want to delete.
	public function deleteProduct($prod_id)
	{
		$url = $this->api_url . "deleteproduct/" . (int)$prod_id;
		return $this->send_request($url, null, 'DELETE');
	}

// GET A SPECIFIC PRODUCT DETAILS
	
	// $prod_id = ID of the product.
	public function getProduct($prod_id)
	{
		$url = $this->api_url . "getproduct/" . (int)$prod_id;
		return $this->send_request($url);
	}

// LIST ALL PRODUCTS

	public function listProducts()
	{
		$url = $this->api_url . "listproducts";
		return $this->send_request($url);
	}

	/*********************/
	/***Coupon hangling***/
	/*********************/

// Kupon érvényességének lekérdezése

	public function couponCheck($fields = null)
	{
		$url = $this->api_url . "couponcheck/{$this->nl_id}";
		return $this->send_request($url, $fields);
	}
	
	// coupon_code = Kupon kód.
	// $prod_id = Termék egyedi azonosítója.
    // $quantity = Az adott termékből rendelt darabszám.
	// $price = Termék ára. Csak akkor kell átadni, ha különbözik a terméktörzsben tárolt ártól.
	public function couponCheckWithValue($coupon_code, $prod_id, $quantity, $price)
	{
		return $this->couponCheck(array(
			"coupon_code" => $coupon_code,
			"products" => array(array(
				"prod_id" => $prod_id,
				"quantity" => $quantity,
				"price" => $price
			))
		));
	}

	// coupon_code = Kupon kód.
	// $product = Rendelt termék.
	public function couponCheckWithProduct($coupon_code, $product)
	{
		if (isset($product->oi_quantity))
			$product->quantity = $product->oi_quantity;
		return $this->couponCheck(array(
			"coupon_code" => $coupon_code,
			"products" => array($product)
		));
	}
	
	// coupon_code = Kupon kód.
	// $products = Rendelt termékek altömbben átadva.
	public function couponCheckWithProducts($coupon_code, $products)
	{
		foreach ($products as $product)
			if (isset($product->oi_quantity))
			$product->quantity = $product->oi_quantity;
		return $this->couponCheck(array(
			"coupon_code" => $coupon_code,
			"products" => $products
		));
	}

	/**********************/
	/***Levelek kezelése***/
	/**********************/
	
// Levélküldés

	public function sendMail($fields)
	{
		$url = $this->api_url . "sendmail";
		return $this->send_request($url, $fields);
	}

	// $send_id = Az időzítés azonosítója. Az időzítés neve után zárójelben a # után szerepel 5 vagy 6 jegyű szám.
	// $contactid = A feliratkozó azonosítója. Az email listában ez az id mező.
	// $mssys_text_content_part = A levél szöveges részébe behelyettesítendő tartalom. A szöveges tartalomba a [mssys_text_content_part] mezőkódot kell elhelyezni.
	// $mssys_html_content_part = A levél HTML részébe behelyettesítendő tartalom. A HTML tartalomba a [mssys_html_content_part] mezőkódot kell elhelyezni.
	public function sendMailWithValue($send_id, $id, $mssys_text_content_part, $mssys_html_content_part)
	{
		return $this->sendMail(array(array(
			"send_id" => $send_id,
			"contactid" => $id,
			"mssys_text_content_part" => $mssys_text_content_part,
			"mssys_html_content_part" => $mssys_html_content_part
		)));
	}
		
// Adott időszakban elküldött levelek lekérdezése - sentemailids/<start_date>/<end_date>

	// $start_date = Lekérdezendő időszak kezdete YYYYMMDD formátumban.
	// $end_date = Lekérdezendő időszak vége YYYYMMDD formátumban.
	public function sentEmailIds($start_date, $end_date)
	{
		$url = $this->api_url . "sentemailids/$start_date/$end_date";
		return $this->send_request($url);
	}
	
// Adott levél összesített eredményének lekérdezése - sendlogsummary/<letterid>

	// $letterid = A levél azonosítója, amely eredményeit le szeretné kérdezni. A levél azonosítóját pl. a sentemailids metódussal tudja kinyerni.
	public function sendLogSummary($letterid)
	{
		$url = $this->api_url . "sendlogsummary/$letterid";
		return $this->send_request($url);
	}
	
// Adott levélhez kapcsolódó eseményeinek tételes lekérdezése - sendloglist/<letterid>

	// $letterid = A levél azonosítója, amelyhez kapcsolódó eseményeket le szeretné kérdezni. A levél azonosítóját pl. a sentemailids metódussal tudja kinyerni.
	public function sendLogList($letterid)
	{
		$url = $this->api_url . "sendloglist/$letterid";
		return $this->send_request($url);
	}

	/*******************************/
	/***Handling global variables***/
	/*******************************/

// CREATING A GLOBAL VARIABLE

	public function createGlobalVar($fields)
	{
		$url = $this->api_url . "createglobalvar";
		return $this->send_request($url, $fields);
	}

	// $name = Globális változó neve. Csak az angol abc betűit, számokat és alulvonás ( _ ) karaktert tartalmazhat.
	// $html = Globális változó értéke, amely a HTML tartalmakba kerül behelyettesítésre (HTML email, köszönőoldal, landing page).
	// $text = Globálsi változó értéke, amely a szöveges email-ekbe kerül behelyettesítésre. A szöveges érték megadása opcionális. Ha nincs megadva, mindig a HTML érték kerül behelyettesítésre.
	public function createGlobalVarWithValue($name, $html, $text)
	{
		return $this->createglobalvar(array(
			"name" => $name,
			"html" => $html,
			"text" => $text
		));
	}

// MODIFY A GLOBAL VARIABLE

	public function updateGlobalVar($fields)
	{
		if (strripos($fields["name"], "global_") === false)
			$fields["name"] = "global_" . $fields["name"];
		$url = $this->api_url . "updateglobalvar";
		return $this->send_request($url, $fields);
	}

	// $name = Globális változó neve.
	// $html = Globális változó új értéke, amely a HTML tartalmakba kerül behelyettesítésre (HTML email, köszönőoldal, landing page).
	// $text = Globálsi változó új értéke, amely a szöveges email-ekbe kerül behelyettesítésre. A szöveges érték megadása opcionális. Ha nincs megadva, mindig a HTML érték kerül behelyettesítésre.
	public function updateGlobalVarWithValue($name, $html, $text)
	{
		return $this->updateglobalvar(array(
			"name" => $name,
			"html" => $html,
			"text" => $text
		));
	}
	
// GETTING GLOBAL VARIABLE VALUES
	
	// $name = Name of the global variable.
	public function getGlobalVar($name)
	{
		if (strripos($name, "global_") === false)
			$name = "global_" . $name;
		$url = $this->api_url . "getglobalvar/name/" . urlencode($name);
		return $this->send_request($url);
	}

// DELETING A GLOBAL VARIABLE
	
	// not in the documentation.

	/*********/
	/***CRM***/
	/*********/

// CRM feladatok lekérdezése

	public function listTask($fields = null)
	{
		$url = $this->api_url . "listtask/{$this->nl_id}";
		return $this->send_request($url, $fields);
	}

	// $status = A listázandó feladatok státusz értékét, ahol az 1-es érték a lezárt, a 0 érték pedig a nyitott feladatokat jelenti.
	public function listTaskByStatus($status)
	{
		return $this->listTask(array("status" => $status));
	}
	
// Feladat lezárása
	
	// $event_id = A CRM feladat egyedi azonosítója.
	public function closeEvent($event_id)
	{
		$url = $this->api_url . "closeevent/$event_id";
		return $this->send_request($url);
	}


	/*************************/
	/***Virtual Call Center***/
	/*************************/
	
// Kommunikáció a Virtual Call Center-el (VCC)

	public function vccUpdateRecord($id, $mmsyncid, $projectid, $recordid, $fields = null)
	{
		$url = $this->api_url . "vccupdaterecord/$mmsyncid/project/$projectid/record/$recordid";
		return $this->send_request($url, $fields, 'PUT');
	}
}

?>