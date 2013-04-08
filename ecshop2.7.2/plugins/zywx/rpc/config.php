<?php

	/**
	  *	获取制定配置的信息LLX 2012.06.01 16:04:01
	  *	
	  *	@param  code
	  *	@return   value | NO
	  */

	define('IN_ECS', true);
	include_once ('./includes/init.php');

	$code = isset($_GET['code']) ? trim($_GET['code']) : 'shop_name';
	if(!empty($_CFG) && isset($_CFG[$code]))
	{
		$msg = "{\"code\":\"".$_CFG[$code]."\"}";
	}
	else
	{
		$msg = "{\"code\":\"no\"}";
	}

	jsonExit($msg);