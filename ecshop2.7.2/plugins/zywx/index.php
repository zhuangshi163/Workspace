<?php

	define('IN_ECS',true);
	require(dirname(__FILE__).'/includes/init.php');
	set_time_limit(0); 

	$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';

	/**
	 * 邮箱验证
	 * @param string $email 用户邮箱
	 * @param string $nickname 用户昵称
	 * @param string $password 用户密码
	 * @return 成功 定制应用 失败 输出错误提示
 	 */
	if($act == 'email_submit')
	{
		$zywxdata = json_decode(file_get_contents('proxyserver.conf'),true);
		if(!isset($_GET['login_email']))
		{
			$email = isset($_POST['email']) ? trim($_POST['email']) : '';
			$nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : '';
			//转码
			$nickname = gbktoutf8($nickname);		
			$password = isset($_POST['password']) ? trim($_POST['password']) : '';
			if(!isemail($email))
			{
				$links[] = array('text' => $_LANG['check_email'], 'href' => 'index.php');
				sys_msg($_LANG['email_format_error'], 0 ,$links);
			}
			if(!$nickname)
			{
				$links[] = array('text' => $_LANG['check_email'], 'href' => 'index.php');
				sys_msg($_LANG['nickname_error'], 0 ,$links);		
			}
			if(!$password || strlen($password) < 6)
			{
				$links[] = array('text' => $_LANG['check_email'], 'href' => 'index.php');
				sys_msg($_LANG['password_error'], 0 ,$links);
			}
			/**
			 *远程邮箱验证
			 *@return if success then null else err 
			 * 用户创建成功之后增加一个创建应用的接口，保存email,appkey,分配虚拟空间
			 */
			$site_url = str_replace('/plugins/zywx/index.php?act=email_submit','',$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			$url = ZYWX_PROXY.'/index.php?m=curl&domain=http://'.$site_url.'&authcode='.$zywxdata['zywxid'].'&email='.$email.'&nickname='.$nickname.'&password='.$password;
			$result = file_get_contents($url);
		}
		else
		{
			$email = isset($_GET['login_email']) ? trim($_GET['login_email']) : '';
			/*
			$site_url = str_replace('/plugins/zywx/','',$ecs->url());
			$url = ZYWX_PROXY.'/index.php?m=curl&a=loginReport&domain=http://'.$site_url.'&authcode='.$zywxdata['zywxid'];
			log2file('url: '.$url);
			$result = file_get_contents($url);
			*/
		}

		if(preg_match('#msg#',$result))
		{
			$result = json_decode($result,true);
			$message = EC_CHARSET == 'gbk' ? iconv("utf-8", "gbk",$result['msg'])  : $result['msg'];
			$links[] = array('text' => $_LANG['check_email'], 'href' => 'index.php');
			sys_msg($message, 0 ,$links);
		}

		//注册应用
		$url = ZYWX_PROXY.'/index.php?m=curl&a=registeApp&authcode='.$zywxdata['zywxid'].'&pluginName=ecshop';
		$register_app_result = file_get_contents($url);
		$result = json_decode($register_app_result,true);
		if (preg_match('#msg#',$register_app_result))
		{
			$message = EC_CHARSET == 'gbk' ? iconv("utf-8", "gbk",$result['msg'])  : $result['msg'];
			$links[] = array('text' => $_LANG['check_email'], 'href' => 'index.php');
			sys_msg($message, 0 ,$links);
		}
		else
		{
			$zywxdata = json_decode(file_get_contents('proxyserver.conf'),true);
			$zywxdata['zywxappkey'] = $result['appkey'];
			$zywxdata['zywxemail']  = $email;
			$zywxdata['step'] = '1';
			file_put_contents('proxyserver.conf',json_encode($zywxdata));
			//跳转到风格设置 
			$act = 'step1';	
		}
	}
	/**
	 * 进入风格设置页面 判断email,appkey 是否注册
	 */
	init();

	if($act == 'main')
	{
		$smarty->assign('ur_here', $_LANG['manage_app']);
		$smarty->assign('ec_desc', $_LANG['ec_desc']);
		$smarty->display('welcome.html');
	}

	$zywx_data  = json_decode(file_get_contents('proxyserver.conf'),true);
	/**
	 * 定制应用  判断版本号是否需要更新 记录版本号  无更新则 定制应用
	 * @param string $version 
	 */
	$repeatCreateApp  = 'false';
	if ( isset($_GET['version']) && isset($zywx_data['version']) && $_GET['version'] >= $zywx_data['version'])
	{
		$repeatCreateApp = 'true';
	}
	$smarty->assign('repeatCreateApp',$repeatCreateApp);

	if($act == 'step1')
	{
		if((!isset($_GET['version']) && !isset($zywx_data['version'])) || (isset($_GET['version']) && isset($zywx_data['version']) && $_GET['version'] > $zywx_data['version'] ) || !isset($zywx_data['version']))
		{ 
			if(isset($_GET['version']))
			{
				file_put_contents('new_version.ini',$_GET['version']);
			}
			#记录版本号
			//$version = isset($_GET['version']) ? $_GET['version'] : 0.1;
			//$zywx_data['version'] = $version;
			$zywx_data['step'] = '1';
			file_put_contents('proxyserver.conf',json_encode($zywx_data));
			$act = 'made_app';
		}
		else
		{	
			$act = 'made_app';
		}		
	}	
	
	if($act == 'data_statistics')
	{
		assign_query_info();
		$smarty->assign('ur_here', $_LANG['data_statistics']);
		$smarty->assign('action_link', array('href' => 'index.php?act=account_management', 'text' => $_LANG['account_management']));
		$smarty->display('data_statistics.html');
	}
	/**
	 * 定制应用 第一步：风格设置本地color.ini读取颜色值
	 * @parmam string plugin_name ecshop
	 */
	else if($act == 'made_app')
	{
		$zywx_data  = json_decode(file_get_contents('proxyserver.conf'),true);
		$step	= $zywx_data['step'];
		if ($step == '1')
		{
			assign_query_info();
			$color = json_decode(file_get_contents('color.ini'),true);
			if(!$color)
			{
				$color = '242424';
			}
			$smarty->assign('ur_here', $_LANG['customiz_app']);
			$smarty->assign('zywx_proxy', $color);
			$smarty->assign('color', $color);
			$smarty->display('selectstyle.html');
		}
		else if ($step == '2')
		{
			$act = 'step2';
		}
		else
		{
			$act = 'manage_app';
		}
	}
	
	/**
	 * 定制应用 第二步 生成应用 调用iframe
	 */
	if($act == 'step2')
	{
		//设置步骤为2
		$zywx_data['step'] = '2';
		file_put_contents('proxyserver.conf',json_encode($zywx_data));
		
		$zywx_data  = json_decode(file_get_contents('proxyserver.conf'),true);
		$appkey = $zywx_data['zywxappkey'];
		$version = $zywx_data['version'];

		$new_version = file_exists('new_version.ini') ? file_get_contents('new_version.ini') : 0.1 ;
		$new_version_sock = $new_version.'.sock';
		$version_sock = $version.'.sock';
		if(file_exists($version_sock) && !file_exists('new_version.ini'))
		{
			$template  = json_decode(file_get_contents('color.ini'),true);
		}
		else
		{
			$template = isset($_POST['zdy_color']) ? $_POST['zdy_color'] : '';
			$template = substr($template,1);
			//颜色值保存至本地
			$color['template'] = $template;
			file_put_contents('color.ini',json_encode($color['template']));
		}

		if(($version == 0.1 && !file_exists($version_sock) ) || ( $new_version> $version)  )
		{
			$create_url = ZYWX_PROXY."/index.php?m=curl&a=create&version=$new_version&app_style=$template&authcode=".$zywx_data['zywxid'];
			$create_rs  = trim(file_get_contents($create_url));
			if (preg_match('#msg#',$create_rs))
			{
				$message = EC_CHARSET == 'gbk' ? iconv("utf-8", "gbk",$create_rs['msg'])  : $create_rs['msg'];
				$links[] = array('text' => $_LANG['customiz_app'], 'href' => 'index.php');
				sys_msg($message, 0 ,$links);
			}
			else
			{
				$version_data  = json_decode(file_get_contents('proxyserver.conf'),true);
				$version_data['version'] = $new_version;
				file_put_contents('proxyserver.conf',json_encode($version_data));
				@unlink('new_version.ini');
				if(!file_exists($new_version_sock))
				{
					file_exists($version_sock) && @unlink($version_sock);
					file_put_contents($new_version_sock ,$new_version);
				}
			}
			/*
			else
			{
				$c_data = json_decode($create_rs);
				$zywx_data['zywxappkey'] =$c_data->appkey;
				log2file('3:'.$zywx_data['zywxappkey']);
				file_put_contents('proxyserver.conf',json_encode($zywx_data));
			}
			*/
		}

		$iframe_url =  ZYWX_APPCAN.'/plugin/create_app_plugin.action';
		assign_query_info();

		$plugin_version = file_exists('plugin_version.ini') ? file_get_contents('plugin_version.ini') : 1.0;

		if($repeatCreateApp == 'false')
		{
			$smarty->assign('appkey', $appkey);
			$smarty->assign('iframe_url', $iframe_url);
			$smarty->assign('ur_here', $_LANG['manage_app']);
			$smarty->assign('pluginName','ecshop');
			$smarty->assign('pluginVersion',$plugin_version);
			$smarty->display('customiz_app_step3.html');
		}
		else
		{
			$url = $iframe_url.'?app_key='.$appkey .'&pluginName=ecshop&pluginVersion='.$plugin_version;
			header("location: $url");
			exit;
		}
	}

	/**
	 * 管理应用调用iframe
	 */
	else if($act == 'manage_app')
	{
		$zywx_data  = json_decode(file_get_contents('proxyserver.conf'),true);
		$appkey	= $zywx_data['zywxappkey'];
		if(empty($appkey))
		{
			$links[] = array('text' => $_LANG['customiz_app'], 'href' => 'index.php?act=made_app');
			sys_msg('appkey error', 0 ,$links);
		}
		if($zywx_data['version'] == '0.1' && $zywx_data['step'] == '1')
		{
			$links[] = array('text' => $_LANG['customiz_app'], 'href' => 'index.php?act=made_app');
			sys_msg($_LANG['customiz_app_first'], 0 ,$links);
		}

		$manage_app_url =  ZYWX_APPCAN.'/plugin/plugin_app_detail.action?app_key='.$appkey;
		assign_query_info();
		//$smarty->assign('appkey', $appkey);
		$smarty->assign('manage_app_url', $manage_app_url);
		$smarty->assign('ur_here', $_LANG['manage_app']);
		//$smarty->assign('action_link', array('href' => 'index.php?act=account_management', 'text' => $_LANG['account_management']));
		$smarty->display('manage_app.html');
	}
	/**
	 * 修改保存推广设置
	 */
	else if($act == 'extension_set_update')
	{
		$silent = intval($_REQUEST['silent']);
		$file_path = ROOT_PATH.'themes/'.$_CFG['template'].'/index.dwt';
		$index_content = file_get_contents($file_path);

		if(!file_exists('./ad.js.lock'))
		{
			$js_path = ROOT_PATH . '/plugins/zywx/js/ad.js';
			$js_s_content = file_get_contents($js_path);
			$js_content  = 'if(typeof(web_name)=="undefined")';	
			$js_content .= '{';
			$js_content .= 'var web_name ="'.$_CFG['shop_name'].'";';
			$js_content .= '}';
			file_put_contents($js_path,$js_content.$js_s_content);
			file_put_contents('./ad.js.lock','');
		}
		$ad_js = "{insert_scripts files='./plugins/zywx/js/ad.js'}";

		$zywx_config = json_decode(file_get_contents('proxyserver.conf'),true);
		$ex_set_appkey = isset($zywx_config['zywxappkey'])  ? $zywx_config['zywxappkey'] : '';
		$ex_set_url = ZYWX_APPCAN.'/plugin/promotionStatus.action';
		if($silent == 1 && !file_exists('./ad.lock'))
		 {
			//平台记录推广状态
			file_get_contents($ex_set_url.'?app_key='.$ex_set_appkey.'&status=1');
			$index_content .= $ad_js;
			file_put_contents('./ad.lock','advertising is install');
			file_put_contents($file_path,$index_content);
		 }else if($silent == 0 && file_exists('./ad.lock'))
		 {
			//平台记录推广状态
			file_get_contents($ex_set_url.'?app_key='.$ex_set_appkey.'&status=0');
			$index_content = str_replace($ad_js,'',$index_content);
			@unlink('./ad.lock');
			file_put_contents($file_path,$index_content);
		 }

		 $links[] = array('text' => $_LANG['extension_set'], 'href' => 'index.php?act=extension_set');
		 sys_msg($_LANG['edit_ok'], 0 ,$links);
	}
	/**
	 * 账号管理
	 */
	else if($act == 'account_management')
	{
		$zywx_data  = json_decode(file_get_contents('proxyserver.conf'),true);
		$zywxid	= $zywx_data['zywxid'];
		if(empty($zywxid))	
		{
			$links[] = array('text' => $_LANG['customiz_app'], 'href' => 'index.php');
		 	sys_msg('zywxid error', 0 ,$links);
		}
		$user_url = ZYWX_PROXY."/index.php?&m=curl&a=getUser&authcode=$zywxid";
		$user = json_decode(trim(file_get_contents($user_url)),true);
		if(empty($user) || (is_string($user) && preg_match('#invalid#', $user)))
		{
			$links[] = array('text' => $_LANG['customiz_app'], 'href' => 'index.php');
		 	sys_msg( $_LANG['get_user_error'], 0 ,$links);
		}

		assign_query_info();
		$smarty->assign('user', $user);
		$smarty->assign('ur_here', $_LANG['account_info']);
		$smarty->display('account_manage.html');
	}
	/**
	 * 账号修改
	 */
	else if($act == 'account_password_edit')
	{
		assign_query_info();
		$smarty->assign('ur_here', $_LANG['button_user_password_edit']);
		$smarty->display('account_password_eidt.html');
	}
	/**
	 * 显示推广设置
	 */
	else if($act == 'extension_set')
	{
		 //显示内存占用情况和SQL执行时间
		 assign_query_info();
		 $ad_flag= file_exists('./ad.lock') == true ? 1 : 0;
		 $smarty->assign('ad_flag', $ad_flag);
		 $smarty->assign('link_url', 'http://www.baidu.com');
		 $smarty->assign('ur_here', $_LANG['extension_set']);
		 $smarty->display('extension_set.html');
	}
	
	//初始化判断是否注册
	function init()
	{
		global $smarty,$_LANG,$ecs;
		
		@$data = file_get_contents('proxyserver.conf');
		if (!$data) {
			$links[] = array('text' => $_LANG['customiz_app'], 'href' => 'index.php');
		 	sys_msg( $_LANG['plugin_error'], 0 ,$links,false);
		}
		$arr  = json_decode($data,true);
		if(!isset($arr['zywxemail']))
		{
			assign_query_info();
			$iframe_url = ZYWX_PROXY .'/index.php?m=curl&a=toLogin&callback_uri='.$ecs->url() .'success.php';
			$smarty->assign('iframe_url',$iframe_url);
			$smarty->assign('repeatCreateApp','false');
			$smarty->assign('ur_here', $_LANG['check_email']);
			$smarty->display('email_set.html');
			exit;
		}
	}
	
	//检测EMAIL
	function isemail($email)
	{
		return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
	}

	function log2file($str)
	{
		$fn = "index-log.log";
		@error_log(date('Y-m-d H:i:s',time())."\t".$str."\n",3,$fn);
	}