<?php
// Adam制作 今日特价

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
checkStoreLogin();	//判断店家是否已经登录
if ((DEBUG_MODE & 2) != 2) $smarty->caching = true;

$goods_id = !empty($_GET['goods_id']) && intval($_GET['goods_id'])>0 ? intval($_GET['goods_id']) : 0;

$cache_id = sprintf('%X', crc32($goods_id.'-'.$_SESSION['user_rank'].'-'.$_CFG['lang'].'-'.$_SESSION['store']['store_id']));
if (!$smarty->is_cached('shop_goods.dwt', $cache_id)){
	$now = gmtime();
	$goods = $db->getRow('SELECT g.*, IFNULL(sp.price, g.shop_price) AS sale_price  FROM '.$ecs->table('goods').' as g '.
			'LEFT JOIN ' . $ecs->table('store_price') . " AS sp ON sp.goods_id = g.goods_id AND sp.store_id = ".$_SESSION['store']['store_id'].
			' where g.goods_id='.$goods_id.' and g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1');
	if(empty($goods)){
		ecs_header("Location: shop_specials.php\n");
		exit;
	}elseif ($goods["is_promote"]==1&&$goods["promote_start_date"]<=$now&&$goods["promote_end_date"]>$now){
		$smarty->assign("is_promote",1);
		$goods['save_price'] = number_format($goods['market_price']-$goods['promote_price'], 2, '.', '');
	}else {
		$goods['save_price'] = number_format($goods['market_price']-$goods['sale_price'], 2, '.', '');
	}
	$goods['goods_name'] = $goods['goods_name'].($goods['goods_specification']==''?'':'（'.$goods['goods_specification'].'）');
	
	$goods['give_integral'] = $goods['give_integral']<0 ? intval($goods['sale_price']) : $goods['give_integral'];
	if($goods['brand_id']>0){
		$brand = $db->getRow('SELECT brand_name,detail FROM ' . $ecs->table('brand') . 'WHERE brand_id='.$goods['brand_id']);
		$goods['brand_name'] = $brand['brand_name'];
		$goods['brand_detail'] = $brand['detail'];
	}else{
		$goods['brand_name'] = '';
		$goods['brand_detail'] = '暂无供货厂家信息';
	}
	$smarty->assign('goods',      $goods);
	
	$goods_gallery  = $db->getAll('SELECT img_original FROM '.$ecs->table('goods_gallery').' where goods_id='.$goods_id.' order by img_id limit 0,3');
	$smarty->assign('goods_gallery',      $goods_gallery);
	
	$article = $db->getAll('SELECT title,content FROM '.$ecs->table('article').' where cat_id=4 order by article_id');
	foreach ($article as $k=>$v){
		$article[$k]['index'] = $k+4;
	}
	$smarty->assign('article',      $article);
	$smarty->assign('detail_look_count',      count($article)+3);
	
	
}
$smarty->display('shop_goods.html', $cache_id);
?>