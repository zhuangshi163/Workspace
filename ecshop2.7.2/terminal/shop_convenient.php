<?php
// Adam制作 今日特价
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
checkStoreLogin();	//判断店家是否已经登录
if ((DEBUG_MODE & 2) != 2) $smarty->caching = true;

$cat_id = !empty($_GET['cat_id']) && intval($_GET['cat_id'])>0 ? intval($_GET['cat_id']) : 0;
$page = !empty($_GET['page']) && intval($_GET['page'])>1 ? intval($_GET['page']) : 1;
$page_size = 54;
if($cat_id==0) $cat_id = $_SESSION['store']['site_store_cat'];
/*{
	$cat_id = intval($db->getOne('SELECT cat_id FROM ' . $ecs->table('category') . 'WHERE parent_id='.$_SESSION['store']['site_store_cat'].' and is_show=1 order by sort_order limit 0,1'));
	header("Location: ./shop_convenient.php?cat_id=$cat_id\n");
    exit;
}*/
$act = ! empty ( $_GET ['act'] ) ? trim ( $_GET ['act'] ) : 'default';
if($act == "default"){
	//$cache_id = sprintf('%X', crc32($cat_id.'-'.$page.'-'.$_SESSION['user_rank'].'-'.$_CFG['lang'].'-'.$_SESSION['store']['store_id']));
	//if (!$smarty->is_cached('shop_convenient.html', $cache_id)){
		$res = $db->getAll('SELECT cat_id,cat_name FROM '.$ecs->table('category').' where parent_id='.$cat_id.' and is_show=1 order by sort_order');
		$cat_list = array();
		$i = 1;
		$i_cur = 1;
		foreach ($res as $row){
			if($i_cur==1){
				$i ++;
				if($row['cat_id']==$cat_id) $i_cur = ceil($i / 11);
			}
			//获取子分类
			$child_cat = $db->getAll('SELECT cat_id,cat_name FROM '.$ecs->table('category').' where parent_id='.$row['cat_id'].' and is_show=1 order by sort_order');
			$child_cat_array = array();
			foreach ($child_cat as $c){
				$child_cat_array[] = array(
						'cat_id'	=>	$c['cat_id'],
						'cat_name'	=>	$c['cat_name']);
			}
			$cat_list[] = array(
					'parent_cat_id'		=>	$row['cat_id'],
					'parent_cat_name'	=>	$row['cat_name'],
					'children_cat'		=>	$child_cat_array);
		}
		$smarty->assign('cat_list',      $cat_list);
		
		$children = get_children($cat_list[0]['parent_cat_id']);
		$smarty->assign('default_cat_id',      $cat_list[0]['parent_cat_id']);
		$goods_count = $db->getOne('SELECT count(*) FROM ' . $ecs->table('goods') . ' AS g ' .
				"WHERE ($children OR " . get_extension_goods($children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1');
		
		$goods_list = $db->getAll('SELECT g.*, IFNULL(sp.price, g.shop_price) AS sale_price FROM ' . $ecs->table('goods') . ' AS g ' .
				'LEFT JOIN ' . $ecs->table('store_price') . ' AS sp ' .
				"ON sp.goods_id = g.goods_id AND sp.store_id = ".$_SESSION['store']['store_id'].
				" WHERE ($children OR " . get_extension_goods($children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1
			ORDER BY g.sort_order,g.last_update desc limit '.($page-1) * $page_size.','.$page_size);
		$now = gmtime();
		foreach ($goods_list AS $k => $v){
			if($goods_list[$k]["is_promote"]==1&&$goods_list[$k]["promote_start_date"]<=$now&&$goods_list[$k]["promote_end_date"]>$now){
				$goods_list[$k]["is_promote_ex"]=1;
			}
			$goods_list[$k]['goods_style_name'] =add_style($v['goods_name'],$v['goods_name_style']);
			$goods_list[$k]['rec_id'] = intval($db->getRow('SELECT rec_id FROM '.$ecs->table('cart').' where goods_id='.$v['goods_id']." and store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'"));
			$goods_list[$k]['txt_status'] = $v['goods_number']==0 && $goods_list[$k]['rec_id']<1 ? true : false ;
		}
		$smarty->assign('goods_list',      $goods_list);
		//$smarty->assign('goods_list',      array_merge($goods_list,$goods_list,$goods_list,$goods_list,$goods_list,$goods_list,$goods_list,$goods_list,$goods_list));
		$page_big = $goods_count>0?ceil($goods_count/$page_size):1;
		$smarty->assign('total',      array('count'=>$goods_count,'page_big'=>$page_big, 'page'=>$page, 'page_before'=>$page-1, 'page_after'=>($page_big>$page?$page+1:0),'cat_id'=>$cat_id,'i_cur'=>$i_cur-1));
	//}
	$smarty->display('shop_convenient.html');
}elseif($act == "goods_list"){
	$children = get_children($cat_id);
	$goods_count = $db->getOne('SELECT count(*) FROM ' . $ecs->table('goods') . ' AS g ' .
			"WHERE ($children OR " . get_extension_goods($children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1');
	
	$goods_list = $db->getAll('SELECT g.*, IFNULL(sp.price, g.shop_price) AS sale_price FROM ' . $ecs->table('goods') . ' AS g ' .
			'LEFT JOIN ' . $ecs->table('store_price') . ' AS sp ' .
			"ON sp.goods_id = g.goods_id AND sp.store_id = ".$_SESSION['store']['store_id'].
			" WHERE ($children OR " . get_extension_goods($children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1
		ORDER BY g.sort_order,g.last_update desc limit '.($page-1) * $page_size.','.$page_size);
	$now = gmtime();
	$result_goods_list = '<div class="product_convenient">';
	foreach ($goods_list AS $k => $v){
		if($goods_list[$k]["is_promote"]==1&&$goods_list[$k]["promote_start_date"]<=$now&&$goods_list[$k]["promote_end_date"]>$now){
			$goods_list[$k]["is_promote_ex"]=1;
		}
		$goods_style_name = add_style($v['goods_name'],$v['goods_name_style']);
		$rec_id = intval($db->getRow('SELECT rec_id FROM '.$ecs->table('cart').' where goods_id='.$v['goods_id']." and store_id = ".$_SESSION['store']['store_id']." and session_id='".SESS_ID."'"));
		$txt_status = $v['goods_number']==0 && $goods_list[$k]['rec_id']<1 ? true : false ;
		
		$div_class = '';
		if(!empty($rec_id)){
			$div_class = 'class="cur"';
		}
		if($txt_status){
			$result_goods_list .= '<div id="product"'.$v['goods_id'].' '.$div_class.'>';
		}else{
			$result_goods_list .= '<div id="product'.$v['goods_id'].'" '.$div_class.' onclick="add2cart(this,'.$v['goods_id'].')">';				
		}
		$result_goods_list .= '<span class="name">'.$goods_style_name.'</span>';
		if($v['is_promote_ex']==1){
			$result_goods_list .= '<span class="sale_price">'.$v['promote_price'].'</span>';
		}else{
			$result_goods_list .= '<span class="sale_price">'.$v['sale_price'].'</span>';
		}
		$result_goods_list .= '<span class="goods_specification">'.$v['goods_specification'].'</span>';
		if($txt_status){
			$result_goods_list .= '<span class="txt" id="txt'.$v['goods_id'].'"><span class="txt_bg"></span><span>库存为零</span></span>';
		}else{
			$result_goods_list .= '<span class="none" id="txt'.$v['goods_id'].'"></span>';
		}
		$result_goods_list .= '</div>';
	}
	$result_goods_list .= '</div>';
	$page_big = $goods_count>0?ceil($goods_count/$page_size):1;
	include_once('includes/cls_json.php');
	$json = new JSON;
	$result = $json->encode(array(
			'total'			=>	array('count'=>$goods_count,'page_big'=>$page_big, 'page'=>$page, 'page_before'=>$page-1, 'page_after'=>($page_big>$page?$page+1:0),'cat_id'=>$cat_id,'i_cur'=>$i_cur-1,
			'goods_list'	=>	$result_goods_list
	)));
	die($result);
}

?>