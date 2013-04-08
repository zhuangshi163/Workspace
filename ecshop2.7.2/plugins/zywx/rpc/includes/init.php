<?php

/**
 * ECSHOP rpc Widget接口公共函数
 * 
 * $Author: lilixing $
 * $Id: init.php 15013 2011-12-23 09:31:42Z lilixing $
 */
error_reporting(E_ERROR | E_PARSE);
header("Content-type:text/html;charset=utf-8");
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

if (__FILE__ == '')
{
    die('Fatal error code: 0');
}

/* 取得当前ecshop所在的根目录 */
define('ROOT_PATH', str_replace('plugins/zywx/rpc/includes/init.php', '', str_replace('\\', '/', __FILE__)));
define('RPC_ROOT', dirname(dirname(__FILE__)).'/');
define('EC_PATH','http://ecshop.3g2win.com/');
define("ACTION", 'act');
define("OTHERACTION", 'step');
if (DIRECTORY_SEPARATOR == '\\')
{
    @ini_set('include_path',      '.;' . ROOT_PATH);
}
else
{
    @ini_set('include_path',      '.:' . ROOT_PATH);
}

if (file_exists(ROOT_PATH . 'data/config.php'))
{
    include(ROOT_PATH . 'data/config.php');
}
else
{
    include(ROOT_PATH . 'includes/config.php');
}

if (defined('DEBUG_MODE') == false)
{
    define('DEBUG_MODE', 7);
}

if (PHP_VERSION >= '5.1' && !empty($timezone))
{
    date_default_timezone_set($timezone);
}

$php_self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
if ('/' == substr($php_self, -1))
{
    $php_self .= 'index.php';
}
define('PHP_SELF', $php_self);


//require(ROOT_PATH . 'includes/cls_ecshop.php');
//require(ROOT_PATH . 'includes/lib_base.php');
//require(ROOT_PATH . 'includes/lib_main.php');
//require(ROOT_PATH . 'includes/inc_constant.php');
//2012-03-30
require(RPC_ROOT . 'includes/inc_constant.php');
require(RPC_ROOT . 'includes/lib_base.php');
require(RPC_ROOT . 'includes/lib_main_ec.php');

require(RPC_ROOT . 'includes/lib_goods.php');
require(RPC_ROOT . 'includes/lib_common.php');
require(RPC_ROOT . 'includes/lib_time.php');
require(RPC_ROOT . 'includes/lib_main.php');
//3-23 删除邮箱验证
require(RPC_ROOT . 'includes/ecshop.php');
require(RPC_ROOT . 'includes/cls_ecshop.php');

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
//$ecs = new ECS($db_name, $prefix);
//3-23
$ecs = new RPC_ECS($db_name, $prefix);

/* 初始化数据库类 */
//require(ROOT_PATH . 'includes/cls_mysql.php');
require(RPC_ROOT . 'includes/cls_mysql.php');
$db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
$db_host = $db_user = $db_pass = $db_name = NULL;


/* 载入系统参数 */
$_CFG = load_config();


/* 初始化session */
require(RPC_ROOT . 'includes/cls_session.php');
$sess = new cls_session($db, $ecs->table('sessions'), $ecs->table('sessions_data'), 'ecsid');
define('SESS_ID', $sess->get_session_id());


if (!defined('INIT_NO_USERS'))
{
    /* 会员信息 */
    $user =& init_users();
    if (empty($_SESSION['user_id']))
    {
        if ($user->get_cookie())
        {
            /* 如果会员已经登录并且还没有获得会员的帐户余额、积分以及优惠券 */
            if ($_SESSION['user_id'] > 0 && !isset($_SESSION['user_money']))
            {
                update_user_info();
            }
        }
        else
        {
            $_SESSION['user_id']     = 0;
            $_SESSION['user_name']   = '';
            $_SESSION['email']       = '';
            $_SESSION['user_rank']   = 0;
            $_SESSION['discount']    = 1.00;
        }
    }
}


/* 判断是否支持gzip模式 */
if (gzip_enabled())
{
    ob_start('ob_gzhandler');
}  
?>
