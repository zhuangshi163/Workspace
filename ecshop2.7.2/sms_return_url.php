<?php
/**
 * ECSHOP 注册短信
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: douqinghua $
 * $Id: sms_return_url.php 16654 2009-09-09 10:29:24Z douqinghua $
 */
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/cls_sms.php');
$sms = new sms();
$info=$sms->registerRespond();
if($info)
{
    $sql="UPDATE" .$ecs->table('shop_config')." SET `VALUE`='".$info['ent_id']."' WHERE `code` = 'ent_id'";
    $db->query($sql);
    $sql="UPDATE" .$ecs->table('shop_config')." SET `VALUE`='".$info['ent_ac']."' WHERE `code` = 'ent_ac'";
    $db->query($sql);
    $sql="UPDATE" .$ecs->table('shop_config')." SET `VALUE`='".$info['ent_email']."' WHERE `code` = 'ent_email'";
    $db->query($sql); 
    clear_cache_files();
    $GLOBALS['_CFG']['ent_id']=$info['ent_id'];
    $GLOBALS['_CFG']['ent_ac']=$info['ent_ac'];
    $url=$sms->getSmsUrl();
    ecs_header("Location: $url\n",true);
    exit; 
}
else
{
   echo  $_LANG['register_fail'];
}
?>