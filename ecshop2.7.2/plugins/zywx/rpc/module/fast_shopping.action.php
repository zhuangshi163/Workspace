<?php
include_once ('includes/modules/category.model.php');
/**
 * 购物车接口
 */
require(RPC_ROOT . 'includes/lib_order.php');
require(RPC_ROOT . 'includes/modules/fastshopping.model.php');
class FastShoppingAction {
	public function index () {
		$this->cart();
	}
	
	//获取商品列表（包括过滤部分）
	public function search () {
		/**
		 * 参数描述：
		 * @var id 分类id.
		 * @var page 当前页.
		 * @var brand  品牌id.
		 * @var price_max 最高价格.
		 * @var price_min 最低价格.
		 * @var order 排序方式. @enum.<'ASC', 'DESC'>
		 * @var sort 排序字段.@enum.<'goods_id', 'shop_price', 'last_update'>
		 * @var filter_attr table goods_attr 中goods_attr_id使用"."连接。例如9.10.11
		 */
		global $_CFG,$ecs,$db;
		$cat_id = isset($_REQUEST['id']) ? intval($_REQUEST['id'])  : 0;
		if (!$cat_id) {
			jsonExit(null);
		}
		$cat = get_cat_info($cat_id);   // 获得分类的相关信息
		if (empty($cat)) {
			jsonExit(null);
		}
		$page = isset($_REQUEST['page'])   && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
		// $size = isset($_CFG['page_size'])  && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 1;
		$size = 1000;
		//品牌筛选.
		$brand = isset($_REQUEST['brand']) && intval($_REQUEST['brand']) > 0 ? intval($_REQUEST['brand']) : 0;
		//价格区间
		$price_max = isset($_REQUEST['price_max']) && intval($_REQUEST['price_max']) > 0 ? intval($_REQUEST['price_max']) : 0;
		$price_min = isset($_REQUEST['price_min']) && intval($_REQUEST['price_min']) > 0 ? intval($_REQUEST['price_min']) : 0;
		 
		//属性筛选.格式1.2.3  / attr_id使用.分割
		$filter_attr_str = isset($_REQUEST['filter_attr']) ? htmlspecialchars(trim($_REQUEST['filter_attr'])) : '0';
		$filter_attr_str = urldecode($filter_attr_str);
		 
		$filter_attr = empty($filter_attr_str) ? '' : explode('.', trim($filter_attr_str));
		 
		 
		/* 排序、方式及类型 */
		$default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
		$default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'shop_price' : 'last_update');
		 
		$sort  = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'shop_price', 'last_update'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;
		$order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC')))                              ? trim($_REQUEST['order']) : $default_sort_order_method;
		 
		$children = get_children($cat_id);
		 
		//属性筛选
		$ext = '';
		if (!empty($filter_attr))
		{
			$ext_sql = "SELECT DISTINCT(b.goods_id) FROM " . $ecs->table('goods_attr') . " AS a, " . $ecs->table('goods_attr') . " AS b " .  "WHERE ";
			$ext_group_goods = array();
		  
			foreach ($filter_attr AS $k => $v)// 查出符合所有筛选属性条件的商品id */
			{
				if (!is_numeric($v) || $v == 0) continue;
				$sql = $ext_sql . "b.attr_value = a.attr_value  AND a.goods_attr_id = " . $v;
				$ext_group_goods = $db->getColCached($sql);
				$ext .= ' AND ' . db_create_in($ext_group_goods, 'g.goods_id');
			}
		}
		$count = get_cagtegory_goods_count($children, $brand, $price_min, $price_max, $ext);
		$max_page = ($count> 0) ? ceil($count / $size) : 1;
		if ($page > $max_page) $page = $max_page;
		 
		$goodslist = category_get_goods($children, $brand, $price_min, $price_max, $ext, $size, $page, $sort, $order);
		
		$goodslist = array_values($goodslist);
		$pager = get_pager('category.php', $_GET, $count, $page, $size);
		//print_r(array('goods_list'=>$goodslist, 'pager'=>$pager));exit;
		//jsonExit(array('goods_list'=>$goodslist, 'pager'=>$pager));
		jsonExit(array('goods_list'=>$goodslist));
	}
	
	//购物车列表
	public function cart () {
		/* 标记购物流程为普通商品 */
		$_SESSION['flow_type'] = CART_GENERAL_GOODS;
		//取得商品列表，计算合计 */
		$cart_goods = get_cart_goods();
		if(!$cart_goods) {
			jsonExit(null);
		}
		#计算折扣
		$discount = compute_discount();
	
		#购物车中商品配件列表，取得购物车中基本件ID
		$sql = "SELECT goods_id " ."FROM " . $GLOBALS['ecs']->table('cart') ." WHERE session_id = '" . SESS_ID . "' " .
				"AND rec_type = '" . CART_GENERAL_GOODS . "' AND is_gift = 0 AND extension_code <> 'package_buy' " .
	           "AND parent_id = 0 ";
	  
		$parent_list   = $GLOBALS['db']->getCol($sql);
			$fittings_list = get_goods_fittings($parent_list);
		/*
			$cart_list = array(
					'cart_goods' => $cart_goods,
					'discount' => $discount,
					'fittings_list' => $fittings_list
		);
		*/
		$cart_list = array('cart_goods' => $cart_goods[goods_list]);
		jsonExit($cart_list);
	}
	
	
	//添加到购物车
	public function add_to_cart () {
		if (empty($_REQUEST['goods_id']))
		{
			jsonExit(null);
		}
		
		$goods_id_list = explode(",", $_REQUEST['goods_id']);

		//删除购物车商品
		remove_all_cart_goods();
		
		foreach ($goods_id_list as $goods_id){
			// 更新：添加到购物车
			$buy_number = 1;
			$goods_id = intval($goods_id);
			if($goods_id > 0){
				zy_addto_cart($goods_id, $buy_number, array(), 0);
			}
		}
		jsonExit("1");
	}

}