<?php 
/**
 * 供货商门店发货模块接口
 * @author jkdun
 *
 */
class SuppliersDelivery{

	/**
	 *  获取发货单列表信息
	 *
	 * @access  public
	 * @param
	 *
	 * @return void
	 */
	public function suppliers_delivery_list()
	{
		$result = get_filter();
		if ($result === false)
		{
			/* 过滤信息 */
			$filter['start_time'] = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ?  local_strtotime($_REQUEST['start_time']) : $_REQUEST['start_time']);
			$filter['end_time'] = empty($_REQUEST['end_time']) ? local_strtotime ( 'now' ) : (strpos($_REQUEST['end_time'], '-') > 0 ?  local_strtotime($_REQUEST['end_time']) : $_REQUEST['end_time']);
			$filter['suppliers_id'] = isset($_REQUEST['suppliers_id']) ? intval($_REQUEST['suppliers_id']) : -1;
			$filter['delivery_sn'] = empty($_REQUEST['delivery_sn']) ? '' : trim($_REQUEST['delivery_sn']);
			$filter['status'] = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : -1;
			$filter['store_id'] = isset($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : -1;
	
			$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
			$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
	
			$where = 'WHERE 1 ';
			if ($filter['start_time'])
			{
				$where .= " AND sd.add_time >= '$filter[start_time]'";
			}
			if ($filter['end_time'])
			{
				$where .= " AND sd.add_time <= '$filter[end_time]'";
			}
			/* 如果不是供货商*/
			if(empty($_SESSION['suppliers_id'])){
				if ($filter['suppliers_id'] >= 0)
				{
					$where .= " AND sd.suppliers_id='".$filter['suppliers_id']."'";
				}
			}else{
				$where .= " AND sd.suppliers_id='".$_SESSION['suppliers_id']."'";
			}
			if($filter['delivery_sn']){
				$where .= " AND sd.delivery_sn LIKE '%".$filter['delivery_sn']."%'";
			}
			if($filter['status'] >= 0){
				$where .= " AND sd.status = '".$filter['status']."'";
			}
			if($filter['store_id'] >= 0){
				$where .= " AND sd.store_id = '".$filter['store_id']."'";
			}
	
			/* 分页大小 */
			$filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);
	
			if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
			{
				$filter['page_size'] = intval($_REQUEST['page_size']);
			}
			elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
			{
				$filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
			}
			else
			{
				$filter['page_size'] = 15;
			}
	
			/* 记录总数 */
			$sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('suppliers_delivery') . " AS sd " . $where;
	
			$filter['record_count']   = $GLOBALS['db']->getOne($sql);
			$filter['page_count']     = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;
	
			/* 查询 */
			$sql = "SELECT sd.delivery_id, sd.delivery_sn, sd.add_time, sd.status, sd.driver_name, sup.suppliers_name,".
					" sd.driver_name, store.store_remark, admin.user_name as action_user" .
					" FROM " . $GLOBALS['ecs']->table('suppliers_delivery') . " AS sd " .
					" LEFT JOIN ".$GLOBALS['ecs']->table('store')."as store on store.store_id=sd.store_id ".
					" LEFT JOIN ".$GLOBALS['ecs']->table('suppliers')." as sup on sup.suppliers_id=sd.suppliers_id ".
					" LEFT JOIN ".$GLOBALS['ecs']->table('admin_user')." as admin on admin.user_id=sd.admin_id ".
					$where .
					" ORDER BY sd.$filter[sort_by] $filter[sort_order] ".
					" LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";
	
			set_filter($filter, $sql);
		}
		else
		{
			$sql    = $result['sql'];
			$filter = $result['filter'];
		}
	
		$row = $GLOBALS['db']->getAll($sql);
	
		/* 格式话数据 */
		foreach ($row AS $key => $value)
		{
			$row[$key]['add_time'] = local_date('Y-m-d H:i:s', $value['add_time']);
			/* 供货商操作权限*/
			if(!empty($_SESSION['suppliers_id'])){
				/* 是否能店家发货*/
				$row[$key]['can_shop_delivery'] = 0;
				/* 是否能删除发货单*/
				$row[$key]['can_remove'] = 1;
				$row[$key]['can_confirm'] = $row[$key]['status']==SDS_UNCONFIRMED? 1:0;
				$row[$key]['can_ship'] = $row[$key]['status']==SDS_CONFIRMED? 1:0;
			}
		}
		$arr = array('delivery_list' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
		return $arr;
	}
	
	/**
	 * 取得门店列表
	 * @return array 门店列表 id => name
	 */
	public function get_store_list()
	{
		$sql = 'SELECT store_id, store_remark FROM ' . $GLOBALS['ecs']->table('store') . " WHERE restraint=0 ORDER BY store_id DESC ";
		$res = $GLOBALS['db']->getAll($sql);
	
		$store_list = array();
		foreach ($res AS $row)
		{
			$store_list[$row['store_id']] = addslashes($row['store_remark']);
		}
	
		return $store_list;
	}
	
	/**
	 * 插入发货单记录,(说明：店家确认收货后，才说明发货完成)
	 * @param int $sort_id	分拣单id号
	 */
	public function innsert_delivery($sort_id)
	{
		if (!isset($sort_id)){
			return false;
		}
		
		/* 查找是否存在需分拣记录订单*/
		$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('suppliers_sort_info').
				" AS si where si.sort_id = $sort_id and si.`status` = 1";
		$data = $GLOBALS['db']->getAll($sql);
		if(empty($data)){
			return false;
		}
		/* 插入发货单记录*/
		mt_srand((double) microtime() * 1000000);
		$delivery_sn = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);;
		$now = local_strtotime('now');
		$sql = "INSERT INTO ".$GLOBALS['ecs']->table('suppliers_delivery')." (admin_id, delivery_sn, ".
				" stock_id, sort_id, suppliers_id, store_id, `status`, store_name, add_time)". 
				" SELECT ".$_SESSION['admin_id'].", '$delivery_sn', stock_id, $sort_id, suppliers_id, store_id, 0, store_name, $now".
				" FROM ecs_suppliers_sort_info AS ssi WHERE ssi.sort_id=$sort_id";
		$GLOBALS['db']->query($sql);
		$delivery_id = $GLOBALS['db']->insert_id();
		/* 插入发货单商品记录*/
		$sql = "INSERT INTO ".$GLOBALS['ecs']->table('suppliers_delivery_goods'). "(delivery_id, goods_id, ".
				" goods_name, goods_specification, need_goods_num, real_goods_num, box_name)". 
				" SELECT $delivery_id, goods_id, goods_name, goods_specification, need_goods_num, ".
				" real_goods_num, box_name FROM ".$GLOBALS['ecs']->table('suppliers_sort_goods')." AS ssg ".
				" WHERE ssg.sort_id=$sort_id";
		$GLOBALS['db']->query($sql);
		return true;
	}
	
	/**
	 * 根据发货单id查找发货单记录
	 * @param int $delivery_id
	 */
	public function get_delivery_by_id($delivery_id){
		if (!isset($delivery_id) || intval($delivery_id)==0){
			return null;
		}
		$sql = "select * from ".$GLOBALS['ecs']->table('suppliers_delivery')." where delivery_id=".$delivery_id;
		$delivery = $GLOBALS['db']->getRow($sql);
		return $delivery;
	}
	
	/**
	 * 根据发货单id号删除其发货单
	 * @param int $delivery_id
	 */
	public function delete_delivery_by_id($delivery_id){
		if (!isset($delivery_id) || intval($delivery_id)==0){
			return false;
		}
		/*删除发货单商品记录*/
		$sql = "DELETE FROM ".$GLOBALS['ecs']->table('suppliers_delivery_goods')." WHERE delivery_id=$delivery_id";
		$GLOBALS['db']->query($sql);
		/*删除发货单记录*/
		$sql = "DELETE FROM ".$GLOBALS['ecs']->table('suppliers_delivery')." WHERE delivery_id=$delivery_id";
		$GLOBALS['db']->query($sql);
		return true;
	}
	
	/**
	 * 根据批量删除其发货单
	 * @param int $delivery_id
	 */
	public function batch_delete_delivery_by_id($delivery_id_array){
		if(!isset($delivery_id_array) || empty($delivery_id_array) || intval($delivery_id_array)==0){
			return false;
		}
		$ids = implode(',', $delivery_id_array);
		/*删除发货单商品记录*/
		$sql = "DELETE FROM ".$GLOBALS['ecs']->table('suppliers_delivery_goods')." WHERE delivery_id IN ($ids)";
		$GLOBALS['db']->query($sql);
		/*删除发货单记录*/
		$sql = "DELETE FROM ".$GLOBALS['ecs']->table('suppliers_delivery')." WHERE delivery_id IN ($ids)";
		$GLOBALS['db']->query($sql);
		return true;
	}
}
?>