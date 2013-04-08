<?php

/**
 * 设置管理员的session内容
 *
 * @access  public
 * @param   integer $user_id        管理员编号
 * @param   string  $username       管理员姓名
 * @param   string  $action_list    权限列表
 * @param   string  $last_time      最后登录时间
 * @return  void
 */

function set_admin_session($user_id, $username, $action_list, $last_time)
{
	$_SESSION['admin_id']    = $user_id;
	$_SESSION['admin_name']  = $username;
	$_SESSION['action_list'] = $action_list;
	$_SESSION['last_check']  = $last_time; // 用于保存最后一次检查订单的时间
}


/**
 * 系统提示信息
 *
 * @access      public
 * @param       string      msg_detail      消息内容
 * @param       int         msg_type        消息类型， 0消息，1错误，2询问
 * @param       array       links           可选的链接
 * @param       boolen      $auto_redirect  是否需要自动跳转
 * @return      void
 */
function sys_msg($msg_detail, $msg_type = 0, $links = array(), $auto_redirect = true)
{
    if (count($links) == 0)
    {
        $links[0]['text'] = $GLOBALS['_LANG']['go_back'];
        $links[0]['href'] = 'javascript:history.go(-1)';
    }

    assign_query_info();

    $GLOBALS['smarty']->assign('ur_here',     $GLOBALS['_LANG']['system_message']);
    $GLOBALS['smarty']->assign('msg_detail',  $msg_detail);
    $GLOBALS['smarty']->assign('msg_type',    $msg_type);
    $GLOBALS['smarty']->assign('links',       $links);
    $GLOBALS['smarty']->assign('default_url', $links[0]['href']);
    $GLOBALS['smarty']->assign('auto_redirect', $auto_redirect);

    $GLOBALS['smarty']->display('message.html');

    exit;
}
/**
 *
 * 功能：对字符串或数组进行本地化编码
 * 参数：字符串或数组
 * 返回值：编码后的字符串或数组
 *
 */
function gbktoutf8($data) 
{
	if(is_array($data)){
		foreach($data as &$value){
			$value = gbktoutf8($value);
		}
	} else{
		if(EC_CHARSET != 'utf-8'){
			//$data = iconv("gbk", 'utf-8',$data);
			$data = mb_convert_encoding($data,'utf-8', EC_CHARSET);
		}
	}
	return $data;
}
/**
 *
 * 功能：对字符串或数组进行本地化编码
 * 参数：字符串或数组
 * 返回值：编码后的字符串或数组
 *
 */
function utf8togbk($data) 
{
	if(is_array($data)){
		foreach($data as &$value){
			$value = utf8togbk($value);
		}
	} else{
		if(EC_CHARSET != 'utf-8'){
			//$data = iconv("gbk", 'utf-8',$data);
			$data = mb_convert_encoding($data,'gbk', EC_CHARSET);
		}
	}
	return $data;
}

/**
 * 得到当前的版本号
 * @return  string
 */
function  get_current_version()
{
	global $db, $ecs;

	$ver = $db->getOne('SELECT value FROM ' . $ecs->table('shop_config') . " WHERE code = 'ecs_version'");
	$ver = $ver ? $ver : 'v2.0.5';
	$ver = preg_replace('/\.[a-z]*$/i', '', $ver);

    return $ver;
}