<?php
/**
 * Update an Order
 * This processor can change all parameters of the existing order, including status, payment, delivery and so on.
 * 
 * @package minishop
 * @subpackage processors
 */

// Checking for permission
if (!$modx->hasPermission('save')) {return $modx->error->failure($modx->lexicon('ms.no_permission'));}

// Getting variables
$id = $modx->getOption('id', $_REQUEST, 0);
$status = $modx->getOption('status', $_REQUEST, 1);
$comment = $modx->getOption('comment', $_REQUEST, '');
$warehouse = $modx->getOption('wid', $_REQUEST, 0);
$delivery = $modx->getOption('delivery', $_REQUEST, 0);
$payment = $modx->getOption('payment', $_REQUEST, 0);
if (empty($id)) {return $modx->error->failure($modx->lexicon('ms.orders.item_err_save'));}

// Processing variables for assress of customer
$addr = array();
foreach ($_REQUEST as $k => $v) {
	if (strstr($k, 'addr_') != false) {
		$k = substr($k, 5);
		$addr[$k] = $v;
	}
}
// Loading miniShop class for its methods
$miniShop = new miniShop($modx);

if ($res = $modx->getObject('ModOrders', $id)) {
	$oldstatus = $res->get('status');
	$olddelivery = $res->get('delivery');
	$oldpayment = $res->get('payment');
	$oldwarehouse = $res->get('wid');
	
	// Changing warehouse
	if ($delivery > 0 && $warehouse != $oldwarehouse) {
		if ($tmp = $modx->getObject('ModWarehouse', $warehouse)) {
			$deliveries = $tmp->getDeliveries();
			if ($delivery > 0 && !in_array($delivery, $deliveries)) {
				return $modx->error->failure($modx->lexicon('ms.delivery.err_save'));
			}
			
		}
		else {
			return $modx->error->failure($modx->lexicon('ms.warehouse.err_nf'));
		}
	}
	if ($warehouse != $oldwarehouse) {
		$change_warehouse = 1;
		$res->set('wid', $warehouse);
	}
	// Changing delivery method and check of payment method for this delivery
	if ($delivery > 0) {
		if ($tmp = $modx->getObject('ModDelivery', $delivery)) {
			$payments = $tmp->getPayments();
			if (!in_array($payment, $payments) && $payment != 0) {
				return $modx->error->failure($modx->lexicon('ms.payment.err_save'));
			}
		}
		else {
			return $modx->error->failure($modx->lexicon('ms.delivery.err_nf'));
		}
	}
	if ($delivery != $olddelivery) {
		$change_delivery = 1;
		$res->set('delivery', $delivery);
	}
	// Changing payment method
	if ($payment != $oldpayment) {
		$change_payment = 1;
		$res->set('payment', $payment);
	}
	// Saving changes
	$res->set('comment', $comment);
	if  ($res->save()) {
		if ($change_warehouse) {$miniShop->Log('warehouse', $id, 'change', $oldwarehouse, $warehouse);}
		if ($change_delivery) {$miniShop->Log('delivery', $id, 'change', $oldelivery, $delivery);}
		if ($change_payment) {$miniShop->Log('payment', $id, 'change', $oldpayment, $payment);}
	}
	
	if ($address = $modx->getObject('ModAddress', $addr['id'])) {
		$address->fromArray($addr);
		$address->save();
	}
	// Changing status of order
	if ($oldstatus != $status) {
		$miniShop->changeOrderStatus($id, $status);
	}
}
else {
	return $modx->error->failure($modx->lexicon('ms.orders.item_err_save'));
}

return $modx->error->success('', $res);
