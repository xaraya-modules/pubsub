<?php
sys::import('modules.dynamicdata.class.objects.master');
function pubsub_user_submit_form($args) {
	extract($args);
	
	if (!xarVarFetch('name',        'str',    $name,            'pubsub_subscriptions', XARVAR_NOT_REQUIRED)) return;
	if (!xarVarFetch('userid',      'int',    $userid,       0, XARVAR_NOT_REQUIRED)) return;
	if (!xarVarFetch('event_id',    'int',    $event_id,       0, XARVAR_NOT_REQUIRED)) return;
	//if (!xarVarFetch('action_id',   'int',    $action_id,       0, XARVAR_NOT_REQUIRED)) return;
	if (!xarVarFetch('email',       'email', $email,'',XARVAR_NOT_REQUIRED)) return;
	
	// Set some default values
	$default_values = array(
	    'event'   => $event_id,
	    'user_id' => $userid,
	    'email'   => $email,
	);
	
	// Get the object we are working with
	$data['object'] = DataObjectMaster::getObject(array('name' => $name));
	$data['object']->setFieldValues($default_values,1);
			
	// Good data: create the item
	$itemid = $data['object']->createItem();

    // If this is an AJAX call, end here
    xarController::$request->exitAjax();

	// Jump to the next page
    xarController::redirect(xarServer::getCurrentURL());
	return true;
}

?>