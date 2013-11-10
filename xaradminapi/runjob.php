<?php
/**
 * Pubsub Module
 *
 * @package modules
 * @subpackage pubsub module
 * @category Third Party Xaraya Module
 * @version 2.0.0
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.com/index.php/release/181.html
 * @author Pubsub Module Development Team
 * @author Chris Dudley <miko@xaraya.com>
 * @author Garrett Hunter <garrett@blacktower.com>
 */
/**
 * run the job
 * @param $args['handlingid'] the process handling id
 * @param $args['pubsubid'] the subscription id
 * @param $args['objectid'] the specific object in the module
 * @param $args['templateid'] the template id for this job
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, DATABASE_ERROR
 */
function pubsub_adminapi_runjob($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    $invalid = array();
    if (!isset($handlingid) || !is_numeric($handlingid)) {
        $invalid[] = 'handlingid';
    }
    if (!isset($pubsubid) || !is_numeric($pubsubid)) {
        $invalid[] = 'pubsubid';
    }
    if (!isset($objectid) || !is_numeric($objectid)) {
        $invalid[] = 'objectid';
    }
    if (!isset($templateid) || !is_numeric($templateid)) {
        $invalid[] = 'templateid';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) function #(3)() in module #(4)',
                    join(', ',$invalid), 'runjob', 'Pubsub');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Database information
    $dbconn =& xarDB::getConn();
    $xartable =& xarDB::getTables();
    $pubsubregtable = $xartable['pubsub_reg'];
    $pubsubeventstable = $xartable['pubsub_events'];

    // Get info on job to run
    $query = "SELECT xar_actionid,
                     xar_userid,
                     $pubsubregtable.xar_eventid,
                     xar_modid,
                     xar_itemtype,
                     $pubsubregtable.xar_email
              FROM $pubsubregtable
              LEFT JOIN $pubsubeventstable
              ON $pubsubregtable.xar_eventid = $pubsubeventstable.xar_eventid
              WHERE xar_pubsubid = ?";
    $result   = $dbconn->Execute($query, array((int)$pubsubid));
    if (!$result) return;

    if ($result->EOF) return;

    list($actionid,$userid,$eventid,$modid,$itemtype,$email) = $result->fields;

    if( $userid != -1 ) {
        $info = xarUserGetVar('email',$userid);
        $name = xarUserGetVar('uname',$userid);
    } else {
        $emailinfo = explode(' ',$email,2);
        $info    = $emailinfo[0];
        if( isset($emailinfo[1]) ) {
            $name = $emailinfo[1];
        } else {
            $name = '';
        }
    }

    $modinfo = xarModGetInfo($modid);
    if (empty($modinfo['name'])) {
        $msg = xarML('Invalid #(1) function #(3)() in module #(4)',
                    'module', 'runjob', 'Pubsub');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    } else {
        $modname = $modinfo['name'];
    }

    switch ($actionid)
    {
        case 1:
            $action = 'mail';
            break;
        case 2: // currently unused
            $action = 'htmlmail';
            break;
        default:
            $action = 'unknown';
            break;
    }

    if ($action == "mail" || $action == "htmlmail") {
        // Database information
        $pubsubtemplatestable = $xartable['pubsub_templates'];
        // Get the (compiled) template to use
        $query = "SELECT xar_compiled, xar_template
                  FROM $pubsubtemplatestable
                  WHERE xar_templateid = ?";
        $result   = $dbconn->Execute($query, array((int)$templateid));
        if (!$result) return;

        if ($result->EOF) {
            $msg = xarML('Invalid #(1) template',
                         'Pubsub');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                     new SystemException($msg));
            return;
        }

        $compiled = $result->fields[0];
        $templatecontent = $result->fields[1];

        if (empty($compiled)) {
            $msg = xarML('Invalid #(1) template',
                         'Pubsub');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                     new SystemException($msg));
            return;
        }
        // Close the result
        $result->Close();

        $tplData = array();
        $tplData['userid'] = $userid;
        $tplData['name'] = $name;
        $tplData['module'] = $modname;
        $tplData['itemtype'] = $itemtype;
        $tplData['itemid'] = $objectid;
        $tplData['templatecontent'] =$templatecontent;

        // (try to) retrieve a title and link for this item
        $itemlinks = xarMod::apiFunc($modname,'user','getitemlinks',
                                   array('itemtype' => $itemtype,
                                         'itemids' => array($objectid)),
                                   0); // don't throw an exception here
        if (!empty($itemlinks) && !empty($itemlinks[$objectid])) {
            $tplData['title'] = $itemlinks[$objectid]['label'];
            $tplData['link'] =  $itemlinks[$objectid]['url'];
        } else {
            $tplData['title'] = xarML('Item #(1)', $objectid);
            $tplData['link'] =  xarModURL($modname,'user','main');
        }

        // *** TODO  ***
        // need to define some variables for user firstname and surname,etc.
        // might not be able to use the normal BL user vars as they would
        // probably expand to currently logged in user, not the user for
        // this event.
        // But you can use $userid to get the relevant user, as above...

         if( xarModGetVar('pubsub','subjecttitle') == 1 ) {
             $subject = $tplData['title'];
         } else {
             $subject = xarML('Publish / Subscribe Notification');
         }
         $fmail = xarConfigGetVar('adminmail');
         $fname = xarConfigGetVar('adminmail');

        // call BL with the (compiled) template to parse it and generate the HTML free plaintext version
        $html = xarTplString($compiled, $tplData);
        $tplData['htmlcontent'] = $html;
        $tplData['textcontent'] = strip_tags($html);

        $UseTemplateVersions = xarModGetVar('pubsub', 'UseTemplateVersions') ? true : false;
        if ($UseTemplateVersions) {
             $htmltemplate = 'html-' . $templateid;
             $texttemplate = 'text-' . $templateid;
        } else {
             $htmltemplate = 'html';
             $texttemplate = 'text';
        }

        $htmlmessage= xarTplModule('pubsub','user','mail',$tplData,$htmltemplate);
        if (xarCurrentErrorID() == 'TEMPLATE_NOT_EXIST') {
            xarErrorHandled();
            // Default to the module template
            $htmlmessage= xarTplModule('pubsub', 'user', 'mail',$tplData,'html');
        }
        $textmessage= xarTplModule('pubsub','user','mail', $tplData,$texttemplate);
        if (xarCurrentErrorID() == 'TEMPLATE_NOT_EXIST') {
            xarErrorHandled();
            $textmessage= xarTplModule('pubsub', 'user', 'mail',$tplData,'text');
        }

            /*
            $boundary = "b" . md5(uniqid(time()));
            $message = "From: xarConfigGetVar('adminmail')\r\nReply-to: xarConfigGetVar('adminmail')\r\n";
            $message .= "Content-type: multipart/mixed; ";
            $message .= "boundary = $boundary\r\n\r\n";
            $message .= "This is a MIME encoded message.\r\n\r\n";
            // first the plaintext message
            $message .= "--$boundary\r\n";
            $message .= "Content-type: text/plain\r\n";
            $message .= "Content-Transfer-Encoding: base64";
            $message .= "\r\n\r\n" . chunk_split(base64_encode($plaintext)) . "\r\n";
            // now the HTML version
            $message .= "--$boundary\r\n";
            $message .= "Content-type: text/html\r\n";
            $message .= "Content-Transfer-Encoding: base64";
            $message .= "\r\n\r\n" . chunk_split(base64_encode($html)) . "\r\n";

            */
        if ($actionid == 2 ) {
             // Send the mail using the mail module
             if (!xarMod::apiFunc('mail',
                                'admin',
                                'sendhtmlmail',
                                array('info'     => $info,
                                      'name'     => $name,
                                      'subject'  => $subject,
                                      'message'  => $textmessage,
                                      'htmlmessage' => $htmlmessage,
                                      'from'     => $fmail,
                                      'fromname' => $fname,
                                      'usetemplates' => false))) return;
        } else {
             // plaintext mail
             if (!xarMod::apiFunc('mail',
                                'admin',
                                'sendmail',
                                array('info'     => $info,
                                      'name'     => $name,
                                      'subject'  => $subject,
                                      'message'  => $textmessage,
                                      'htmlmessage' => $htmlmessage,
                                      'from'     => $fmail,
                                      'fromname' => $fname,
                                      'usetemplates' => false))) return;
        }
        // delete job from queue now it has run
        xarMod::apiFunc('pubsub','admin','deljob', array('handlingid' => $handlingid));

    } else {
        // invalid action - update queue accordingly
        xarMod::apiFunc('pubsub','admin','updatejob',
                      array('handlingid' => $handlingid,
                            'pubsubid' => $pubsubid,
                            'objectid' => $objectid,
                            'templateid' => $templateid,
                            'status' => 'error'));
        $msg = xarML('Invalid #(1) action',
                     'Pubsub');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                 new SystemException($msg));
        return false;
    }
    return true;
}

?>
