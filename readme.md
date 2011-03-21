Description:
============

vBulletin addon. Add different infraction options to simplify moderation. All new features available from ACP, in infractions editor.

1. Pre & Post Hooks. This code will be executed before / after infraction given.


Custom Hooks Samples
====================

Insert name of an infraction to "Ban Reason"
-------------------------------------------- 
   
Hook: Infraction Start

    require_once(DIR . '/includes/functions_misc.php');
    $infractionlevel = verify_id('infractionlevel', $vbulletin->GPC['infractionlevelid'], 1, 1);
    $vbulletin->GPC['banreason'] = ($infractionlevel['infractionlevelid']) ?
        fetch_phrase('infractionlevel' . $infractionlevel['infractionlevelid'] . '_title', 'infractionlevel', '', true, true, userinfo['languageid']) :
        $vbulletin->GPC['customreason'];

Send email notification to administrator
----------------------------------------
 
Hook: Infraction End

    $subject = "Admin subject";
    $message = "Message body for admin";
    vbmail($vbulletin->options['webmasteremail'], $subject, $message);
        
Move infraction from post to profile (clean postinfo)
-----------------------------------------------------
    
Hook: Infraction Start

    $postinfo = array();
        
Remove avatar and photo
-----------------------
    
Hook: Infraction End

    $userpic =& datamanager_init('Userpic_Profilepic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
    $userpic->condition = "userid = " . $vbulletin->GPC['userid'];
    $userpic->delete();
    unset($userpic);
        
    $userpic =& datamanager_init('Userpic_Avatar', $vbulletin, ERRTYPE_CP, 'userpic');
    $userpic->condition = "userid = " . $vbulletin->GPC['userid'];
    $userpic->delete();
    unset($userpic);

Move user to "loosers" usergroup
-------------------------------

Hook: Infraction End

    $userinfo = fetch_userinfo($vbulletin->GPC['userid']);
    $userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
    $userdm->set_existing($userinfo);
    $userdm->set('usergroupid', 14); // Set your group id here
    $userdm->save();
    unset($userdm);
