<?php
/**
 * 商品接口
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-12-24 下午04:19:59
 * @author	   myy
 */

define('IN_ECS', true);
include_once('includes/init.php'); 
include_once('includes/lib_goods.php');

$act = empty($_GET['act']) ? 'goodsDetail': $_GET['act'];

if(!empty ($_REQUEST['goods_id']))
{
    $goods_id = trim($_REQUEST['goods_id']);
}else{
    $msg = rpcLang('goods.php', 'param_failure1');
    $error['param_failure1'] = $msg;
    jsonExit($error);
}

$action_arr = array("goodsDetail","fittings","properties","spe","collect","goods_gallery");
if(!in_array($act,$action_arr))
{
    $msg = rpcLang('goods.php', 'error_action');
	jsonExit("{\"status\":\"$msg\"}");
}

//商品信息
if($act=="goodsDetail")
{
    $goods = zy_get_goods_info($goods_id);
    jsonExit($goods);

} 
else if($act=="goods_gallery")
{
    //商品相册列表
    $goods_gallery = get_goods_gallery($goods_id);
    jsonExit($goods_gallery);
    
} 
else if($act=="fittings")
{
    //相关配件
    $arr = zy_get_goods_fittings(array($goods_id));
    jsonExit($arr);
    
} 
else if($act=="properties")
{
    //商品属性
    $properties = zy_get_goods_properties($goods_id);  // 获得商品的规格和属性
    jsonExit($properties['pro']);
    
} else if($act=="spe")
{
    //获得商品的规格
    $properties = zy_get_goods_properties($goods_id);  // 获得商品的规格和属性
    jsonExit($properties['spe']);
    
}
else if ($act == 'collect')
{
    /* 添加收藏商品(ajax) */
    $result = array();
    $goods_id = $_REQUEST['goods_id'];
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
    {
        $result['status'] = rpcLang('user.php', 'nologin');
        $result['code']='1';
        jsonExit($result);
    }
    else
    {
        /* 检查是否已经存在于用户的收藏夹 */
        $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('collect_goods') .
            " WHERE user_id='$_SESSION[user_id]' AND goods_id = '$goods_id'";
        if ($GLOBALS['db']->GetOne($sql) > 0)
        {
            $result['status'] = rpcLang('goods.php', 'collect_existed');
            $result['code']='1';
            jsonExit($result);
        }
        else
        {
            $time = gmtime();
            $sql = "INSERT INTO " .$GLOBALS['ecs']->table('collect_goods'). " (user_id, goods_id, add_time)" .
                    "VALUES ('$_SESSION[user_id]', '$goods_id', '$time')";

            if ($GLOBALS['db']->query($sql) === false)
            {
                $result['message'] = rpcLang("goods.php", "collect_failure");
                jsonExit($result);
            }
            else
            {
                $result['status'] = rpcLang("goods.php", 'collect_success');
                $result['code']='0';
                jsonExit($result);
            }
        }
    }
}

?>