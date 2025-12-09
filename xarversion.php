<?php
/**
 * Pubsub Module
 *
 * @package modules
 * @subpackage pubsub module
 * @category Third Party Xaraya Module
 * @version 2.4.1
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.com/index.php/release/181.html
 * @author Pubsub Module Development Team
 * @author Chris Dudley <miko@xaraya.com>
 * @author Garrett Hunter <garrett@blacktower.com>
 * @author Marc Lutolf <mfl@netspan.ch>
 */

$modversion['name']         = 'Pubsub';
$modversion['id']           = '181';
$modversion['version']      = '2.4.1';
$modversion['displayname']  = xarML('Pubsub');
$modversion['description']  = 'Allow users to subscribe to updates to events';
$modversion['official']     = 1;
$modversion['author']       = 'Chris Dudley,Garrett Hunter';
$modversion['contact']      = 'miko@xaraya.com,garrett@blacktower.com';
$modversion['admin']        = 1;
$modversion['user']         = 0;
$modversion['class']        = 'Utility';
$modversion['category']     = 'Global';
$modversion['dependencyinfo'] = [
                                0 => [
                                        'name' => 'Xaraya Core',
                                        'version_ge' => '2.4.1',
                                     ],
                                      ];
