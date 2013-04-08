<?php
define('IN_ECS', true);
include_once('includes/init.php');
$act = empty($_GET['act']) ? 'list': $_GET['act'];
//无分页商品分类列表
if($act == 'tree_list') {
     $result = get_categories_tree();
	 //树状count.
     //$count = categoriesCount($result);
   	 if (!$result) {
         $result = rpcLang('category.php', 'category_list_empty');
		 jsonExit("{\"status\":\"$result\"}");
	 } else {
         jsonExit($result);
	 }
}
//有分页商品(平铺商品)分类列表
else if($act == 'falt_list') {
	 $page = isset($_REQUEST['page']) && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
	 $result = get_categories_tree();
	 if (!$result) {
         $result = rpcLang('category.php', 'category_list_empty');
		 jsonExit("{\"status\":\"$result\"}");
	 }
	 //平铺
     $flat_result = array();
     arrayFlat($result, $flat_result);
     $count = count($flat_result);
     //获取分页信息
	 $pager  = get_pager('user.php', array('act' => $act), $count, $page);
     //print_r(array('flat_result'=>$flat_result, 'pager'=>$pager));exit;
     jsonExit(array('flat_result'=>$flat_result, 'pager'=>$pager));
	
}
//获取商品列表（包括过滤部分）
else if ($act == 'search') {
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
	$cat_id = isset($_REQUEST['id']) ? intval($_REQUEST['id'])  : 0;
    if (!$cat_id) {
    	$result = rpcLang('category.php', 'goodslist_failure');
        jsonExit("{\"status\":\"$result\"}");
    }
    $cat = get_cat_info($cat_id);   // 获得分类的相关信息
    if (empty($cat)) {
    	$result = rpcLang('category.php', 'search_cat_failure');
        jsonExit("{\"status\":\"$result\"}");
    }
    
    $page = isset($_REQUEST['page'])   && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
    // $size = isset($_CFG['page_size'])  && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 1;
    $size = 10;
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
    if (!$goodslist) {
		$goodslist = rpcLang('category.php', 'goodslist_empty');
	}
	$goodslist = array_values($goodslist);
	$pager = get_pager('category.php', $_GET, $count, $page, $size);
	//print_r(array('goods_list'=>$goodslist, 'pager'=>$pager));exit;
    jsonExit(array('goods_list'=>$goodslist, 'pager'=>$pager));
}
//获取分类的筛选属性.
else if ($act == 'attr_list') {
	$cat_id = isset($_REQUEST['id']) ? intval($_REQUEST['id'])  : 0;
    if (!$cat_id ) {
        $result = rpcLang('category.php', 'goodslist_failure');
        jsonExit("{\"status\":\"$result\"}");
    }
    //get category info error.
    $cat = get_cat_info($cat_id);   // 获得分类的相关信息
    $children = get_children($cat_id);
     if (empty($cat)) {
        $result = rpcLang('category.php', 'category_attr_failure');
        jsonExit("{\"status\":\"$result\"}");
    }
    $all_attr_list = array();
    if ($cat['filter_attr'])
    {
        $cat_filter_attr = explode(',', $cat['filter_attr']);       //提取出此分类的筛选属性

        foreach ($cat_filter_attr AS $key => $value)
        {
            $sql = "SELECT a.attr_name FROM " . $ecs->table('attribute') . " AS a, " . $ecs->table('goods_attr') . " AS ga, " . $ecs->table('goods') . " AS g WHERE ($children OR " . get_extension_goods($children) . ") AND a.attr_id = ga.attr_id AND g.goods_id = ga.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND a.attr_id='$value'";
            if($temp_name = $db->getOne($sql))
            {
                $all_attr_list[$key]['filter_attr_name'] = $temp_name;
                
                $sql = "SELECT a.attr_id, MIN(a.goods_attr_id ) AS goods_id, a.attr_value AS attr_value FROM " . $ecs->table('goods_attr') . " AS a, " . $ecs->table('goods') .
                       " AS g" .
                       " WHERE ($children OR " . get_extension_goods($children) . ') AND g.goods_id = a.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 '.
                       " AND a.attr_id='$value' ".
                       " GROUP BY a.attr_value";

                $attr_list = $db->getAll($sql);
                if (count($attr_list) <= 0) continue;
                foreach ($attr_list as $k => $v)
                {
                    $all_attr_list[$key]['attr_list'][$k]['attr_value'] = $v['attr_value'];
                    $all_attr_list[$key]['attr_list'][$k]['goods_attr_id'] = $v['goods_id'];
                }
            }
        }
    }
    if (!$all_attr_list) {
    	$result = rpcLang('category.php', 'all_attr_list_failure');
        jsonExit("{\"status\":\"$result\"}");
    	
    }
    //var_dump($all_attr_list);exit();
    jsonExit($all_attr_list);
}
//获取某类别下的品牌列表.
else if ($act == 'brands') {
	$cat_id = isset($_REQUEST['id']) ? intval($_REQUEST['id'])  : 0;
    if (!$cat_id) {
    	$result = rpcLang('category.php', 'goodslist_failure');
        jsonExit("{\"status\":\"$result\"}");
    } 
    $children = get_children($cat_id);
    $sql = "SELECT b.brand_id, b.brand_name, COUNT(*) AS goods_num ".
            "FROM " . $GLOBALS['ecs']->table('brand') . "AS b, ".
                $GLOBALS['ecs']->table('goods') . " AS g LEFT JOIN ". $GLOBALS['ecs']->table('goods_cat') . " AS gc ON g.goods_id = gc.goods_id " .
            "WHERE g.brand_id = b.brand_id AND ($children OR " . 'gc.cat_id ' . db_create_in(array_unique(array_merge(array($cat_id), array_keys(cat_list($cat_id, 0, false))))) . ") AND b.is_show = 1 " .
            " AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".
            "GROUP BY b.brand_id HAVING goods_num > 0 ORDER BY b.sort_order, b.brand_id ASC";

    $brands = $GLOBALS['db']->getAll($sql);
    if (!$brands) {
	    $result = rpcLang('category.php', 'brands_failure');
        jsonExit("{\"status\":\"$result\"}");
    }
    //var_dump($brands);exit;
    jsonExit($brands);
}
//获取某类别下的价格分级列表
else if($act == 'prices') {
	$cat_id = isset($_REQUEST['id']) ? intval($_REQUEST['id'])  : 0;
    if (!$cat_id)  {
    	$result = rpcLang('category.php', 'goodslist_failure');
        jsonExit("{\"status\":\"$result\"}");
    }
	$cat = get_cat_info($cat_id);   // 获得分类的相关信息
	$children = get_children($cat_id);
	/* 获取价格分级 */
    if ($cat['grade'] == 0  && $cat['parent_id'] != 0)
    {
        $cat['grade'] = get_parent_grade($cat_id); //如果当前分类级别为空，取最近的上级分类
    }

    if ($cat['grade'] > 1)
    {
        /* 需要价格分级 */

        /*
            算法思路：
                1、当分级大于1时，进行价格分级
                2、取出该类下商品价格的最大值、最小值
                3、根据商品价格的最大值来计算商品价格的分级数量级：
                        价格范围(不含最大值)    分级数量级
                        0-0.1                   0.001
                        0.1-1                   0.01
                        1-10                    0.1
                        10-100                  1
                        100-1000                10
                        1000-10000              100
                4、计算价格跨度：
                        取整((最大值-最小值) / (价格分级数) / 数量级) * 数量级
                5、根据价格跨度计算价格范围区间
                6、查询数据库

            可能存在问题：
                1、
                由于价格跨度是由最大值、最小值计算出来的
                然后再通过价格跨度来确定显示时的价格范围区间
                所以可能会存在价格分级数量不正确的问题
                该问题没有证明
                2、
                当价格=最大值时，分级会多出来，已被证明存在
        */

        $sql = "SELECT min(g.shop_price) AS min, max(g.shop_price) as max ".
               " FROM " . $ecs->table('goods'). " AS g ".
               " WHERE ($children OR " . get_extension_goods($children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1  ';
               //获得当前分类下商品价格的最大值、最小值

        $row = $db->getRow($sql);

        // 取得价格分级最小单位级数，比如，千元商品最小以100为级数
        $price_grade = 0.0001;
        for($i=-2; $i<= log10($row['max']); $i++)
        {
            $price_grade *= 10;
        }

        //跨度
        $dx = ceil(($row['max'] - $row['min']) / ($cat['grade']) / $price_grade) * $price_grade;
        if($dx == 0)
        {
            $dx = $price_grade;
        }

        for($i = 1; $row['min'] > $dx * $i; $i ++);

        for($j = 1; $row['min'] > $dx * ($i-1) + $price_grade * $j; $j++);
        $row['min'] = $dx * ($i-1) + $price_grade * ($j - 1);

        for(; $row['max'] >= $dx * $i; $i ++);
        $row['max'] = $dx * ($i) + $price_grade * ($j - 1);

        $sql = "SELECT (FLOOR((g.shop_price - $row[min]) / $dx)) AS sn, COUNT(*) AS goods_num  ".
               " FROM " . $ecs->table('goods') . " AS g ".
               " WHERE ($children OR " . get_extension_goods($children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 '.
               " GROUP BY sn ";

        $price_grade = $db->getAll($sql);
        $price_arr = array();
        foreach ($price_grade as $key=>$val)
        {
            $price_arr[$key]['goods_num'] = $val['goods_num'];
            $price_arr[$key]['start'] = $row['min'] + round($dx * $val['sn']);
            $price_arr[$key]['end'] = $row['min'] + round($dx * ($val['sn'] + 1));
        }
    } else {
    	$result = rpcLang('category.php', 'prices_empty');
        jsonExit("{\"status\":\"$result\"}");
    }
     jsonExit($price_arr);
}
else {
    $result = rpcLang('category.php', 'act_not_exsist');
    jsonExit("{\"status\":\"$result\"}");
}
			 
/**
 * 获得分类的信息
 *
 * @param   integer $cat_id
 *
 * @return  void
 */
function get_cat_info($cat_id)
{
    return $GLOBALS['db']->getRow('SELECT cat_name, keywords, cat_desc, style, grade, filter_attr, parent_id FROM ' . $GLOBALS['ecs']->table('category') .
        " WHERE cat_id = '$cat_id'");
}

/**
 * 获得分类下的商品总数
 *
 * @access  public
 * @param   string     $cat_id
 * @return  integer
 */
function get_cagtegory_goods_count($children, $brand = 0, $min = 0, $max = 0, $ext='')
{
    $where  = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';

    if ($brand > 0)
    {
        $where .=  " AND g.brand_id = $brand ";
    }

    if ($min > 0)
    {
        $where .= " AND g.shop_price >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND g.shop_price <= $max ";
    }

    /* 返回商品总数 */
    return $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('goods') . " AS g WHERE $where $ext");
}
			
/**
 * 获得分类下的商品
 *
 * @access  public
 * @param   string  $children
 * @return  array
 */
function category_get_goods($children, $brand, $min, $max, $ext, $size, $page, $sort, $order)
{
    $display = isset($GLOBALS['display']) ? $GLOBALS['display'] : '';
    $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ".
            "g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';

    if ($brand > 0)
    {
        $where .=  "AND g.brand_id=$brand ";
    }

    if ($min > 0)
    {
        $where .= " AND g.shop_price >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND g.shop_price <= $max ";
    }

    /* 获得商品列表 */
    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " .
                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
            "WHERE $where $ext ORDER BY $sort $order";
   
    $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        }
        else
        {
            $promote_price = 0;
        }

        /* 处理商品水印图片 */
        $watermark_img = '';

        if ($promote_price != 0)
        {
            $watermark_img = "watermark_promote_small";
        }
        elseif ($row['is_new'] != 0)
        {
            $watermark_img = "watermark_new_small";
        }
        elseif ($row['is_best'] != 0)
        {
            $watermark_img = "watermark_best_small";
        }
        elseif ($row['is_hot'] != 0)
        {
            $watermark_img = 'watermark_hot_small';
        }

        if ($watermark_img != '')
        {
            $arr[$row['goods_id']]['watermark_img'] =  $watermark_img;
        }

        $arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
        if($display == 'grid')
        {
            $arr[$row['goods_id']]['goods_name']       = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        }
        else
        {
            $arr[$row['goods_id']]['goods_name']       = $row['goods_name'];
        }
       // $arr[$row['goods_id']]['name']             = $row['goods_name'];
        $arr[$row['goods_id']]['goods_brief']      = $row['goods_brief'];
        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_name'],$row['goods_name_style']);
        $arr[$row['goods_id']]['market_price']     = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
        $arr[$row['goods_id']]['type']             = $row['goods_type'];
        $arr[$row['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';
        $arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']        = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['url']              = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
    }

    return $arr;
}
/**
 * 取得最近的上级分类的grade值
 *
 * @access  public
 * @param   int     $cat_id    //当前的cat_id
 *
 * @return int
 */
function get_parent_grade($cat_id)
{
    static $res = NULL;

    if ($res === NULL)
    {
        $sql = "SELECT parent_id, cat_id, grade ".
               " FROM " . $GLOBALS['ecs']->table('category');
        $res = $GLOBALS['db']->getAll($sql);
    }

    if (!$res)
    {
        return 0;
    }

    $parent_arr = array();
    $grade_arr = array();

    foreach ($res as $val)
    {
        $parent_arr[$val['cat_id']] = $val['parent_id'];
        $grade_arr[$val['cat_id']] = $val['grade'];
    }

    while ($parent_arr[$cat_id] >0 && $grade_arr[$cat_id] == 0)
    {
        $cat_id = $parent_arr[$cat_id];
       
    }

    return $grade_arr[$cat_id];

}

//计算树形分类数量.
function categoriesCount($arr, $field = 'cat_id') {
    if(!is_array($arr)) return 0;
    $sum = 0;
    foreach ($arr as $one) {
        $sum++;
        if (is_array($one) && is_array($one[$field])) {
            $sum += categoriesCount($one[$field], $field);
        }
    }
    return $sum;
}

//平铺树形分类列表.
function arrayFlat($arr, &$result, $field = 'cat_id') {
    if (!is_array($arr)) return array();

    foreach ($arr as $one) {
        if (!is_array($one)) continue;
        $tem = $one;
        unset($tem[$field]);
        array_push($result, $tem);
        if (is_array($one[$field])) {
            arrayFlat($one[$field], $result, $field);
        }
    }
}
