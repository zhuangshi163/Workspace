<?php

	define('IN_ECS', true);
	require(dirname(__FILE__) . '/includes/init.php');
	
	if(!isset($_POST['right']) || (isset($_POST['right'])  && $_POST['right'] != 'right') )
	{
		exit($_LANG['check_first']);
	}
	$smarty->assign('installname',$_LANG['install']);
	$smarty->assign('install','install');	

	if(isset($_POST['install'])  && trim($_POST['install']) == 'install')
	{
		$result = '<script type="text/javascript">parent.installcallback("%s");</script>';
		if(file_exists('install.lock'))	
		{
			exit(sprintf($result,$_LANG['plugin_installed']));
		}	
		#安装
		$site_url = str_replace('/plugins/zywx/install.php','',$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		$appkey = random(4).'enl3eHp5d3h6eXd4'.random(4);
		$url  = ZYWX_PROXY.'/index.php?m=curl&a=guid&plugin_name=ecshop&domain=http://'.$site_url.'&appkey='.$appkey;
		$data = file_get_contents($url);
		$arr = json_decode($data,true);
		if(empty($arr['zywxid']))
		{
			$msg = EC_CHARSET == 'gbk' ? iconv("utf-8", "gbk",$arr['msg'])  : $arr['msg'];
			exit(sprintf($result,$msg));
		}
		$arr['step'] = '1';
		$arr['version'] = isset($arr['app_version']) ? $arr['app_version'] : '0.1';
		unset($arr['app_version']);
		$data = json_encode($arr);
		file_put_contents('proxyserver.conf',$data);

		#安装菜单树
		$file_menu_path		= ROOT_PATH.ADMIN_PATH.'/includes/inc_menu.php';
		$file_menu_bak_path = ROOT_PATH.ADMIN_PATH.'/includes/inc_menu.bak';
		if(!file_exists($file_menu_bak_path))
		{
		copy($file_menu_path,$file_menu_bak_path);
		$menu_content = <<<EOT
<?php
	include_once("inc_menu.bak");
	if (file_exists(ROOT_PATH . 'plugins/zywx/includes/inc_menu.php'))
	{
		include_once(ROOT_PATH . 'plugins/zywx/includes/inc_menu.php');
	}
EOT;
		file_put_contents($file_menu_path,$menu_content);
		}
	
		$file_lang_path		= ROOT_PATH.'languages/'.$_CFG['lang'].'/admin/common.php';
		$file_lang_bak_path = ROOT_PATH.'languages/'.$_CFG['lang'].'/admin/common.bak';
		$lang = $_CFG['lang'];
		if(!file_exists($file_lang_bak_path))
		{
			copy($file_lang_path,$file_lang_bak_path);
			if (EC_CHARSET == 'gbk'){
				$lang_content = <<<EOT
<?php
	include_once('common.bak');
	if (file_exists(ROOT_PATH.'plugins/zywx/languages/$lang/common_gbk.php'))
	{
		include_once(ROOT_PATH.'plugins/zywx/languages/$lang/common_gbk.php');
	}
EOT;
			}else{
				$lang_content = <<<EOT
<?php
	include_once('common.bak');
	if (file_exists(ROOT_PATH.'plugins/zywx/languages/$lang/common.php'))
	{
		include_once(ROOT_PATH.'plugins/zywx/languages/$lang/common.php');
	}
EOT;
			}
			file_put_contents($file_lang_path,$lang_content);
		}
		if(!file_exists('color.ini'))
		{
    		file_put_contents('color.ini','');
		}
		file_put_contents('install.lock','This plugin is install');
		echo sprintf($result,$_LANG['Install_ok']);
	}
	else
	{
		assign_query_info();
		$smarty->assign('plugin_install_tips', $_LANG['plugin_install_tips']);
		$smarty->assign('repeatCreateApp', 'false');
		$smarty->assign('ur_here', $_LANG['zinstall']);
		$smarty->display('tooltip.html');
	}
	

	function random($num)
	{
		if(!isset($num)) $num = 4;

		$rand = '';
		for($i = 0;$i < $num; $i++)
		{
			$rand .= rand(0,9);
		}
		return $rand;
	}