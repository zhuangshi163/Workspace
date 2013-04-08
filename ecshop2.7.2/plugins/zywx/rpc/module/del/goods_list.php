<?php

/**
 * 商品列表接口
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-12-24 下午03:15:57
 * @author	   myy 
 */

define('IN_ECS', true);
include_once('includes/init.php');
include_once('includes/lib_goods.php');

$act = empty($_GET['act']) ? 'homeShow': $_GET['act'];

$action_arr = array("search","homeShow","brand","goodsByBrandId","showOneHomeage");

if(!in_array($act,$action_arr))
{
    $msg = rpcLang('goods.php', 'error_action');
	jsonExit("{\"status\":\"$msg\"}");
}

if ($act=="search")
{
    //搜索
    if(!empty($_REQUEST['keyword']))
	{
		$keyword = gbktoutf8(trim($_REQUEST['keyword']));
		$page = !empty($_REQUEST['page']) ? intval(trim($_REQUEST['page'])) : 1;
		$page_size = !empty($_REQUEST['page_size']) ? intval(trim($_REQUEST['page_size'])) : 3;
		
		//计算查询结果条数
		$sql_count = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods') . " where goods_name like '%$keyword%' ";
		$record_count = $GLOBALS['db']->getOne($sql_count);
		
		$list = array();
		if($record_count>0)
		{
			$pager = get_pager('goods_list.php', array('act' => $act), $record_count, $page, $page_size);
			$arr = zy_search($keyword, $page, $page_size, $record_count);
			if(!empty($arr))
			{
				$list['goods'] = $arr;
				$list['pager'] = $pager;
			}else
			{
				$list['status'] = rpcLang('goods.php', 'no_data');
			}
		}
		/*
		else
		{
		   $list['status'] = rpcLang('goods.php', 'no_data');
		}
		*/
		jsonExit($list);
    }
    else
    {
		$msg = rpcLang('goods.php', 'no_keyword');
		jsonExit("{\"status\":\"$msg\"}");
    }

} else if ($act=="homeShow")
{
    //hot best new 三模块块显示
	$type = !empty($_GET['type']) ? $_GET['type'] : 'best';
	$page = !empty($_REQUEST['page']) ? intval(trim($_REQUEST['page'])) : 1;
	$orderStr = !empty($_REQUEST['orderStr']) ? trim($_REQUEST['orderStr']) : "shop_price";
	$orderVal = !empty($_REQUEST['orderVal']) ? trim($_REQUEST['orderVal']) : "0";
	$page_size = !empty($_REQUEST['page_size']) ? intval(trim($_REQUEST['page_size'])) : 10;
	
	//正序 倒叙排列
	if ($orderVal == "0")
	{
		$order = "asc";
	} else if ($orderVal == "1") 
	{
		$order = "desc";
	}
	$goods = zy_get_recommend_goods($type,$page,$page_size,$orderStr,$order);
	jsonExit($goods);
    
} else if ($act == "brand")
{
    //品牌列表
    $page = !empty($_REQUEST['page']) ? intval(trim($_REQUEST['page'])) : 1;
    $page_size = !empty($_REQUEST['page_size']) ? intval(trim($_REQUEST['page_size'])) : 10;
    $brands = zy_get_brands("brand",$page,$page_size);
    jsonExit($brands);

} else if ($act == "goodsByBrandId")
{
    //品牌下的商品列表
    $list = array();
    if (!empty($_REQUEST['id'])) 
	{
        $id = trim($_REQUEST['id']);
        $page = !empty($_REQUEST['page']) ? intval(trim($_REQUEST['page'])) : 1;
        $page_size = !empty($_REQUEST['page_size']) ? intval(trim($_REQUEST['page_size'])) : 3;
        $orderStr = !empty($_REQUEST['orderStr']) ? trim($_REQUEST['orderStr']) : "shop_price";
        $orderVal = !empty($_REQUEST['orderVal']) ? trim($_REQUEST['orderVal']) : "0";
        //正序 倒叙排列
        if ($orderVal == "0") 
		{
            $order = "asc";
        } else if ($orderVal == "1")
		{
            $order = "desc";
        }
        $sql_count = "SELECT count(*) FROM " . $GLOBALS['ecs']->table('goods') . " where brand_id = " . $id ;
        $record_count = $GLOBALS['db']->getOne($sql_count);
        $page_count = ($record_count > 0) ? intval(ceil($record_count / $page_size)) : 1;
        if($record_count>0)
		{
                $sql = "SELECT goods_id,goods_name,market_price,shop_price,goods_brief,goods_thumb,is_best,is_hot,is_new FROM " . $GLOBALS['ecs']->table('goods') . " as g where g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 and g.brand_id = " . $id . " order by " . $orderStr . " $order ";
                $res = $GLOBALS['db']->selectLimit($sql, $page_size, ($page - 1) * $page_size);
                $index = 0;
                while($row =$GLOBALS['db']->fetchRow($res))
				{
                    $arr[$index] = $row;
                    $index++;
                }
                $pager = get_pager('goods_list.php', array('act' => "goodsByBrandId"), $record_count, $page, $page_size);
                if(!empty($arr))
				{
                    $list['goods'] = $arr;
                    $list['pager'] = $pager;
                }
                /* 
                else{
                        $list['status'] = rpcLang('goods.php', 'no_data');
                }
                */
        }
    } 
    /*
    else 
	{
		$msg = rpcLang('goods.php', 'param_failure1');
		$error['param_failure1'] = $msg;
		jsonExit($error);
		exit;
    }
    */
    jsonExit($list);
}
//显示首页第一张图片 
else if ($act == "showOneHomeage") {
	//hot best new 三模块块按更新时间显示前三条数据
	$new_goods = zy_get_recommend_goods('new',1,3,'last_update','desc');
	$hot_goods = zy_get_recommend_goods('hot',1,3,'last_update','desc');
	$best_goods = zy_get_recommend_goods('best',1,3,'last_update','desc');
	
	$new =  array_values($new_goods['goods']);
	$hot =  array_values($hot_goods['goods']);
	$best =  array_values($best_goods['goods']);
	for ($i=0;$i<3;$i++){
		$new_idArr[$i]= $new[$i]['goods_id'];
		$hot_idArr[$i] = $hot[$i]['goods_id'];
		$best_idArr[$i] = $best[$i]['goods_id'];
	}
	for ($i=0;$i<3;$i++) {
		//新品
		$new_good[$i] = zy_get_goods_info($new_idArr[$i]);
		$newArr[$i] = array('id' =>$new_idArr[$i],
						    'name' =>$new_good[$i]['goods_name'],
						    'img'=>$new_good[$i]['goods_img']);
		//热卖
		$hot_good[$i] = zy_get_goods_info($hot_idArr[$i]);
		$hotArr[$i] = array('id' =>$hot_idArr[$i],
							'name' =>$hot_good[$i]['goods_name'],
							'img'=>$hot_good[$i]['goods_img']);
		//精品
		$best_good[$i] = zy_get_goods_info($best_idArr[$i]);
		$bestArr[$i] = array('id' =>$best_idArr[$i],
							 'name' =>$best_good[$i]['goods_name'],
							 'img'=>$best_good[$i]['goods_img']);
	
	}
	$Arr = array('news'=>$newArr,'hot'=>$hotArr,'best'=>$bestArr);
	//var_dump($Arr);	
	jsonExit($Arr);
	
}


?>