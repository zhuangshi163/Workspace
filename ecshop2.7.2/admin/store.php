<?php

/**
 * ECSHOP 管理中心店家管理
 * ============================================================================
 * 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liuhui $
 * $Id: store.php 17063 2010-03-25 06:35:46Z liuhui $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

define('STORES_ACTION_LIST', 'recharge_manage,store_recharge,user_recharge,order_view,order_view_finished');
$exc = new exchange($ecs->table('store'), $db, 'store_id', 'store_name');

/*------------------------------------------------------ */
//-- 店家列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    admin_priv('store_manage');
    $smarty->assign('ur_here',      $_LANG['store_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['add_store'], 'href' => 'store.php?act=add'));
    $smarty->assign('full_page',    1);

    $store_list = get_storelist();
    $smarty->assign('store_list',  $store_list['store']);
    $smarty->assign('filter',       $store_list['filter']);
    $smarty->assign('record_count', $store_list['record_count']);
    $smarty->assign('page_count',   $store_list['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($store_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('store_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('store_manage');
    $store_list = get_storelist();
    $smarty->assign('store_list',  $store_list['store']);
    $smarty->assign('filter',       $store_list['filter']);
    $smarty->assign('record_count', $store_list['record_count']);
    $smarty->assign('page_count',   $store_list['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($store_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('store_list.htm'), '',
        array('filter' => $store_list['filter'], 'page_count' => $store_list['page_count']));
}

/*------------------------------------------------------ */
//-- 店员列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'clerk')
{
    admin_priv('store_manage');
	$id = empty($_GET['id']) ? 0 : intval($_GET['id']);
	$store = $db->getRow("SELECT * FROM " . $ecs->table('store').' where store_id='.$id);
	if(empty($store)) sys_msg('参数不正确，请重新操作。');
	
	
    $smarty->assign('ur_here',      '店家：'.$store['store_name'].' （'.$store['store_remark'].'）店员列表');
    $smarty->assign('action_link',  array('text' => '添加店员', 'href' => 'store.php?act=add_clerk&store_id='.$id));
	
	$clerk = $db->getAll("SELECT * FROM " . $ecs->table('store_clerk').' where store_id='.$id);
	foreach ($clerk as $k => $v){
		$clerk[$k]['time_reg'] = local_date('Y-m-d H:m:s',$v['time_reg']);
		$clerk[$k]['time_last'] = local_date('Y-m-d H:m:s',$v['time_last']);
	}
    $smarty->assign('clerk',     $clerk);

    assign_query_info();
    $smarty->display('store_clerk.htm');
}

/*------------------------------------------------------ */
//-- 删除店家
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('store_manage');

    $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
	if( $db->getOne("SELECT count(*) FROM " . $ecs->table('order_info').' where store_id='.$id)>0){
		make_json_error('此店家已有销售记录，请先清除相应订单。');
	} 
    $name = $exc->get_name($id);
    $exc->drop($id);
    
    $sql = "UPDATE " . $ecs->table('admin_user') . " SET store_id = 0 WHERE store_id = '" . $id . "'";
    $db->query($sql);

    /* 记日志 */
    admin_log($name, 'remove', 'store');

    /* 清除缓存 */
    clear_cache_files();

    $url = 'store.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 删除店员
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove_clerk')
{
    admin_priv('store_manage');

    $clerk_id = empty($_GET['clerk_id']) ? 0 : intval($_GET['clerk_id']);
	if( $db->getOne("SELECT count(*) FROM " . $ecs->table('order_info').' where clerk_id='.$clerk_id)>0) make_json_error('此店员已有销售记录，请先清除相应订单。');
    $store_id = $db->getOne("SELECT store_id FROM " . $ecs->table('store_clerk').' where clerk_id='.$clerk_id);
	
    $db->query("DELETE FROM " . $ecs->table('store_clerk') . ' WHERE clerk_id='.$clerk_id);

    /* 记日志 */
    admin_log($clerk_id.'(店家：'.$store_id.')', 'remove', 'store_clerk');

    /* 清除缓存 */
    clear_cache_files();
    ecs_header("Location: store.php?act=clerk&id=".$store_id."\n");
    exit;
}

/*------------------------------------------------------ */
//-- 添加、编辑店家
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    admin_priv('store_manage');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'add';
    $smarty->assign('form_action', $is_add ? 'insert' : 'update');

    /* 初始化、取得店家信息 */
    if ($is_add)
    {
        $store = array(
            'store_id'     => 0,
            'store_name'   => '',
            'store_cat'   => 0,
            'store_remark'   => ''
        );
        /* 取得所有管理员，*/
        /* 标注哪些是该供货商的('this')，哪些是空闲的('free')，哪些是别的供货商的('other') */
        /* 排除是办事处的管理员 */
        $sql2 = "SELECT user_id, user_name, CASE
        WHEN store_id = '$id' THEN 'this'
        WHEN store_id = 0 THEN 'free'
        ELSE 'other' END AS type
        FROM " . $ecs->table('admin_user') . "
        WHERE agency_id = 0
        AND action_list <> 'all'";
        $user_store['admin_list'] = $db->getAll($sql2);
        
    }
    else
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
		if ($id<1) sys_msg('invalid param');

        $sql = "SELECT * FROM " . $ecs->table('store') . " WHERE store_id = '$id'";
        $store = $db->getRow($sql);
        if (empty($store)) sys_msg('store does not exist');
        
        /* 取得所有管理员，*/
        /* 标注哪些是该供货商的('this')，哪些是空闲的('free')，哪些是别的供货商的('other') */
        /* 排除是办事处的管理员 */
        $sql2 = "SELECT user_id, user_name, CASE
        WHEN store_id = '$id' THEN 'this'
        WHEN store_id = 0 THEN 'free'
        ELSE 'other' END AS type
        FROM " . $ecs->table('admin_user') . "
        WHERE agency_id = 0
        AND action_list <> 'all'";
        $user_store['admin_list'] = $db->getAll($sql2);
    }
    
    /* 取得国家 */
    $smarty->assign('country_list', get_regions());
    if ($store['country'] > 0)
    {
    	/* 取得省份 */
    	$smarty->assign('province_list', get_regions(1, $store['country']));
    	if ($store['province'] > 0)
    	{
    		/* 取得城市 */
    		$smarty->assign('city_list', get_regions(2, $store['province']));
    		if ($store['city'] > 0)
    		{
    			/* 取得区域 */
    			$smarty->assign('district_list', get_regions(3, $store['city']));
    		}
    	}
    }

    $smarty->assign('store', $store);
    $smarty->assign('user_store', $user_store);
    $smarty->assign('ur_here', $is_add ? $_LANG['add_store'] : $_LANG['edit_store']);
    $smarty->assign('cat_list', cat_list(0, $store['store_cat'],true,1));
    $smarty->assign('action_link', array('href' => 'store.php?act=list', 'text' => $_LANG['store_list']));
    assign_query_info();
    $smarty->display('store_info.htm');
}

/*------------------------------------------------------ */
//-- 提交添加、编辑店家
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    /* 检查权限 */
    admin_priv('store_manage');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'insert';

    $country = !empty($_POST['country']) ? trim($_POST['country']) : 0;
    $province = !empty($_POST['province']) ? trim($_POST['province']) : 0;
    $city = !empty($_POST['city']) ? trim($_POST['city']) : 0;
    $district = !empty($_POST['district']) ? trim($_POST['district']) : 0;
    $address = !empty($_POST['address']) ? trim($_POST['address']) : '';
    $store_remark = !empty($_POST['store_remark']) ? trim($_POST['store_remark']) : '';
    $sql = 'select CONCAT(country.region_name, p.region_name, city.region_name, d.region_name) as complete_address'
    		.' from '.$ecs->table(region).' as country'
    		.' left join '.$ecs->table(region).' as p on p.region_id='.$province
    		.' left join '.$ecs->table(region).' as city on city.region_id='.$city
    		.' left join '.$ecs->table(region).' as d on d.region_id='.$district
    		.' where country.region_id='.$country;
    $addr = $db->getOne($sql);
    $complete_address = $addr.$address.$store_remark;
    /* 提交值 */
    $store = array(
        'store_id'     => empty($_POST['id']) ? 0 : intval($_POST['id']),
        'store_name'   => !empty($_POST['store_name']) ? trim($_POST['store_name']) : '',
		'store_cat'   => empty($_POST['store_cat']) ? 0 : intval($_POST['store_cat']),
		'store_remark'   => $store_remark,
		'address'   => $address,
		'phone'   => !empty($_POST['phone']) ? trim($_POST['phone']) : '',
    	'country'	=> $country,
    	'province'	=> $province,
    	'city'	=> $city,
    	'district'	=> $district,
    	'complete_address'	=> $complete_address
    );
    
	if($store['store_name']=='' || !preg_match("/^[a-zA-Z0-9]+$/",$store['store_name']))sys_msg('用户名填写有误，请用英文大小写字母和数字组合');
    /* 判断名称是否重复 */
    if (!$exc->is_only('store_name', $store['store_name'], $store['store_id']))
    {
        sys_msg($_LANG['store_name_exist']);
    }
	
	$password = !empty($_POST['password']) ? trim($_POST['password']) : '';
	$password_confirm = !empty($_POST['password_confirm']) ? trim($_POST['password_confirm']) : '';
	
	if($is_add && $password=='') sys_msg('请输入店家的登录密码。');
	if($password!=$password_confirm) sys_msg('登录密码两次输入不一致！');
	if($password!='') $store['password'] = md5($password);
	
    /* 保存店家信息 */
    if ($is_add)
    {
        $store['time_reg'] = gmtime();
		$db->autoExecute($ecs->table('store'), $store, 'INSERT');
        $store['store_id'] = $db->insert_id();
        
        if (isset($_POST['admins']))
        {
        	$sql = "UPDATE " . $ecs->table('admin_user') . " SET store_id = '" . $store[store_id] . "', action_list = '" . STORES_ACTION_LIST . "' WHERE user_id " . db_create_in($_POST['admins']);
        	$db->query($sql);
        }
    }
    else
    {
        $db->autoExecute($ecs->table('store'), $store, 'UPDATE', "store_id = '$store[store_id]'");
        /* 清空供货商的管理员 */
        $sql = "UPDATE " . $ecs->table('admin_user') . " SET store_id = 0, action_list = '" . STORES_ACTION_LIST . "' WHERE store_id = '" . $store[store_id]. "'";
        $db->query($sql);
        /* 添加供货商的管理员 */
        if (isset($_POST['admins']))
        {
        	$sql = "UPDATE " . $ecs->table('admin_user') . " SET store_id = '" . $store[store_id] . "', action_list = '" . STORES_ACTION_LIST . "' WHERE user_id " . db_create_in($_POST['admins']);
        	$db->query($sql);
        }
        
    }

    /* 记日志 */
    if ($is_add)
    {
        admin_log($store['store_name'], 'add', 'store');
    }
    else
    {
        admin_log($store['store_name'], 'edit', 'store');
    }

    /* 清除缓存 */
    clear_cache_files();

    /* 提示信息 */
    if ($is_add)
    {
        $links = array(
            array('href' => 'store.php?act=add', 'text' => $_LANG['continue_add_store']),
            array('href' => 'store.php?act=list', 'text' => $_LANG['back_store_list'])
        );
        sys_msg($_LANG['add_store_ok'], 0, $links);
    }
    else
    {
        $links = array(
            array('href' => 'store.php?act=list&' . list_link_postfix(), 'text' => $_LANG['back_store_list'])
        );
        sys_msg($_LANG['edit_store_ok'], 0, $links);
    }
}

/*------------------------------------------------------ */
//-- 添加、编辑店员
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add_clerk' || $_REQUEST['act'] == 'edit_clerk')
{
    /* 检查权限 */
    admin_priv('store_manage');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'add_clerk';
    $smarty->assign('form_action', $is_add ? 'insert_clerk' : 'update_clerk');

    /* 初始化、取得店家信息 */
    if ($is_add)
    {
        $clerk = array(
            'clerk_id'     => 0,
            'store_id'     => empty($_GET['store_id']) ? 0 : intval($_GET['store_id']),
            'clerk_remark'   => ''
        );
    }
    else
    {
        $clerk_id = empty($_GET['clerk_id']) ? 0 : intval($_GET['clerk_id']);
		if ($clerk_id<1) sys_msg('invalid param');
        $sql = "SELECT * FROM " . $ecs->table('store_clerk') . " WHERE clerk_id = '$clerk_id'";
        $clerk = $db->getRow($sql);
        if (empty($clerk)) sys_msg('store clerk does not exist');
    }

	$sql = "SELECT * FROM " . $ecs->table('store') . " WHERE store_id = '".$clerk['store_id']."'";
	$store = $db->getRow($sql);
	if (empty($store)) sys_msg('store does not exist');

    $smarty->assign('store', $store);
    $smarty->assign('clerk', $clerk);
    $smarty->assign('ur_here', '店家：'.$store['store_name'].' （'.$store['store_remark'].'）'.($is_add ? '添加' : '编辑').'店员' );
    $smarty->assign('action_link', array('href' => 'store.php?act=clerk&id='.$clerk['store_id'], 'text' => '返回店家：'.$store['store_name']));
    assign_query_info();
    $smarty->display('store_clerk_info.htm');
}

/*------------------------------------------------------ */
//-- 提交添加、编辑店员
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert_clerk' || $_REQUEST['act'] == 'update_clerk')
{
    /* 检查权限 */
    admin_priv('store_manage');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'insert_clerk';
	$password = !empty($_POST['password']) ? trim($_POST['password']) : '';
	$password_confirm = !empty($_POST['password_confirm']) ? trim($_POST['password_confirm']) : '';
	if($is_add){
		$store_id = empty($_POST['store_id']) ? 0 : intval($_POST['store_id']);
		$clerk = array(
			'store_id'     => $store_id,
			'clerk_remark'   => !empty($_POST['clerk_remark']) ? trim($_POST['clerk_remark']) : '',
			'password'   => md5($password),
			'time_reg'  => gmtime()
		);
		if($password=='') sys_msg('请输入店员的交易密码。');
		if($password!=$password_confirm) sys_msg('交易密码两次输入不一致！');
		if( $db->getOne("SELECT count(*) FROM " . $ecs->table('store_clerk').' where store_id='.$store_id.' and  password=\''.$clerk['password'].'\'')>0) sys_msg('此交易密码已被本店的其他店员使用，请重新输入。');
		$db->autoExecute($ecs->table('store_clerk'), $clerk, 'INSERT');
        $clerk_id = $db->insert_id();
        
	}else{
		$stores_id = empty($_POST['store_id']) ? 0 : intval($_POST['store_id']);
		$clerk = array(
			'clerk_remark'   => !empty($_POST['clerk_remark']) ? trim($_POST['clerk_remark']) : ''
		);
		$clerk_id = empty($_POST['clerk_id']) ? 0 : intval($_POST['clerk_id']);
		$store_id = $db->getOne("SELECT store_id FROM " . $ecs->table('store_clerk').' where clerk_id='.$clerk_id);
		if($password!=''){
			if($password!=$password_confirm) sys_msg('交易密码两次输入不一致！');
			$clerk['password'] = md5($password);
			if( $db->getOne("SELECT count(*) FROM " . $ecs->table('store_clerk').' where store_id='.$store_id.' and clerk_id<>'.$clerk_id.' and  password=\''.$clerk['password'].'\'')>0) sys_msg('此交易密码已被本店的其他店员使用，请重新输入。');
		}
		$db->autoExecute($ecs->table('store_clerk'), $clerk, 'UPDATE', "clerk_id = '$clerk_id'");
	}

    /* 记日志 */
    admin_log($clerk_id.'(店家：'.$store_id.')', $is_add?'add':'edit', 'store_clerk');

    /* 清除缓存 */
    clear_cache_files();

    /* 提示信息 */
    if ($is_add)
    {
        $links = array(
            array('href' => 'store.php?act=add_clerk&store_id='.$store_id, 'text' => '继续添加店员'),
            array('href' => 'store.php?act=clerk&id='.$store_id, 'text' => '返回店员列表')
        );
        sys_msg('店员添加成功！', 0, $links);
    }
    else
    {
        $links = array(
            array('href' => 'store.php?act=clerk&id='.$store_id, 'text' => '返回店员列表')
        );
        sys_msg('店员编辑成功！', 0, $links);
    }
}

elseif ($_REQUEST['act'] == 'toggle_store_restraint')
{
    admin_priv('store_manage');

    $id       = intval($_POST['id']);
    $restraint       = intval($_POST['val']);

    if ($exc->edit("restraint = '$restraint'", $id))
    {
        clear_cache_files();
        make_json_result($restraint);
    }
}

elseif ($_REQUEST['act'] == 'toggle_clerk_restraint')
{
    admin_priv('store_manage');

    $id       = intval($_POST['id']);
    $restraint       = intval($_POST['val']);

    if ($db->autoExecute($ecs->table('store_clerk'), array('restraint' => $restraint), 'UPDATE', "clerk_id = '$id'"))
    {
        clear_cache_files();
        make_json_result($restraint);
    }
}

/**
 * 取得店家列表
 * @return  array
 */
function get_storelist()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 初始化分页参数 */
        $filter = array();
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'store_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        /* 查询记录总数，计算分页数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('store');
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter = page_and_size($filter);

        /* 查询记录 */
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('store') . " ORDER BY $filter[sort_by] $filter[sort_order]";

        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    $arr = array();
    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        $rows['store_cat'] = $rows['store_cat']==0 ? '调用全部商品' : $GLOBALS['db']->getOne("SELECT cat_name FROM " . $GLOBALS['ecs']->table('category').' where cat_id='.$rows['store_cat']);
        $rows['time_reg'] = local_date('Y-m-d H:m:s',$rows['time_reg']);
        $rows['time_last'] = local_date('Y-m-d H:m:s',$rows['time_last']);
		$arr[] = $rows;
    }

    return array('store' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>