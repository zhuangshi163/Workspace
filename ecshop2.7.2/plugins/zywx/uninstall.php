<?php
	
	define('IN_ECS', true);

	require(dirname(__FILE__) . '/includes/init.php');
	
	assign_query_info();
	$smarty->assign('repeatCreateApp', 'false');
	$smarty->assign('ur_here', $_LANG['uninstall']);
	$smarty->assign('uninstallname',$_LANG['uninstallok']);
	$smarty->assign('uninstall','uninstall');

	if(isset($_POST['uninstall'])  && $_POST['uninstall'] == 'uninstall')
	{
		$result = '<script type="text/javascript">parent.uncallback("%s");</script>';
		if(!file_exists('install.lock'))	
		{
			exit(sprintf($result, $_LANG['plugin_not_install']));
		}

		#卸载菜单树
		$file_menu_bak_path = ROOT_PATH.ADMIN_PATH.'/includes/inc_menu.bak';
		if(file_exists($file_menu_bak_path))
		{
			$file_menu_path		= ROOT_PATH.ADMIN_PATH.'/includes/inc_menu.php';
			@unlink($file_menu_path);
			@rename($file_menu_bak_path,$file_menu_path);
		}
		else
		{
			exit(sprintf($result,$_LANG['plugin_uninstall_tree_failure']));
		}

		$file_lang_bak_path = ROOT_PATH.'languages/'.$_CFG['lang'].'/admin/common.bak';
		if(file_exists($file_lang_bak_path))
		{
			$file_lang_path		= ROOT_PATH.'languages/'.$_CFG['lang'].'/admin/common.php';
			@unlink($file_lang_path);
			@rename($file_lang_bak_path,$file_lang_path);
		}
		else
		{
			exit(sprintf($result,$_LANG['plugin_uninstall_language_failure']));
		}

		if(@unlink('install.lock'))
		{
			//平台记录插件卸载状态
			$zywx_config = json_decode(file_get_contents('proxyserver.conf'),true);
			$appkey = isset($zywx_config['zywxappkey'])  ? $zywx_config['zywxappkey'] : '';
			file_get_contents(ZYWX_APPCAN.'/plugin/installStatus.action?app_key='.$appkey.'&status=0');

			@unlink('proxyserver.conf');
			@unlink('color.ini');
			exit(sprintf($result,$_LANG['plugin_uninstall_success']));
		}
		else
		{
			exit(sprintf($result,$_LANG['plugin_uninstall_failure']));
		}
	}
	else
	{
		$smarty->assign('plugin_uninstall_tips', $_LANG['plugin_uninstall_tips']);
		$smarty->assign('plugin_uninstall_button',$_LANG['plugin_uninstall_button']);
		$smarty->display('tooltip.html');
	}