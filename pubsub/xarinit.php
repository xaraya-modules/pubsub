<?php 
/**
 * File: $Id$
 * 
 * Pubsub Initialise Module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.org
 *
 * @subpackage Pubsub Module
 * @author Chris Dudley <miko@xaraya.com>
*/ 
 
/**
 * initialise the pubsub module
 *
 * @access public
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function pubsub_init()
{
    // Get database information
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    xarDBLoadTableMaintenanceAPI();
    
    // Create tables
    $pubsubeventstable = $xartable['pubsub_events'];
    $eventsfields = array(
        'xar_eventid'=>array('type'=>'integer','null'=>FALSE,'increment'=>TRUE,'primary_key'=>TRUE),
        'xar_modid'=>array('type'=>'integer','null'=>FALSE),
        'xar_cid'=>array('type'=>'integer','null'=>FALSE),
        'xar_iid'=>array('type'=>'integer','null'=>FALSE),
        'xar_groupdescr'=>array('type'=>'varchar','size'=>64,'null'=>FALSE)
    );
    $query = xarDBCreateTable($pubsubeventstable,$eventsfields);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $pubsubregtable = $xartable['pubsub_reg'];
    $regfields = array(
        'xar_pubsubid'=>array('type'=>'integer','null'=>FALSE,'increment'=>TRUE,'primary_key'=>TRUE),
        'xar_eventid'=>array('type'=>'integer','size'=>'medium','null'=>FALSE),
        'xar_userid'=>array('type'=>'integer','size'=>'medium','null'=>FALSE),
        'xar_actionid'=>array('type'=>'varchar','size'=>100,'null'=>FALSE,'default'=>'0')
    );
    $query = xarDBCreateTable($pubsubregtable,$regfields);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $pubsubprocesstable = $xartable['pubsub_process'];
    $processfields = array(
        'xar_handlingid'=>array('type'=>'integer','null'=>FALSE,'increment'=>TRUE,'primary_key'=>TRUE),
        'xar_pubsubid'=>array('type'=>'integer','size'=>'medium','null'=>FALSE),
        'xar_objectid'=>array('type'=>'integer','size'=>'medium','null'=>FALSE),
        'xar_status'=>array('type'=>'varchar','size'=>100,'null'=>FALSE)
    );
    $query = xarDBCreateTable($pubsubprocesstable,$processfields);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $pubsubtemplatetable = $xartable['pubsub_template'];
    $templatefields = array(
        'xar_templateid'=>array('type'=>'integer','null'=>FALSE,'increment'=>TRUE,'primary_key'=>TRUE),
        'xar_eventid'=>array('type'=>'integer','size'=>'medium','null'=>FALSE),
        'xar_template'=>array('type'=>'text','size'=>'long','null'=>FALSE)
    );
    $query = xarDBCreateTable($pubsubtemplatetable,$templatefields);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Set up module hooks
    if (!xarModRegisterHook('item',
                           'create',
                           'API',
                           'pubsub',
                           'admin',
                           'createhook')) {
        return false;
    }
    if (!xarModRegisterHook('item',
                           'delete',
                           'API',
                           'pubsub',
                           'admin',
                           'deletehook')) {
        return false;
    }
    #if (!xarModRegisterHook('item',
    #                       'create',
    #                       'API',
    #                       'pubsub',
    #                       'user',
    #                       'subscribe')) {
    #   return false;
    #}
    #if (!xarModRegisterHook('item',
    #                       'delete',
    #                       'API',
    #                       'pubsub',
    #                       'user',
    #                       'unsubscribe')) {
    #    return false;
    #}
    if (!xarModRegisterHook('item',
                           'delete',
                           'API',
                           'pubsub',
                           'user',
                           'delsubscriptons')) {
        return false;
    }
    if (!xarModRegisterHook('item',
                           'display',
                           'GUI',
                           'pubsub',
                           'user',
                           'displayicon')) {
        return false;
    }

    // Initialisation successful
    return true;
}

/**
 * upgrade the pubsub module from an old version
 * 
 * @access public
 * @param oldversion float "Previous version upgrading from"
 * @returns bool
 * @raise DATABASE_ERROR
 */
function pubsub_upgrade($oldversion)
{
    return true;
}
/**
 * delete the pubsub module
 *
 * @access public
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function pubsub_delete()
{
    // Remove module hooks
    if (!xarModUnregisterHook('item',
                           'create',
                           'API',
                           'pubsub',
                           'admin',
                           'createhook')) {
        xarSessionSetVar('errormsg', _PUBSUBSCOULDNOTUNREGISTER);
    }
    #if (!xarModUnregisterHook('item',
    #                       'create',
    #                       'API',
    #                       'pubsub',
    #                       'user',
    #                       'subscribe')) {
    #    xarSessionSetVar('errormsg', _PUBSUBSCOULDNOTUNREGISTER);
    #}
    if (!xarModUnregisterHook('item',
                           'display',
                           'GUI',
                           'pubsub',
                           'user',
                           'displayicon')) {
        xarSessionSetVar('errormsg', _PUBSUBSCOULDNOTUNREGISTER);
    }
    #if (!xarModUnregisterHook('item',
    #                       'delete',
    #                       'API',
    #                       'pubsub',
    #                       'user',
    #                       'unsubscribe')) {
    #    xarSessionSetVar('errormsg', _PUBSUBSCOULDNOTUNREGISTER);
    #}
    if (!xarModUnregisterHook('item',
                           'delete',
                           'API',
                           'pubsub',
                           'user',
                           'delsubscriptions')) {
        xarSessionSetVar('errormsg', _PUBSUBSCOULDNOTUNREGISTER);
    }
    if (!xarModUnregisterHook('item',
                           'delete',
                           'API',
                           'pubsub',
                           'admin',
                           'deletehook')) {
        xarSessionSetVar('errormsg', _PUBSUBSCOULDNOTUNREGISTER);
    }

    // Get database information
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    //Load Table Maintainance API
    xarDBLoadTableMaintenanceAPI();

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['pubsub_events']);
    if (empty($query)) return; // throw back

    $query = xarDBDropTable($xartable['pubsub_reg']);
    if (empty($query)) return; // throw back

    $query = xarDBDropTable($xartable['pubsub_process']);
    if (empty($query)) return; // throw back

    $query = xarDBDropTable($xartable['pubsub_template']);
    if (empty($query)) return; // throw back

    // Deletion successful
    return true;
}

?>
