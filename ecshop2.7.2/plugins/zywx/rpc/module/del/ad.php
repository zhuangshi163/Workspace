<?php
/**
 * 广告接口
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-12-24 下午03:57:24
 * @author mayuan 
 */
 
define('IN_ECS', true);
include_once('includes/init.php');
$act = empty($_GET['act']) ? 'adList': $_GET['act'];

//广告列表 广告类型,0图片
if($act=="adList")
{

    $flashdb = array();
    if (file_exists(ROOT_PATH .'data/flash_data.xml'))
    {

        // 兼容v2.7.0及以前版本
        if (!preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"\ssort="([^"]*)"/', file_get_contents(ROOT_PATH . 'data/flash_data.xml'), $t, PREG_SET_ORDER))
        {
            preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"/', file_get_contents(ROOT_PATH . 'data/flash_data.xml'), $t, PREG_SET_ORDER);
        }
       
        if (!empty($t))
        {
            foreach ($t as $key => $val)
            {
                $val[4] = isset($val[4]) ? $val[4] : 0;
                $flashdb[] = array('src'=>$val[1],'url'=>$val[2],'text'=>$val[3],'sort'=>$val[4]);
            }
        }

    } else {
    	
        // 兼容v2.7.0及以前版本
        if (!preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"/', file_get_contents(ROOT_PATH . 'data/cycle_image.xml'), $t, PREG_SET_ORDER))
        {
            preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"/', file_get_contents(ROOT_PATH . 'data/cycle_image.xml'), $t, PREG_SET_ORDER);
        }
       
        if (!empty($t))
        {
            foreach ($t as $key => $val)
            {
                $val[4] = isset($val[4]) ? $val[4] : 0;
                $flashdb[] = array('src'=>$val[1],'url'=>$val[2]);
            }
        }
    	
    }
    jsonExit($flashdb); 
}else
{
    $msg = rpcLang('goods.php', 'error_action');
    jsonExit("{\"status\":\"$msg\"}");
}

?>
