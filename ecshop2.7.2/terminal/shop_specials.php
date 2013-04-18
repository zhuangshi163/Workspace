<?php
// Adam制作 今日特价
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
checkStoreLogin();	//判断店家是否已经登录
if ((DEBUG_MODE & 2) != 2) $smarty->caching = true;

$cat_id = !empty($_GET['cat_id']) && intval($_GET['cat_id'])>0 ? intval($_GET['cat_id']) : 0;
$page = !empty($_GET['page']) && intval($_GET['page'])>1 ? intval($_GET['page']) : 1;
$page_size = 4;
$time = gmtime();
$cache_id = sprintf('%X', crc32($cat_id.'-'.$page.'-'.$_SESSION['user_rank'].'-'.$_CFG['lang'].'-'.$_SESSION['store']['store_id']));
if (!$smarty->is_cached('shop_specials.html', $cache_id)){
	
	$res = $db->getAll('SELECT cat_id,cat_name FROM '.$ecs->table('category').' where parent_id='.$_SESSION['store']['site_store_cat'].' and is_show=1 order by sort_order');
	$cat_list = array();
	$cat_list[0] = array('cat_id'=>0,'cat_name'=>'全部商品','class'=>'');
	$cat_list_key = 0;
	$key = 0;
	foreach ($res as $row){
		$key ++;
		if($row['cat_id']==$cat_id){
			$cat_list_key = $key;
			$class = ' class="cur"';
		}else $class = '';
		$cat_list[$key] = array('cat_id'=>$row['cat_id'],'cat_name'=>$row['cat_name'],'class'=>$class);
	}
	if($cat_id>0 && $cat_list_key==0) {
		ecs_header("Location: shop_specials.php\n");
		exit;
	}
	if($cat_list_key>0) $cat_list[$cat_list_key-1]['class'] = ' class="before"';
	else $cat_list[0]['class'] = ' class="cur"';
	$smarty->assign('cat_list',      $cat_list);
	
	if($cat_id==0 && $_SESSION['store']['site_store_cat']==0) $where_cat_id = '';
	else{
		$children = get_children($cat_id==0?$_SESSION['store']['site_store_cat']:$cat_id);
		$where_cat_id = "($children OR " . get_extension_goods($children) . ') AND';
	}
	
	$goods_count = $db->getOne('SELECT count(*) FROM ' . $ecs->table('goods') . ' AS g ' .
			' WHERE '.$where_cat_id.' g.is_promote = 1 AND promote_start_date <= '.$time.' AND promote_end_date >= '.$time.' and g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1');
	
	$goods_list = $db->getAll('SELECT g.goods_id, g.goods_name, g.goods_specification,g.goods_name_style, g.market_price, IFNULL(sp.price, g.shop_price) AS sale_price, g.promote_price, IFNULL(g.goods_thumb, \'images/no_picture.gif\') AS goods_thumb FROM ' . $ecs->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $ecs->table('store_price') . ' AS sp ' .
                "ON sp.goods_id = g.goods_id AND sp.store_id =".$_SESSION['store']['store_id'] .
            ' WHERE '.$where_cat_id.' g.is_promote = 1 AND promote_start_date <= '.$time.' AND promote_end_date >= '.$time.' and g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 
			ORDER BY g.sort_order,g.last_update desc limit '.($page-1) * $page_size.','.$page_size);
	foreach ($goods_list AS $k => $v){
        $goods_list[$k]['goods_style_name'] =add_style($v['goods_name'].($v['goods_specification']==''?'':'（'.$v['goods_specification'].'）'),$v['goods_name_style']);
    }
	$smarty->assign('goods_list',      $goods_list);
	$page_big = $goods_count>0?ceil($goods_count/$page_size):1;
	$smarty->assign('total',      array('count'=>$goods_count,'page_big'=>$page_big, 'page'=>$page, 'page_before'=>$page-1, 'page_after'=>($page_big>$page?$page+1:0),'cat_id'=>$cat_id));
	
	
}
$smarty->display('shop_specials.html');
?>