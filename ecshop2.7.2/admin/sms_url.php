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
 * $Id: sms_url.php 16654 2009-09-09 10:29:24Z douqinghua $
 */
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
require_once(ROOT_PATH . 'includes/cls_sms.php');
$sms = new sms();
if(!$GLOBALS['db']->getOne("SELECT count(*) FROM ".$GLOBALS['ecs']->table('shop_config')." WHERE `code`='ent_id'"))
{
   $sql="INSERT INTO ".$GLOBALS['ecs']->table('shop_config')." (`id`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`) VALUES
  (243, 2, 'ent_id', 'hidden', '', '', '', 1),
  (244, 2, 'ent_ac', 'hidden', '', '', '', 1),
  (245, 2, 'ent_sign', 'hidden', '', '', '', 1),
  (246, 2, 'ent_email', 'hidden', '', '', '', 1)";
   $GLOBALS['db']->query($sql);
    
}
if(!empty($GLOBALS['_CFG']['ent_id']))
{ 
   $url="shop_config.php?act=sms_login";
   $url_act=$url;
}
else
{
   $url=$sms->getRegisterUrl();
   $url_act='ent_binding.php';
}


?>