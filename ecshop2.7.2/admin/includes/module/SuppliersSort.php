<?php 
/**
 * 供货商门店分拣模块接口
 * @author jkdun
 *
 */
class SuppliersSort{

	/**
	 * 查找门店分拣列表
	 * @param int $stockId 备货单ID
	 * @return array $sort_list
	 */
	public function findSortByStockId($stockId){

		$sql = "SELECT si.stock_id,si.sort_id,si.sort_sn,si.store_id,si.store_name,si.status FROM "
				.$GLOBALS['ecs']->table('suppliers_sort_info')." AS si where si.stock_id = $stockId";
		$sort_list = $GLOBALS['db']->getAll($sql);
		return $sort_list;
	}
	
	/**
	 * 获取某个分拣单中商品详细信息
	 *
	 * @param int $sort_id
	 * @return array 
	 */
	public function getCheckBySortId($sort_id){
		$sql = "SELECT sg.*,si.status,si.store_name FROM "
				.$GLOBALS['ecs']->table('suppliers_sort_goods')." as sg left join "
				.$GLOBALS['ecs']->table('suppliers_sort_info')." as si on sg.sort_id = si.sort_id where si.sort_id=$sort_id";
		$sort_list = $GLOBALS['db']->getAll($sql);
		return $sort_list;
	}
	
	/**
	 * 插入分拣记录,(说明：分箱核对后，才说明分拣完成)
	 */
	public function insertSort($stockId){
		if (!isset($stockId)){
			return false;
		}
		
		//查找是否存在需分拣记录订单
		$sql = "SELECT si.store_id,si.suppliers_id FROM "
				.$GLOBALS['ecs']->table('split_order_info')." AS si where si.stock_id = $stockId and si.order_status = 1";
		$data = $GLOBALS['db']->getAll($sql);
		if(empty($data)){
			return false;
		}
		
		//分拣基本信息表
		$sql = "SELECT si.store_id,si.suppliers_id,s.store_remark FROM "
				.$GLOBALS['ecs']->table('split_order_info')." AS si left join "
						.$GLOBALS['ecs']->table('store')." AS s ON si.store_id = s.store_id where si.stock_id = $stockId and si.order_status = 1 group by s.store_id order by null";
		$data = $GLOBALS['db']->getAll($sql);
		//print_r($sort_list);die;
	    $sql = "INSERT INTO ".$GLOBALS['ecs']->table('suppliers_sort_info')."(sort_sn,stock_id,store_id,suppliers_id,store_name,status) VALUES ";  
	    foreach( $data as $row) {
	    	mt_srand((double) microtime() * 1000000);
	    	$sort_sn = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
	        $sql .= ' ("'.$sort_sn.'","'.$stockId.'","' . implode($row, '","') . '","0"),';
	        $sort_no[]= $sort_sn;
	    }
	    $sql = substr($sql, 0, -1);  
    	//echo $sql;
		//print_r(implode(',', $data[0]));
		$GLOBALS['db']->query($sql);
		$data = $GLOBALS['db']->getAll("select sort_id,store_id from ".$GLOBALS['ecs']->table('suppliers_sort_info')." where sort_sn in (".implode($sort_no, ",").")");
		foreach ($data as $v){
			$sort_store[$v['store_id']] = $v['sort_id'];	
		}
		//分拣商品信息表
		//$sort_id = $GLOBALS['db']->insert_id();
		$sql = "SELECT sg.goods_id,sg.goods_name,sg.goods_specification,SUM(sg.goods_number)as gTotal,si.store_id FROM "
				.$GLOBALS['ecs']->table('split_order_goods')." AS sg left join "
				.$GLOBALS['ecs']->table('split_order_info')." AS si ON sg.order_id = si.order_id where si.stock_id = $stockId and si.order_status = 1 group by si.store_id,sg.goods_id order by null";
		$data = $GLOBALS['db']->getAll($sql);
		$sql = "INSERT INTO ".$GLOBALS['ecs']->table('suppliers_sort_goods')
		." (`sort_id` ,`goods_id` ,`goods_name` ,`goods_specification` ,`need_goods_num`)VALUES ";
		//print_r($data);die;
	    foreach( $data as $row) {
	    	mt_srand((double) microtime() * 1000000);
	    	$sort_sn = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
	        $sql .= ' ("'.$sort_store[$row['store_id']].'","'.$row['goods_id']. '","'.$row['goods_name'].'","'.$row['goods_specification'].'","'.$row['gTotal'].'"),';

	    }
	    $sql = substr($sql, 0, -1);
	    $GLOBALS['db']->query($sql);
	    
	    //更新订单状态(order_status 5 分拣中)
	    $sql = "UPDATE ".$GLOBALS['ecs']->table('split_order_info')." SET `order_status` = '5' WHERE stock_id =$stockId;";
	    $GLOBALS['db']->query($sql);

	}
	/**
	 * 保存分箱信息
	 * @param unknown_type $data
	 * @return boolean
	 */
	public function saveSortGoods($data){
		foreach ($data as $k => $v){
			$con = '';
			if (isset($v['real_goods_num'])){
				$con = " SET `real_goods_num` = ".$v['real_goods_num'];
				if (isset($v['box_name'])){
					$con .= ", `box_name` = '".$v['box_name']."'";
				}
			}
			
			$sql = "UPDATE ".$GLOBALS['ecs']->table('suppliers_sort_goods')."$con WHERE rec_id =$k;";
			$GLOBALS['db']->query($sql);
		}
		return true;
	}
	
	/**
	 * 更新分拣单状态
	 * @param unknown_type $sortId
	 */
	public function updateSortStatusBySortId($sortId,$status){
		$sql = "UPDATE ".$GLOBALS['ecs']->table('suppliers_sort_info')." SET `status` = $status WHERE sort_id =$sortId;";
		$GLOBALS['db']->query($sql);
	}
}
?>