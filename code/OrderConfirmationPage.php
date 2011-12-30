<?php

/**
 * @description:
 * The Order Confirmation page shows order history.
 * It also serves as the end point for the current order...
 * once submitted, the Order Confirmation page shows the
 * finalised detail of the order.
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: pages
 *
 **/

class OrderConfirmationPage extends CartPage{

	public static $icon = 'ecommerce/images/icons/OrderConfirmationPage';


	public static $db = array(
		'StartNewOrderLinkLabel' => 'Varchar(100)',
		'CopyOrderLinkLabel' => 'Varchar(100)'
	);


	public static $defaults = array(
		"ShowInMenus" => false,
		"ShowInSearch" => false,
		"StartNewOrderLinkLabel" => "start new order",
		"CopyOrderLinkLabel" => "copy order items into a new order"
	);

	function canCreate($member = null) {
		return !DataObject :: get_one("OrderConfirmationPage", "\"ClassName\" = 'OrderConfirmationPage'");
	}

	/**
	 *@return Fieldset
	 **/
	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fields->removeFieldFromTab('Root.Content.Actions',"ProceedToCheckoutLabel");
		$fields->removeFieldFromTab('Root.Content.Actions',"ContinueShoppingLabel");
		$fields->removeFieldFromTab('Root.Content.Actions',"ContinuePageID");
		$fields->removeFieldFromTab('Root.Content.Actions',"SaveOrderLinkLabel");
		$fields->removeFieldFromTab('Root.Content.Errors',"NoItemsInOrderMessage");
		$fields->addFieldToTab('Root.Content.Messages.Messages.Actions', new TextField('StartNewOrderLinkLabel', 'Label for starting new order - e.g. click here to start new order'));
		$fields->addFieldToTab('Root.Content.Messages.Messages.Actions', new TextField('CopyOrderLinkLabel', 'Label for copying order items into a new one  - e.g. click here start a new order with the current order items'));
		return $fields;
	}


	/**
	 * Returns the link or the Link to the OrderConfirmationPage page on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		if($page = DataObject::get_one('OrderConfirmationPage', "\"ClassName\" = 'OrderConfirmationPage'")) {
			return $page->Link();
		}
		elseif($page = DataObject::get_one('OrderConfirmationPage')) {
			return $page->Link();
		}
		return CartPage::find_link();
	}


	/**
	 * Return a link to view the order on this page.
	 * @return String (URLSegment)
	 * @param int|string $orderID ID of the order
	 */
	public static function get_order_link($orderID) {
		return self::find_link(). 'showorder/' . $orderID . '/';
	}

	/**
	 * Return a link to copy the order to cart
	 * @return String (URLSegment)
	 * @param int|string $orderID ID of the order
	 */
	public static function new_order_link($orderID) {
		return self::find_link(). 'copyorder/' . $orderID . '/';
	}

	/**
	 * Return a link to view the order on this page.
	 * @return String (URLSegment)
	 * @param int|string $orderID ID of the order
	 */
	public static function get_email_link($orderID) {
		return self::find_link(). 'sendreceipt/' . $orderID . '/';
	}

	/**
	 * Return a link to view the order on this page.
	 * @return String (URLSegment)
	 * @param int|string $orderID ID of the order
	 */
	public function getOrderLink($orderID) {
		return self::get_order_link($orderID);
	}

}

class OrderConfirmationPage_Controller extends CartPage_Controller{

	static $allowed_actions = array(
		'retrieveorder',
		'loadorder',
		'copyorder',
		'startneworder',
		'showorder',
		'sendreceipt',
		'CancelForm',
		'PaymentForm',
	);


	/**
	 * standard controller function
	 **/
	function init() {
		//we retrieve the order in the parent page
		//the parent page also takes care of the security
		parent::init();
		Requirements::themedCSS('Order');
		Requirements::themedCSS('Order_Print', "print");
		Requirements::javascript('ecommerce/javascript/EcomPayment.js');
	}

	/**
	 * This method exists just so that template
	 * sets CurrentOrder variable
	 *
	 *@return array
	 **/
	function showorder($request) {
		if(isset($_REQUEST["print"])) {
			Requirements::themedCSS("OrderReport"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			Requirements::themedCSS("OrderReport_Print", "print"); // LEAVE HERE - NOT EASY TO INCLUDE VIA TEMPLATE
			return $this->renderWith("Invoice");
		}
		return array();
	}


	/**
	 * Returns the form to cancel the current order,
	 * checking to see if they can cancel their order
	 * first of all.
	 *
	 * @return OrderForm_Cancel
	 */
	function CancelForm() {
		if($this->Order()) {
			if($this->currentOrder->canCancel()) {
				return new OrderForm_Cancel($this, 'CancelForm', $this->currentOrder);
			}
		}
		//once cancelled, you will be redirected to main page - hence we need this...
		if($this->orderID) {
			return array();
		}
	}


	/**
	 * show the payment form
	 *@return Form (OrderForm_Payment) or Null
	 **/
	function PaymentForm(){
		if($this->Order()){
			if($this->currentOrder->canPay()) {
				Requirements::javascript("ecommerce/javascript/EcomPayment.js");
				return new OrderForm_Payment($this, 'PaymentForm', $this->currentOrder);
			}
		}
	}


	/**
	 * This is an additional way to look at an order.
	 * The order is already retrieved from the
	 *@return Array
	 **/
	function retrieveorder(){
		return array();
	}


	/**
	 *@return Array - just so the template is still displayed
	 **/
	function sendreceipt($request) {
		if($o = $this->currentOrder) {
			if($m = $o->Member()) {
				if($m->Email) {
					$o->sendReceipt(_t("Account.COPYONLY", "--- COPY ONLY ---"), true);
					$this->message = _t('OrderConfirmationPage.RECEIPTSENT', 'An order receipt has been sent to: ').$m->Email.'.';
				}
				else {
					$this->message = _t('OrderConfirmationPage.RECEIPTNOTSENTNOTSENDING', 'Email could NOT be sent.');
				}
			}
			else {
				$this->message = _t('OrderConfirmationPage.RECEIPTNOTSENTNOEMAIL', 'No email could be found for sending this receipt.');
			}
		}
		else {
			$this->message = _t('OrderConfirmationPage.RECEIPTNOTSENTNOORDER', 'Order could not be found.');
		}
		Director::redirectBack();
		return array();
	}


}


