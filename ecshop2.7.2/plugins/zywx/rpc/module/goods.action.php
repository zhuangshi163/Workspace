<?php
/**
 * 商品接口
 */
include_once ('includes/lib_goods.php');
class GoodsAction {
	public function index () {
		$this->goodsDetail();
	}
	//商品信息
	public function goodsDetail () {
	if(!empty ($_REQUEST['goods_id']))
	{
	    $goods_id = trim($_REQUEST['goods_id']);
	}else{
	    $msg = rpcLang('goods.php', 'param_failure1');
	    $error['param_failure1'] = $msg;
	    jsonExit($error);
	}
		$goods = zy_get_goods_info($goods_id);
    	jsonExit($goods);
	}
	//商品相册列表
	public function goods_gallery (){
		if(!empty ($_REQUEST['goods_id']))
		{
		    $goods_id = trim($_REQUEST['goods_id']);
		}else{
		    $msg = rpcLang('goods.php', 'param_failure1');
		    $error['param_failure1'] = $msg;
		    jsonExit($error);
		}
		$goods_gallery = get_goods_gallery($goods_id);
	    jsonExit($goods_gallery);
	}
	//相关配件
	public function fittings() {
		if(!empty ($_REQUEST['goods_id']))
		{
		    $goods_id = trim($_REQUEST['goods_id']);
		}else{
		    $msg = rpcLang('goods.php', 'param_failure1');
		    $error['param_failure1'] = $msg;
		    jsonExit($error);
		}	 
    	$arr = zy_get_goods_fittings(array($goods_id));
    	jsonExit($arr);
	}
	//商品属性
	public function properties () {
		if(!empty ($_REQUEST['goods_id']))
		{
		    $goods_id = trim($_REQUEST['goods_id']);
		}else{
		    $msg = rpcLang('goods.php', 'param_failure1');
		    $error['param_failure1'] = $msg;
		    jsonExit($error);
		}	 
		$properties = zy_get_goods_properties($goods_id);  // 获得商品的规格和属性
    	jsonExit($properties['pro']);
	}
	//获得商品的规格
	public function spe() {
		if(!empty ($_REQUEST['goods_id']))
		{
		    $goods_id = trim($_REQUEST['goods_id']);
		}else{
		    $msg = rpcLang('goods.php', 'param_failure1');
		    $error['param_failure1'] = $msg;
		    jsonExit($error);
		}	 
	    $properties = zy_get_goods_properties($goods_id);  // 获得商品的规格和属性
    	jsonExit($properties['spe']);
	}
	//添加收藏商品(ajax) 
	public function collect () {
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

}