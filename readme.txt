Description:
------------

vBulletin addon. Add different ACP infraction options to simplify moderation.

1. Custom hooks for each infraction





Custom Hooks Samples:
=====================



Insert name of an infraction to banreason :
------------------------------------ 
   
    Infraction Start:
        require_once(DIR . '/includes/functions_misc.php');
        $infractionlevel = verify_id('infractionlevel', $vbulletin->GPC['infractionlevelid'], 1, 1);
        $vbulletin->GPC['banreason'] = ($infractionlevel['infractionlevelid']) ? 
            fetch_phrase('infractionlevel' . $infractionlevel['infractionlevelid'] . '_title', 'infractionlevel', '', true, true, $userinfo['languageid']) 
            : $vbulletin->GPC['customreason'];



Send email notification to administrator:
------------------------------------------------   
 
    Infraction End:
        $subject = "Admin subject";
        $message = "Message body for admin";
        vbmail($vbulletin->options['webmasteremail'], $subject, $message);


        
Move infraction from post to profile(clean postinfo) - put on infraction pre hook:
------------------------------------------------------------------------------------
    
    Infraction Start:
        $postinfo = array();


        
Remove avatar and photo:
------------------------
    
    Infraction End:
        $userpic =& datamanager_init('Userpic_Profilepic', $vbulletin, ERRTYPE_STANDARD, 'userpic');
        $userpic->condition = "userid = " . $vbulletin->GPC['userid'];
        $userpic->delete();
        unset($userpic);
        
        $userpic =& datamanager_init('Userpic_Avatar', $vbulletin, ERRTYPE_CP, 'userpic');
        $userpic->condition = "userid = " . $vbulletin->GPC['userid'];
        $userpic->delete();
        unset($userpic);
        


Move user to "losers" usergroup:
--------------------------------

    Infraction End:
        $userinfo = fetch_userinfo($vbulletin->GPC['userid']);
        $userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
        $userdm->set_existing($userinfo);
        $userdm->set('usergroupid', 14);
        $userdm->save();
        unset($userdm);
