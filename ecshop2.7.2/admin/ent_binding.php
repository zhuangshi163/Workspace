<?php
/**
 * ECSHOP 绑定通行证
 * ============================================================================
 * 版权所有 2005-2011 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: douqinghua $
 * $Id: ent_binding.php 16654 2009-09-09 10:29:24Z douqinghua $
 */
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/cls_sms.php');
$sms = new sms();
/* act操作项的初始化 */
$action = trim($_REQUEST['act']);
if (!in_array($action, array('sms_login', 'sms_login_act')))
{
    $action = 'sms_login';
}
admin_priv('shop_config');
if($action=='sms_login')
{
    /* 获取通行证帐号 */
    $sql = "SELECT `value` FROM " .$ecs->table('shop_config')." WHERE `code` = 'ent_id'";
    $ent_id = $db->getOne($sql);
    assign_query_info();

    $smarty->assign('ur_here',     $_LANG['ent_binding']);
    $smarty->assign('ent_id',     $ent_id);
    $smarty->display('ent_binding.htm');
}
elseif($action =='sms_login_act')
{
   $ent_id=$_POST['ent_id']; 
   $ent_ac=$_POST['ent_pwd'];
   if(!empty($_POST['ent_id'])&&!empty($_POST['ent_pwd']))
   {  
       $result=$sms->getSmsLogin($ent_id,$ent_ac);
       if($result['res'] == 'succ')
        {
            $sql="UPDATE" .$ecs->table('shop_config')." SET `VALUE`='".$result['info']['entid']."' WHERE `code` = 'ent_id'";
            $db->query($sql);
            $sql="UPDATE" .$ecs->table('shop_config')." SET `VALUE`='".$result['info']['password']."' WHERE `code` = 'ent_ac'";
            $db->query($sql); 
            clear_cache_files();
            $GLOBALS['_CFG']['ent_id']=$result['info']['entid'];
            $GLOBALS['_CFG']['ent_ac']=$result['info']['password'];
            $url=$sms->getSmsUrl();
            ecs_header("Location: $url\n",true);
            exit;
        }
        $link[] = array('text' => $_LANG['go_back'], 'href'=>'javascript:history.back(-1)');
        sys_msg($_LANG['edit_ent_failure'], 0, $link);
   }
   else
   {
       $link[] = array('text' => $_LANG['go_back'], 'href'=>'javascript:history.back(-1)');
       sys_msg($_LANG['entid_empty'], 0, $link);
   }
}
?>