<?php

	define('IN_ECS', true);
	require(dirname(__FILE__) . '/includes/init.php');
	
	if(isset($_GET['act']) && 'save' == trim($_GET['act']))
	{
		$iphone_name  = trim($_POST['iphone_name']);
		$android_name = trim($_POST['android_name']);
		$app_version    = trim($_POST['app_version']);
		if(!($iphone_name || $android_name))
		{
			$links[] = array('text' => $_LANG['u_client_upgrade'], 'href' => 'client_upgrade.php');
			sys_msg($_LANG['path_empty_tips'], 0 ,$links);
		}
		if(!$app_version)
		{
			$links[] = array('text' => $_LANG['u_client_upgrade'], 'href' => 'client_upgrade.php');
			sys_msg($_LANG['version_empty_tips'], 0 ,$links);
		}

		$data['iphone_name']  = $iphone_name;
		$data['android_name'] = $android_name;
		$data['app_version']	 = $app_version;
		file_put_contents('widget.xml',serialize($data));
		$links[] = array('text' => $_LANG['u_client_upgrade'], 'href' => 'client_upgrade.php');
		sys_msg($_LANG['app_config_success'], 0 ,$links);
	}
	else
	{
		if(file_exists('widget.xml'))
		{
			$data = unserialize(file_get_contents('widget.xml'));
			$smarty->assign('iphone_name', $data['iphone_name']);
			$smarty->assign('android_name', $data['android_name']);
			$smarty->assign('app_version', $data['app_version']);
		}
		assign_query_info();
		$smarty->assign('repeatCreateApp', 'false');
		$smarty->assign('ur_here', $_LANG['u_client_upgrade']);
		$smarty->display('client_upgrade.html');
	}