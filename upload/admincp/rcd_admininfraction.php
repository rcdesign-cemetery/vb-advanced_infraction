<?php
// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
//define('CVS_REVISION', '$RCSfile$ - $Revision: 32878 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('user', 'cpuser', 'infraction', 'infractionlevel', 'banning');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
    print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
$vbulletin->input->clean_array_gpc('r', array(
    'infractionlevelid' => TYPE_INT,
    'infractiongroupid' => TYPE_UINT,
    'infractionbanid'   => TYPE_UINT,
));
log_admin_action(!empty($vbulletin->GPC['infractionlevelid']) ? 'infractionlevel id = ' . $vbulletin->GPC['infractionlevelid'] : '');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

print_cp_header($vbphrase['user_infraction_manager']);

if (empty($_REQUEST['do']))
{
    $_REQUEST['do'] = 'editlevel';
}

// ###################### Start add #######################
if ($_REQUEST['do'] == 'editlevel')
{
    print_form_header('rcd_admininfraction', 'updatelevel');
    if (!empty($vbulletin->GPC['infractionlevelid']))
    {
        $infraction = $db->query_first("SELECT * FROM " . TABLE_PREFIX . "infractionlevel WHERE infractionlevelid = " . $vbulletin->GPC['infractionlevelid']);

        $title = 'infractionlevel' . $infraction['infractionlevelid'] . '_title';
        $infr_user_msg_id = 'infractionlevel' . $infraction['infractionlevelid'] . '_infr_user_msg';
        
        if ($phrase = $db->query_first("
            SELECT text
            FROM " . TABLE_PREFIX . "phrase
            WHERE languageid = 0 AND
                fieldname = 'infractionlevel' AND
                varname = '$title'
        "))
        {
            $infraction['title'] = $phrase['text'];
            $infraction['titlevarname'] = 'infractionlevel' . $infraction['infractionlevelid'] . '_title';
        }
        unset($phrase);
        
        if ($phrase = $db->query_first("
            SELECT text
            FROM " . TABLE_PREFIX . "phrase
            WHERE languageid = 0 AND
                fieldname = 'infractionlevel' AND
                varname = '$infr_user_msg_id'
        "))
        {
            $infraction['infr_user_msg'] = $phrase['text'];
            $infraction['infr_user_msgvarname'] = 'infractionlevel' . $infraction['infractionlevelid'] . '_infr_user_msg';
        }
        
        if ($infraction['period'] == 'N')
        {
            $infraction['expires'] = '';
        }

        print_table_header(construct_phrase($vbphrase['x_y_id_z'], $vbphrase['user_infraction'], htmlspecialchars_uni($infraction['title']), $vbulletin->GPC['infractionlevelid']), 2, 0);
        construct_hidden_code('infractionlevelid', $vbulletin->GPC['infractionlevelid']);
    }
    else
    {
        $infraction = array(
            'warning' => 1,
            'expires' => 10,
            'period'  => 'D',
            'points'  => 1,
            'extend'  => 0,
        );
        print_table_header($vbphrase['add_new_user_infraction_level']);
    }

    if ($infraction['title'])
    {
        print_input_row($vbphrase['title'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&fieldname=infractionlevel&varname=$title&t=1", 1)  . '</dfn>', 'title', $infraction['title']);
    }
    else
    {
        print_input_row($vbphrase['title'], 'title');
    }
    
    if ($infraction['infr_user_msg'])
    {
        print_textarea_row($vbphrase['rcd_infraction_user_msg'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&fieldname=infractionlevel&varname=$infr_user_msg_id&t=1", 1)  . '</dfn>', 'infr_user_msg', $infraction['infr_user_msg']);
    }
    else
    {
        print_textarea_row($vbphrase['rcd_infraction_user_msg'], 'infr_user_msg');
    }

    $periods = array(
        'H' => $vbphrase['hours'],
        'D' => $vbphrase['days'],
        'M' => $vbphrase['months'],
        'N' => $vbphrase['never'],
    );
    $input = '<input type="text" class="bginput" name="expires" size="5" dir="ltr" tabindex="1" value="' . $infraction['expires'] . '"' . ($vbulletin->debug ? ' title="name=&quot;expires&quot;"' : '') . " />\r\n";
    $input .= '<select name="period" class="bginput" tabindex="1"' . ($vbulletin->debug ? ' title="name=&quot;period&quot;"' : '') . '>' . construct_select_options($periods, $infraction['period']) . '</select>';

    print_label_row($vbphrase['expires'], $input, '', 'top', 'expires');
    print_input_row($vbphrase['points'], 'points', $infraction['points'], true, 5);
    print_yes_no_row($vbphrase['warning'], 'warning', $infraction['warning']);
    print_yes_no_row($vbphrase['extend'], 'extend', $infraction['extend']);
    print_textarea_row($vbphrase['rcd_infraction_hook_start'], 'hook_start', $infraction['hook_start']);
    print_textarea_row($vbphrase['rcd_infraction_hook_end'], 'hook_end', $infraction['hook_end']);
    
    print_submit_row($vbphrase['save']);

}

// ###################### Start do update #######################
if ($_POST['do'] == 'updatelevel')
{

    $vbulletin->input->clean_array_gpc('p', array(
        'title'   => TYPE_STR,
        'points'  => TYPE_UINT,
        'expires' => TYPE_UINT,
        'period'  => TYPE_NOHTML,
        'warning' => TYPE_BOOL,
        'extend'  => TYPE_BOOL,
        'hook_start' => TYPE_STR,
        'hook_end' => TYPE_STR,
        'infr_user_msg' => TYPE_STR
    ));

    if (empty($vbulletin->GPC['title']) OR (empty($vbulletin->GPC['expires']) AND $vbulletin->GPC['period'] != 'N'))
    {
        print_stop_message('please_complete_required_fields');
    }

    if (empty($vbulletin->GPC['infractionlevelid']))
    {
        $db->query_write("INSERT INTO " . TABLE_PREFIX . "infractionlevel (points) VALUES (0)");
        $vbulletin->GPC['infractionlevelid'] = $db->insert_id();
    }

    if ($vbulletin->GPC['period'] == 'N')
    {
        $vbulletin->GPC['expires'] = 0;
    }

    $db->query_write("
        UPDATE " . TABLE_PREFIX . "infractionlevel
        SET points = " . $vbulletin->GPC['points'] . ",
            expires = " . $vbulletin->GPC['expires'] . ",
            period = '" . $db->escape_string($vbulletin->GPC['period']) . "',
            warning = " . intval($vbulletin->GPC['warning']) . ",
            extend = " . intval($vbulletin->GPC['extend']) . ",
            hook_start = '" . $db->escape_string($vbulletin->GPC['hook_start']) . "',
            hook_end = '" . $db->escape_string($vbulletin->GPC['hook_end']) . "' 
        WHERE infractionlevelid = " . $vbulletin->GPC['infractionlevelid'] . "
    ");

    /*insert_query*/
    $db->query_write("
        REPLACE INTO " . TABLE_PREFIX . "phrase
            (languageid, fieldname, varname, text, product, username, dateline, version)
        VALUES
            (0,
            'infractionlevel',
            'infractionlevel" . $vbulletin->GPC['infractionlevelid'] . "_title',
            '" . $db->escape_string($vbulletin->GPC['title']) . "',
            'vbulletin',
            '" . $db->escape_string($vbulletin->userinfo['username']) . "',
            " . TIMENOW . ",
            '" . $db->escape_string($vbulletin->options['templateversion']) . "')
    ");
    
     $db->query_write("
        REPLACE INTO " . TABLE_PREFIX . "phrase
            (languageid, fieldname, varname, text, product, username, dateline, version)
        VALUES
            (0,
            'infractionlevel',
            'infractionlevel" . $vbulletin->GPC['infractionlevelid'] . "_infr_user_msg',
            '" . $db->escape_string($vbulletin->GPC['infr_user_msg']) . "',
            'vbulletin',
            '" . $db->escape_string($vbulletin->userinfo['username']) . "',
            " . TIMENOW . ",
            '" . $db->escape_string($vbulletin->options['templateversion']) . "')
    ");

    require_once(DIR . '/includes/adminfunctions_language.php');
    build_language();

    define('CP_REDIRECT', 'admininfraction.php?do=modify');
    print_stop_message('saved_infraction_level_successfully');

}

// ###################### Start Remove #######################

if ($_REQUEST['do'] == 'removelevel')
{

    print_form_header('rcd_admininfraction', 'killlevel');
    construct_hidden_code('infractionlevelid', $vbulletin->GPC['infractionlevelid']);
    print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], htmlspecialchars_uni($vbphrase['infractionlevel' . $vbulletin->GPC['infractionlevelid'] . '_title'])));
    print_description_row($vbphrase['are_you_sure_you_want_to_delete_this_infraction_level']);
    print_submit_row($vbphrase['yes'], '', 2, $vbphrase['no']);

}

// ###################### Start Kill #######################

if ($_POST['do'] == 'killlevel')
{

    if ($phrase = $db->query_first("SELECT text FROM " . TABLE_PREFIX . "phrase WHERE text <> '' AND fieldname = 'infractionlevel' AND varname = 'infractionlevel" . $vbulletin->GPC['infractionlevelid'] . "_title' AND languageid IN (0," . intval($vbulletin->options['languageid']) . ") ORDER BY languageid DESC"))
    {
        $db->query_write("UPDATE " . TABLE_PREFIX . "infraction SET customreason = '" . $db->escape_string($phrase['text']) . "' WHERE infractionlevelid =" . $vbulletin->GPC['infractionlevelid']);
    }

    $db->query_write("DELETE FROM " . TABLE_PREFIX . "infractionlevel WHERE infractionlevelid = " . $vbulletin->GPC['infractionlevelid']);
    $db->query_write("DELETE FROM " . TABLE_PREFIX . "phrase WHERE fieldname = 'infractionlevel' AND varname = 'infractionlevel" . $vbulletin->GPC['infractionlevelid'] . "_title'");
    $db->query_write("DELETE FROM " . TABLE_PREFIX . "phrase WHERE fieldname = 'infractionlevel' AND varname = 'infractionlevel" . $vbulletin->GPC['infractionlevelid'] . "_infr_user_msg'");
    
    require_once(DIR . '/includes/adminfunctions_language.php');
    build_language();

    define('CP_REDIRECT', 'admininfraction.php?do=modify');
    print_stop_message('deleted_infraction_level_successfully');
}
print_cp_footer();

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 02:44, Wed Sep 15th 2010
|| # CVS: $RCSfile$ - $Revision: 32878 $
|| ####################################################################
\*======================================================================*/
?>