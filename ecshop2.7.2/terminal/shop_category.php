<?php
// 商品分类
define ( 'IN_ECS', true );
require (dirname ( __FILE__ ) . '/includes/init.php');
checkStoreLogin();	//判断店家是否已经登录
if ((DEBUG_MODE & 2) != 2){
	$smarty->caching = true;
}

$act = ! empty ( $_GET ['act'] ) ? trim ( $_GET ['act'] ) : 'default';
if ($act == 'default' || $act=='info') {
	$top_cat_id = ! empty ( $_GET ['top_cat_id'] ) && intval ( $_GET ['top_cat_id'] ) > 0 ? intval ( $_GET ['top_cat_id'] ) : 0;
	$cat_id = ! empty ( $_GET ['cat_id'] ) && intval ( $_GET ['cat_id'] ) > 0 ? intval ( $_GET ['cat_id'] ) : 0;
	if ($cat_id == 0)
		$cat_id = $_SESSION['store']['site_store_cat'];
	
	$cache_id = sprintf ( '%X', crc32 ( $cat_id . '-' . $_SESSION ['user_rank'] . '-' . $_CFG ['lang'] . '-' . $_SESSION['store']['store_id'] ) );
	if (! $smarty->is_cached ( 'shop_category.dwt', $cache_id )) {
		$parent_array = array($top_cat_id);
		$level = 2;
		$children_cate = get_child_cate($parent_array, $level);	//获取分类的所有下2级分类
		$cat_list = array();
		
		if (!empty($children_cate)){
			for ($i=0; $i<$level; $i++){
				foreach ($children_cate as $key => $val){
					if(in_array($val['parent_id'], $parent_array)){
						$c = array(
								'cat_id'	=>	$val['cat_id'],
								'cat_name'	=>	$val['cat_name']
						);
						if($i == 0){
							$cat_list[$val['cat_id']] = $c;
						}else{
							$cat_list[$val['parent_id']]['child'][$val['cat_id']] = $c;
						}
						unset($children_cate[$key]);
					}
				}
				$parent_array = array_keys($cat_list);
			}
			$smarty->assign ( 'cat_list', $cat_list );
			$smarty->assign("top_cat_id",$top_cat_id);
		}
		
		$sql = "select cat_id, cat_name from ".$GLOBALS['ecs']->table('article_cat')." where parent_id=12 order by sort_order";
		$res = $GLOBALS['db']->getAll($sql);
		foreach ($res as $row){
			$article_cat_list[$row['cat_id']] = $row;
		}
		$smarty->assign ( 'article_cat_list', $article_cat_list);
		//获取分类文章
		if (!empty($article_cat_list) && !empty($cat_list)){
			$terminal_cats = db_create_in(array_keys($cat_list), 'terminal_cat_id');
			$article_cats = db_create_in(array_keys($article_cat_list), "cat_id");
			$sql = "select article_id, terminal_cat_id, content, cat_id	from "
					.$GLOBALS['ecs']->table('article')." where $terminal_cats and $article_cats " 
					."order by add_time desc";
			$cat_article = $GLOBALS['db']->getAll($sql);
			$intro = "var intro = [";
			foreach ($cat_article as $key => $val){
				$intro .= "{'article_id':'$val[article_id]', 'terminal_cat_id':'$val[terminal_cat_id]', 'content':'$val[content]', 'cat_id':'$val[cat_id]'},";
			}
			$intro .= "];";
			$intro = preg_replace("/(,];)$/", "];", $intro);
			$smarty->assign ( 'cat_article', $intro);
		}
	}
	$smarty->display ( 'shop_category.html', $cache_id );
}elseif($act=="goods_list"){
	$cat_id = ! empty ( $_GET ['cat_id'] ) && intval ( $_GET ['cat_id'] ) > 0 ? intval ( $_GET ['cat_id'] ) : 0;
	$page = ! empty ( $_GET ['page'] ) && intval ( $_GET ['page'] ) > 1 ? intval ( $_GET ['page'] ) : 1;
	$page_size = 4;
	
	$cache_id = sprintf ( '%X', crc32 ( $cat_id . '-' . $page . '-' . $_SESSION ['user_rank'] . '-' . $_CFG ['lang'] . '-' . $_SESSION['store']['store_id'] ) );
	if (! $smarty->is_cached ( 'shop_category.dwt', $cache_id )) {
		$children = get_children ( $cat_id );
		$sql = "SELECT count(*) FROM " . $GLOBALS['ecs']->table( 'goods' ) . " AS g WHERE ($children OR " 
				. get_extension_goods ( $children ) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1';
		$goods_count = $GLOBALS['db']->getOne ($sql);
		$start_index = ($page - 1) * $page_size;
		$sql = "SELECT g.*, IFNULL(sp.price, g.shop_price) AS sale_price, IFNULL(g.goods_thumb, 'images/no_picture.gif')"
				." AS goods_thumb FROM ".$GLOBALS['ecs']->table('goods' )." AS g LEFT JOIN "
				.$GLOBALS['ecs']->table('store_price')." AS sp ON sp.goods_id = g.goods_id AND sp.store_id =".$_SESSION['store']['store_id']
				." WHERE ($children OR ".get_extension_goods($children)." ) AND g.is_delete = 0 AND g.is_on_sale = 1 "
				." AND g.is_alone_sale = 1 ORDER BY g.sort_order, g.last_update desc "
				." limit " . $start_index . "," . $page_size;
		$goods_list = $GLOBALS['db']->getAll ($sql);
		$now = gmtime ();
	
		foreach ( $goods_list as $k => $v ) {
			$goods_list[$k]['goods_thumb'] = empty($goods_list[$k]['goods_thumb'])?"":"../".$goods_list[$k]['goods_thumb'];
			if ($goods_list [$k] ["is_promote"] == 1 && $goods_list [$k] ["promote_start_date"] <= $now && $goods_list [$k] ["promote_end_date"] > $now) {
				$goods_list [$k] ["is_promote_ex"] = 1;
			}
			$goods_list [$k] ['goods_style_name'] = add_style ( $v ['goods_name'] . ($v ['goods_specification'] == '' ? '' : '（' . $v ['goods_specification'] . '）'), $v ['goods_name_style'] );
		}
		$total_page = $goods_count > 0 ? ceil ( $goods_count / $page_size ) : 1;
		
		$page_info = array (
				'page_size'		=>	$page_size, 
				'count' => intval($goods_count),
				'total_page' => $total_page,
				'cur_page' => $page,
				'page_before' => $page - 1,
				'page_after' => ($total_page > $page ? $page + 1 : 0));
	}
	include_once('includes/cls_json.php');
	$json = new JSON;
	$result = $json->encode(array(
			"page_info"		=>	$page_info,
			"goods_list"	=>	$goods_list
			));
	die($result);
}

/**
 * 获取指定商品分类的下级分类
 * @param int $parent_array	父类分类数组
 * @param int $level		指定下级分类级数
 */
function get_child_cate($parent_array=array(), $level=1){
	$level--;
	$result = array();
	$parent_id_in = db_create_in($parent_array, 'parent_id');
	$sql = "select cat_id, cat_name, parent_id from ".$GLOBALS['ecs']->table('category').
			" where $parent_id_in and is_show=1 order by sort_order";
	$res = $GLOBALS['db']->getAll($sql);
	foreach ($res as $row){
		$val[$row['cat_id']] = $row;
	}
	$result = array_merge($result, $val);
	if($level > 0){
		//递归，获取下级分类
		$child = get_child_cate(array_keys($val), $level);
		$result = array_merge($result, $child);
	}
	return $result;
}
?>