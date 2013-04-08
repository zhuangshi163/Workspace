<?php

	if(!defined('IN_ECS'))
	{
		die('Hacking attempt');
	}

	//error_reporting(E_ALL);
	error_reporting(E_ERROR | E_PARSE);

	define('ROOT_PATH',str_replace('plugins/zywx/includes/init.php','',str_replace('\\','/',__FILE__)));

	if(defined('DEBUG_MODE') == false)
	{
		define('DEBUG_MODE',0);
	}

	if (DIRECTORY_SEPARATOR == '\\')
	{
		@ini_set('include_path',      '.;' . ROOT_PATH);
	}
	else
	{
		@ini_set('include_path',      '.:' . ROOT_PATH);
	}

	if (isset($_SERVER['PHP_SELF']))
	{
		define('PHP_SELF', $_SERVER['PHP_SELF']);
	}
	else
	{
		define('PHP_SELF', $_SERVER['SCRIPT_NAME']);
	}

	if(file_exists(ROOT_PATH.'/data/config.php'))
	{
		include(ROOT_PATH.'/data/config.php');
	}else
	{
		include(ROOT_PATH.'/includes/config.php');
	}
	#外网服务器
	define('ZYWX_PROXY','http://te.tx100.com/proxyserver');
	define('ZYWX_APPCAN', 'http://www.appcan.cn');
	
	require(ROOT_PATH.'includes/lib_base.php');
	require(ROOT_PATH.'includes/cls_ecshop.php');
	require(ROOT_PATH.'includes/lib_common.php');
	require(ROOT_PATH.'includes/lib_time.php');
	require(ROOT_PATH.'plugins/zywx/includes/cls_exchange.php');+
	require(ROOT_PATH.'plugins/zywx/includes/lib_main.php');

	/* 对用户传入的变量进行转义操作。*/
	if (!get_magic_quotes_gpc())
	{
		if (!empty($_GET))
		{
			$_GET  = addslashes_deep($_GET);
		}
		if (!empty($_POST))
		{
			$_POST = addslashes_deep($_POST);
		}

		$_COOKIE   = addslashes_deep($_COOKIE);
		$_REQUEST  = addslashes_deep($_REQUEST);
	}

	/* 创建 ECSHOP 对象 */
	$ecs = new ECS($db_name, $prefix);
	define('DATA_DIR', $ecs->data_dir());

	define('IMAGE_DIR', $ecs->image_dir());

	/*初始化数据库类*/
	require(ROOT_PATH.'includes/cls_mysql.php');
	
	$db = new cls_mysql($db_host,$db_user,$db_pass,$db_name,'utf8',0,1);
	$db_host = $db_user = $db_pass = NULL;

	/* 初始化session */
	require(ROOT_PATH . 'includes/cls_session.php');
	$sess = new cls_session($db, $ecs->table('sessions'), $ecs->table('sessions_data'), 'ECSCP_ID');

	/*初始化action*/
	if(!isset($_REQUEST['act']))
	{
		$_REQUEST['act'] = '';
	}

	/*载入系统参数*/
	$_CFG = load_config();

    /* 创建 Smarty 对象。*/
	require(ROOT_PATH . 'includes/cls_template.php');
	$smarty = new cls_template;

	$smarty->template_dir  = ROOT_PATH . 'plugins/zywx/templates';
	$smarty->compile_dir   = ROOT_PATH . 'temp/compiled/admin';
	if ((DEBUG_MODE & 2) == 2)
	{
		$smarty->force_compile = true;
	}

	
	//加载语言包文件
	require(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/common.php');
	if (EC_CHARSET == 'utf-8'){
		if (file_exists(ROOT_PATH . 'plugins/zywx/languages/'.$_CFG['lang'].'/common.php'))
		{
			require(  ROOT_PATH . 'plugins/zywx/languages/'.$_CFG['lang']. '/common.php');
		}
	}
	if (EC_CHARSET == 'gbk'){
		if (file_exists(ROOT_PATH . 'plugins/zywx/languages/'.$_CFG['lang'].'/common_gbk.php'))
		{
			require(  ROOT_PATH . 'plugins/zywx/languages/'.$_CFG['lang']. '/common_gbk.php');
		}
	}

	!empty($_LANG) && $smarty->assign('lang', $_LANG);
	
	$smarty->assign('img_dir', 'images');

	
	/* 验证管理员身份 */
	
	if((!isset($_SESSION['admin_id']) || intval($_SESSION['admin_id']) <=0) && !in_array($_REQUEST['act'],array('login','signin'))  && !defined('NO_LOGIN'))
	{
		if (!empty($_REQUEST['is_ajax']))
		{
			make_json_error($_LANG['priv_error']);
		}else
		{
			echo $_LANG['login_time_past'];
		}
		exit;
	}
	
require(ROOT_PATH . 'plugins/zywx/includes/shopex_json.php');