<?php
	define('IN_ECS', true);
	require(dirname(__FILE__) . '/includes/init.php');

	$current_version  = get_current_version();
	$current_version_is_use = $current_version == 'v2.7.2' ? 'can_use':'no_use';
	$plugins_is_write = is_writable(dirname(__FILE__)) == true ? 'can_write':'no_write';
	$is_continue = ($current_version_is_use == 'no_use' || $plugins_is_write == 'no_write') ? 'no':'yes' ;

	assign_query_info();
	$smarty->assign('repeatCreateApp', 'false');
	$smarty->assign('ur_here', $_LANG['check']);
	$smarty->assign('current_version_is_use',$current_version_is_use);
	$smarty->assign('plugins_is_write',$plugins_is_write);
	$smarty->assign('is_continue',$is_continue);
	$smarty->display('check.html');