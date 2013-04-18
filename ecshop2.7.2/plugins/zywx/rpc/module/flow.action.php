<?php
/**
 * 购物车接口
 */
require(RPC_ROOT . 'includes/lib_order.php');
require(RPC_ROOT . 'includes/modules/flow.model.php');
class FlowAction {
	public function index () {
		$this->cart();
	}
	//购物车列表
	public function cart () {
		/* 标记购物流程为普通商品 */
	    $_SESSION['flow_type'] = CART_GENERAL_GOODS;
		/*
		#如果是一步购物，跳到结算中心
	    if ($_CFG['one_step_buy'] == '1')
	    {
	        ecs_header("Location: flow.php?step=checkout\n");
	        exit;
	    }
		*/
	    #取得商品列表，计算合计 */
	    $cart_goods = get_cart_goods();
		if(!$cart_goods) {
	        $msg = rpcLang('flow.php', 'cartlist_empty');      
	        jsonExit("{\"status\":\"$msg\",\"code\":\"1\"}");
	    } 
		/*
	    #显示收藏夹内的商品
	    if ($_SESSION['user_id'] > 0)
	    {
	        require_once(ROOT_PATH . 'includes/lib_clips.php');
	        $collection_goods = get_collection_goods($_SESSION['user_id']);
	        $smarty->assign('collection_goods', $collection_goods);
	    }
		*/
	
	    #取得优惠活动 
	    //$favourable_list = favourable_list($_SESSION['user_rank']);    
	    //usort($favourable_list, 'cmp_favourable');
	
	    #计算折扣
	    $discount = compute_discount();
		/*
	    #增加是否在购物车里显示商品图
	    $smarty->assign('show_goods_thumb', $GLOBALS['_CFG']['show_goods_in_cart']);
	    #增加是否在购物车里显示商品属性
	    $smarty->assign('show_goods_attribute', $GLOBALS['_CFG']['show_attr_in_cart']);
		*/
	
	    #购物车中商品配件列表，取得购物车中基本件ID
	    $sql = "SELECT goods_id " ."FROM " . $GLOBALS['ecs']->table('cart') ." WHERE session_id = '" . SESS_ID . "' " .
	           "AND rec_type = '" . CART_GENERAL_GOODS . "' AND is_gift = 0 AND extension_code <> 'package_buy' " .
	           "AND parent_id = 0 ";
	    
		$parent_list   = $GLOBALS['db']->getCol($sql);
	    $fittings_list = get_goods_fittings($parent_list);
	
		$cart_list = array(
			'cart_goods' => $cart_goods,
			//'favourable_list' => $favourable_list,		
			'discount' => $discount,	
			'fittings_list' => $fittings_list
		);
	    //print_r($cart_list);die;
		jsonExit($cart_list);
	}
	//添加到购物车
	public function add_to_cart () {
		if (!empty($_REQUEST['goods_id']))
	    {
	        $goods_id = intval($_REQUEST['goods_id']);
	        
	        if($goods_id<=0){
	                $error['status'] = rpcLang("goods.php","parme_failure2");
	                $error['code'] = '1';
	                jsonExit($error);	               
	        }
	    }
	
	//    $result = array('error' => 0, 'message' => '', 'content' => '', 'goods_id' => '');
	//    $json  = new JSON;
	//
	//    if (empty($_POST['goods']))
	//    {
	//        $result['error'] = 1;
	//        die($json->encode($result));
	//    }
	//
	//    $goods = $json->decode($_POST['goods']);
	
	    /* 检查：如果商品有规格，而post的数据没有规格，把商品的规格属性通过JSON传到前台 */
	    if (empty($_REQUEST['spec_str']))
	    {
	        $spec_arr = array();
	        
	    }else{
	        $spec_str = trim($_REQUEST['spec_str']);
	        $spec_arr = split(",", $spec_str);
	    }
	
	//    /* 更新：如果是一步购物，先清空购物车 */
	//    if ($_CFG['one_step_buy'] == '1')
	//    {
	//        clear_cart();
	//    }
	
	    if(empty($_REQUEST['parent_id'])){
	            $parent_id = 0;
	    }else{
	            $parent_id = intval($_REQUEST['parent_id']);
	    }
	    
	    
	    /* 检查：商品数量是否合法 */
	    if (empty ($_REQUEST['buy_number'])||intval($_REQUEST['buy_number']) <= 0)
	    {
	        $buy_number = 1;
	    }
	    /* 更新：购物车 */
	    else
	    {
	        $buy_number = intval($_REQUEST['buy_number']);
	        // 更新：添加到购物车
	        
	        if (zy_addto_cart($goods_id, $buy_number, $spec_arr, $parent_id))
	        {
	                $msg['status'] = rpcLang("goods.php","add_cart_success");
	                $msg['code'] = '0';
	        }
	        else
	        {
	                $msg['status'] = rpcLang("goods.php","add_cart_failure");
	                $msg['code'] = '1';
	        }
	        jsonExit($msg);	        
	    }   
	}
	//删除购车中的商品 
	public function drop_cart_goods () {
		$rec_id = intval($_REQUEST['id']);
	    $msg = zy_flow_drop_cart_goods($rec_id);
	    if($msg){
	        jsonExit("1");
	    }else{
	        jsonExit("0");
	    }
	}
	//删除闪购中删除商品
	public function drop_fast_shopping(){
		$rec_id = intval($_REQUEST['id']);
		$msg = zy_fast_shopping($rec_id);
		if($msg){
			jsonExit("1");
		}else{
			jsonExit("0");
		}
	}
	//更新购物车信息
	public function update_cart () {
		$param = $_REQUEST['paramStr'];
	    if(!empty($param)){
	        $param = trim($param);
	        $param = stripcslashes($param);
	        $param = json_decode($param);
	        $arr = json_to_array($param);
	        $msg = zy_flow_update_cart($arr);
	        jsonExit("{\"status\":\"$msg\"}");
	    }else{
	        $msg = rpcLang('flow.php', 'wrong_param');
	        jsonExit("{\"status\":\"$msg\"}");
	    }
	}
	//结算
	public function checkout () {
		global $ecs,$db,$_CFG;
		/*------------------------------------------------------ */
	    //-- 订单确认
	    /*------------------------------------------------------ */
	
	    #取得购物类型
	    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;
		
		/*
	    #团购标志
	    if ($flow_type == CART_GROUP_BUY_GOODS)
	    {
	        $smarty->assign('is_group_buy', 1);
	    }
	    #积分兑换商品
	    elseif ($flow_type == CART_EXCHANGE_GOODS)
	    {
	        $smarty->assign('is_exchange_goods', 1);
	    }
	    else
	    {
		*/
		#正常购物流程  清空其他购物流程情况
		$_SESSION['flow_order']['extension_code'] = '';
		/*}*/
	
	    /* 检查购物车中是否有商品 */
	    $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') ." WHERE session_id = '" . SESS_ID . "' " .
	           "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";
	
	    if ($db->getOne($sql) == 0)
	    {
			$msg = rpcLang('flow.php', 'no_goods_in_cart');
			jsonExit("{\"status\":\"$msg\"}");
	    }
	
	    /*
	     * 检查用户是否已经登录
	     * 如果用户已经登录了则检查是否有默认的收货地址
	     * 如果没有登录则跳转到登录和注册页面
	     */
	    if (empty($_SESSION['direct_shopping']) && $_SESSION['user_id'] == 0)
	    {
			$msg = rpcLang('user.php', 'nologin');
			jsonExit("{\"status\":\"$msg\"}");
	    }
	
	    $consignee = get_consignee($_SESSION['user_id']);
	
	    #检查收货人信息是否完整
	    if (!check_consignee_info($consignee, $flow_type))
	    {
			$msg = rpcLang('flow.php', 'user_address_not_full');
			jsonExit("{\"status\":\"$msg\"}");
	    }
	
	    //$_SESSION['flow_consignee'] = $consignee;
	    
	    #对商品信息赋值
	    $cart_goods = cart_goods($flow_type); // 取得商品列表，计算合计
	
		/*
	    $smarty->assign('goods_list', $cart_goods);
	    #对是否允许修改购物车赋值
	    if ($flow_type != CART_GENERAL_GOODS || $_CFG['one_step_buy'] == '1')
	    {
	        $smarty->assign('allow_edit_cart', 0);
	    }
	    else
	    {
	        $smarty->assign('allow_edit_cart', 1);
	    }
	    
	    #取得购物流程设置
	    $smarty->assign('config', $_CFG);
		*/
	
	    /*
	     * 取得订单信息
	     */
	    $order = flow_order_info();
	
	    /* 计算折扣 */
	    if ($flow_type != CART_EXCHANGE_GOODS && $flow_type != CART_GROUP_BUY_GOODS)
	    {
	        $discount = compute_discount();
			
	    }
	
	    /*
	     * 计算订单的费用
	     */
	    $total = order_fee($order, $cart_goods, $consignee);
		
	    #取得配送列表
	    $region            = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
		#快递方式集合
		$shipping_list     = available_shipping_list($region);
		#购物车重量
	    $cart_weight_price = cart_weight_price($flow_type);
	    $insure_disabled   = true;
	    $cod_disabled      = true;
	
	    #查看购物车中是否全为免运费商品，若是则把运费赋为零s
	    $sql = 'SELECT count(*) FROM ' . $ecs->table('cart') . " WHERE `session_id` = '" . SESS_ID. "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
	    $shipping_count = $db->getOne($sql);
	    foreach ($shipping_list AS $key => $val)
	    {
	        $shipping_cfg = unserialize_config($val['configure']);
	        $shipping_fee = ($shipping_count == 0 AND $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val['configure']),
	        $cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);
	
	        $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
	        $shipping_list[$key]['shipping_fee']        = $shipping_fee;
	        $shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
	        $shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
	        price_format($val['insure'], false) : $val['insure'];
	
	        #当前的配送方式是否支持保价
	        if ($val['shipping_id'] == $order['shipping_id'])
	        {
	            $insure_disabled = ($val['insure'] == 0);
	            $cod_disabled    = ($val['support_cod'] == 0);
	        }
	    }
		#取得支付列表
	    if ($order['shipping_id'] == 0)
	    {
	        $cod        = true;
	        $cod_fee    = 0;
	    }
	    else
	    {
	        $shipping = shipping_info($order['shipping_id']);
	        $cod = $shipping['support_cod'];
	
	        if ($cod)
	        {
	            #如果是团购，且保证金大于0，不能使用货到付款
	            if ($flow_type == CART_GROUP_BUY_GOODS)
	            {
	                $group_buy_id = $_SESSION['extension_id'];
	                if ($group_buy_id <= 0)
	                {
	                    show_message('error group_buy_id');
	                }
	                $group_buy = group_buy_info($group_buy_id);
	                if (empty($group_buy))
	                {
	                    show_message('group buy not exists: ' . $group_buy_id);
	                }
	
	                if ($group_buy['deposit'] > 0)
	                {
	                    $cod = false;
	                    $cod_fee = 0;
	
	                    #赋值保证金
	                    $smarty->assign('gb_deposit', $group_buy['deposit']);
	                }
	            }
	
	            if ($cod)
	            {
	                $shipping_area_info = shipping_area_info($order['shipping_id'], $region);
	                $cod_fee            = $shipping_area_info['pay_fee'];
	            }
	        }
	        else
	        {
	            $cod_fee = 0;
	        }
	    }
	    
	
	    # 给货到付款的手续费加<span id>，以便改变配送的时候动态显示
	    $payment_list = available_payment_list(1, $cod_fee);
	    if(isset($payment_list))
	    {
	        foreach ($payment_list as $key => $payment)
	        {
	            if ($payment['is_cod'] == '1')
	            {
	                $payment_list[$key]['format_pay_fee'] = '<span id="ECS_CODFEE">' . $payment['format_pay_fee'] . '</span>';
	            }
	            #如果有易宝神州行支付 如果订单金额大于300 则不显示
	            if ($payment['pay_code'] == 'yeepayszx' && $total['amount'] > 300)
	            {
	                unset($payment_list[$key]);
	            }
	            #如果有余额支付
	            if ($payment['pay_code'] == 'balance')
	            {
	                #如果未登录，不显示
	                if ($_SESSION['user_id'] == 0)
	                {
	                    unset($payment_list[$key]);
	                }
	                else
	                {
	                    if ($_SESSION['flow_order']['pay_id'] == $payment['pay_id'])
	                    {
	                        $smarty->assign('disable_surplus', 1);
	                    }
	                }
	            }
	        }
	    }
	
		$pack_list = array();
		$card_list = array();
	    /* 取得包装与贺卡 */
	    if ($total['real_goods_count'] > 0)
	    {
	        #只有有实体商品,才要判断包装和贺卡
	        if (!isset($_CFG['use_package']) || $_CFG['use_package'] == '1')
	        {
				#如果使用包装，取得包装列表及用户选择的包装
				$pack_list = pack_list();
	        }
	
	        #如果使用贺卡，取得贺卡列表及用户选择的贺卡
	        if (!isset($_CFG['use_card']) || $_CFG['use_card'] == '1')
	        {
				$card_list = card_list();
			}
	    }
	
	    /* 
		$user_info = user_info($_SESSION['user_id']);
		#如果使用余额，取得用户余额
	    if ((!isset($_CFG['use_surplus']) || $_CFG['use_surplus'] == '1') && $_SESSION['user_id'] > 0  && $user_info['user_money'] > 0)
	    {
	        // 能使用余额
	        $smarty->assign('allow_use_surplus', 1);
	        $smarty->assign('your_surplus', $user_info['user_money']);
	    }
		
		#如果使用积分，取得用户可用积分及本订单最多可以使用的积分
	    if ((!isset($_CFG['use_integral']) || $_CFG['use_integral'] == '1')
	        && $_SESSION['user_id'] > 0
	        && $user_info['pay_points'] > 0
	        && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS))
	    {
	        // 能使用积分
	        $smarty->assign('allow_use_integral', 1);
	        $smarty->assign('order_max_integral', flow_available_points());  // 可用积分
	        $smarty->assign('your_integral',      $user_info['pay_points']); // 用户积分
	    }*/
		
	    #如果使用红包，取得用户可以使用的红包及用户选择的红包
	    if ((!isset($_CFG['use_bonus']) || $_CFG['use_bonus'] == '1') && ($flow_type != CART_GROUP_BUY_GOODS && $flow_type != CART_EXCHANGE_GOODS))
	    {
	        #取得用户可用红包
	        $user_bonus = zy_user_bonus($_SESSION['user_id'], $total['goods_price']);
	        if (!empty($user_bonus))
	        {
	            foreach ($user_bonus AS $key => $val)
	            {
	                $user_bonus[$key]['bonus_money_formated'] = price_format($val['type_money'], false);
	            }
	        }
	        #能使用红包
	        //$smarty->assign('allow_use_bonus', 1);
	    }
		/*
	    # 如果使用缺货处理，取得缺货处理列表
	    if (!isset($_CFG['use_how_oos']) || $_CFG['use_how_oos'] == '1')
	    {
	        if (is_array($GLOBALS['_LANG']['oos']) && !empty($GLOBALS['_LANG']['oos']))
	        {
	            $smarty->assign('how_oos_list', $GLOBALS['_LANG']['oos']);
	        }
	    }*/
		
	    #如果能开发票，取得发票内容列表
	    if ((!isset($_CFG['can_invoice']) || $_CFG['can_invoice'] == '1')	&& isset($_CFG['invoice_content']) 
			&& trim($_CFG['invoice_content']) != '' && $flow_type != CART_EXCHANGE_GOODS)
	    {
	        $inv_content_list = explode("\n", str_replace("\r", '', $_CFG['invoice_content']));
	       
	        $inv_type_list = array();
	        foreach ($_CFG['invoice_type']['type'] as $key => $type)
	        {
	            if (!empty($type))
	            {
	                $inv_type_list[$type] = $type . ' [' . floatval($_CFG['invoice_type']['rate'][$key]) . '%]';
	            }
	        }
	        $inv_content_list = implode(",", $inv_content_list);
	        $inv_list = array ('inv_content_list'=>$inv_content_list,'inv_type_list'=>$inv_type_list);
	       
	    }
		
	
	    #保存session
	    $_SESSION['flow_order'] = $order;
	   /* $pay_code = rpcLang('flow.php', 'pay_code_name');
		foreach ($payment_list as $value) {
			
			foreach ($value as $k=>$v){
				
			    if (strpos($v, $pay_code) !== false) {
			        $payment_list = $value;
			        $payment_list = preg_replace('/<.*?>|\[.*?\]/', '', $payment_list);
	    			$payment_list = array($payment_list);
			    }
			}
		}*/
	   
		$checkout_order = array('cart_goods'=>$cart_goods,
								'total'=>$total,
								'pack_list'=>$pack_list,
								'card_list'=>$card_list,
								'shipping_list'=>$shipping_list,
								'payment_list'=>$payment_list,
								'inv_list'=>$inv_list,
								'consignee'=>$consignee,);
		if (!empty($user_bonus)) {
	    	$checkout_order ['user_bonus'] = $user_bonus;
	    }
	  // print_r($checkout_order);die;
		jsonExit($checkout_order);
	}
	//生成订单
	public function done () {
		global $ecs,$db,$_CFG;
		include_once(RPC_ROOT.'includes/lib_clips_ec.php');
	    include_once(RPC_ROOT.'includes/lib_payment.php');
	
	    /* 取得购物类型 */
	    $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;
	
	    /* 检查购物车中是否有商品 */
	    $sql = "SELECT COUNT(*) FROM " . $ecs->table('cart') ." WHERE session_id = '" . SESS_ID . "' " .
	           "AND parent_id = 0 AND is_gift = 0 AND rec_type = '$flow_type'";
	    
		if ($db->getOne($sql) == 0)
	    {
			$msg = rpcLang('flow.php', 'no_goods_in_cart');
			jsonExit("{\"status\":\"$msg\"}");
	    }
	
	    /* 检查商品库存,如果使用库存，且下订单时减库存，则减少库存 */
	    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
	    {
	        $cart_goods_stock = get_cart_goods();       
	        $_cart_goods_stock = array();        
	        foreach ($cart_goods_stock['goods_list'] as $value)
	        {
	            $_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
	        }       
	        zy_flow_cart_stock($_cart_goods_stock);        
	        unset($cart_goods_stock, $_cart_goods_stock);
	    }
	
	    /*
	     * 检查用户是否已经登录
	     * 如果用户已经登录了则检查是否有默认的收货地址
	     * 如果没有登录则跳转到登录和注册页面
	     */
	    if (empty($_SESSION['direct_shopping']) && $_SESSION['user_id'] == 0)
	    {
	        /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
	        $msg = rpcLang('user.php', 'nologin');
			jsonExit("{\"status\":\"$msg\"}");
	    }
	
	    $consignee = get_consignee($_SESSION['user_id']);
	
	    /* 检查收货人信息是否完整 */
	    if (!check_consignee_info($consignee, $flow_type))
	    {
	        $msg = rpcLang('flow.php', 'user_address_not_full');
			jsonExit("{\"status\":\"$msg\"}");
	    }
	
	
	    $_GET['how_oos']	  = isset($_GET['how_oos']) ? intval($_GET['how_oos']) : 0;
	    $_GET['card_message'] = isset($_GET['card_message']) ? htmlspecialchars($_GET['card_message']) : '';
	    $_GET['inv_type']     = !empty($_GET['inv_type']) ? htmlspecialchars($_GET['inv_type']) : '';
	    $_GET['inv_payee']    = isset($_GET['inv_payee']) ? htmlspecialchars($_GET['inv_payee']) : '';
	    $_GET['inv_content']  = isset($_GET['inv_content']) ? htmlspecialchars($_GET['inv_content']) : '';
	    $_GET['postscript']   = isset($_GET['postscript']) ? htmlspecialchars($_GET['postscript']) : '';
	
	    $referer = empty($_GET['referer']) ? 'Android':trim($_GET['referer']);
	    $order = array(
	        //'shipping_id'     => intval($_POST['shipping']),
	    	'shipping_id'     => intval($_GET['shipping']),//快递id
	        'pay_id'          => intval($_GET['payment']),//支付方式id
	        'pack_id'         => isset($_GET['pack']) ? intval($_GET['pack']) : 0,//包装
	        'card_id'         => isset($_GET['card']) ? intval($_GET['card']) : 0,//贺卡
	        'card_message'    => trim($_GET['card_message']),
	        'surplus'         => isset($_POST['surplus']) ? floatval($_POST['surplus']) : 0.00,
	        'integral'        => isset($_POST['integral']) ? intval($_POST['integral']) : 0,
	        'bonus_id'        => isset($_GET['bonus']) ? intval($_GET['bonus']) : 0,
	        'need_inv'        => empty($_POST['need_inv']) ? 0 : 1,
	        'postscript'      => trim($_POST['postscript']),
	        'how_oos'         => isset($_LANG['oos'][$_POST['how_oos']]) ? addslashes($_LANG['oos'][$_POST['how_oos']]) : '',
	        'need_insure'     => isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0,
	        'user_id'         => $_SESSION['user_id'],
	        'add_time'        => gmtime(),
	        'order_status'    => OS_UNCONFIRMED,
	        'shipping_status' => SS_UNSHIPPED,
	        'pay_status'      => PS_UNPAYED,
	        'agency_id'       => get_agency_by_regions(array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']))
	        );
		if(EC_CHARSET == 'utf-8'){
			$order['inv_type'] = gbktoutf8($_GET['inv_type']);
			$order['inv_payee'] = gbktoutf8(trim($_GET['inv_payee']));
			$order['inv_content'] = gbktoutf8($_GET['inv_content']);
		}else{
			$order['inv_type'] = utf8togbk($_GET['inv_type']);
			$order['inv_payee'] = utf8togbk(trim($_GET['inv_payee']));
			$order['inv_content'] = utf8togbk($_GET['inv_content']);
		}
	
	    /* 扩展信息 */
	    if (isset($_SESSION['flow_type']) && intval($_SESSION['flow_type']) != CART_GENERAL_GOODS)
	    {
	        $order['extension_code'] = $_SESSION['extension_code'];
	        $order['extension_id'] = $_SESSION['extension_id'];
	    }
	    else
	    {
	        $order['extension_code'] = '';
	        $order['extension_id'] = 0;
	    }
	    $user_id = $_SESSION['user_id'];
		/*
	    #检查积分余额是否合法
	    $user_id = $_SESSION['user_id'];
	    if ($user_id > 0)
	    {
	        $user_info = user_info($user_id);
	
	        $order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);
	        if ($order['surplus'] < 0)
	        {
	            $order['surplus'] = 0;
	        }
	
	        // 查询用户有多少积分
	        $flow_points = flow_available_points();  // 该订单允许使用的积分
	        $user_points = $user_info['pay_points']; // 用户的积分总数
	
	        $order['integral'] = min($order['integral'], $user_points, $flow_points);
	        if ($order['integral'] < 0)
	        {
	            $order['integral'] = 0;
	        }
	    }
	    else
	    {
	        $order['surplus']  = 0;
	        $order['integral'] = 0;
	    }*/
	
	    #检查红包是否存在
	    if ($order['bonus_id'] > 0)
	    {
	        $bonus = bonus_info($order['bonus_id']);
	
	        if (empty($bonus) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart_amount(true, $flow_type))
	        {
	            $order['bonus_id'] = 0;
	        }
	        
	    }
	    elseif (isset($_POST['bonus_sn']))
	    {
	        $bonus_sn = trim($_POST['bonus_sn']);
	        $bonus = bonus_info(0, $bonus_sn);
	        $now = gmtime();
	        if (empty($bonus) || $bonus['user_id'] > 0 || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > cart_amount(true, $flow_type) || $now > $bonus['use_end_date'])
	        {
	        }
	        else
	        {
	            if ($user_id > 0)
	            {
	                $sql = "UPDATE " . $ecs->table('user_bonus') . " SET user_id = '$user_id' WHERE bonus_id = '$bonus[bonus_id]' LIMIT 1";
	                $db->query($sql);
	            }
	            $order['bonus_id'] = $bonus['bonus_id'];
	            $order['bonus_sn'] = $bonus_sn;
	        }
	    }
		
	
	    /* 订单中的商品 */
	    $cart_goods = cart_goods($flow_type);
	
	    if (empty($cart_goods))
	    {
	        $msg = rpcLang('flow.php', 'no_goods_in_cart');
			jsonExit("{\"status\":\"$msg\"}");
	    }
	
	    /* 检查商品总额是否达到最低限购金额 */
	    if ($flow_type == CART_GENERAL_GOODS && cart_amount(true, CART_GENERAL_GOODS) < $_CFG['min_goods_amount'])
	    {
			$msg = rpcLang('flow.php', 'goods_amount_not_enough');
			jsonExit("{\"status\":\"$msg\"}");
	    }
	
	    /* 收货人信息 */
	    foreach ($consignee as $key => $value)
	    {
	        $order[$key] = addslashes($value);
	    }
	
	    /* 订单中的总额 */
	    $total = order_fee($order, $cart_goods, $consignee);
	
	    $order['bonus']        = $total['bonus'];
	    $order['goods_amount'] = $total['goods_price'];
	    $order['discount']     = $total['discount'];
	    $order['surplus']      = $total['surplus'];
	    $order['tax']          = $total['tax'];
	    #购物车中的商品能享受红包支付的总额
	    $discount_amout = compute_discount_amount();
		#红包和积分最多能支付的金额为商品总额
	    $temp_amout = $order['goods_amount'] - $discount_amout;
		if ($temp_amout <= 0)
	    {
	        $order['bonus_id'] = 0;
	    }
	
	    /* 配送方式 */
	    if ($order['shipping_id'] > 0)
	    {
	        $shipping = shipping_info($order['shipping_id']);
	        $order['shipping_name'] = addslashes($shipping['shipping_name']);
	    }
	    $order['shipping_fee'] = $total['shipping_fee'];
	    $order['insure_fee']   = $total['shipping_insure'];
	
	    /* 支付方式 */
	    if ($order['pay_id'] > 0)
	    {
	        $payment = payment_info($order['pay_id']);
	        $order['pay_name'] = addslashes($payment['pay_name']);
	    }
	    $order['pay_fee'] = $total['pay_fee'];
	    $order['cod_fee'] = $total['cod_fee'];
	
	    /* 商品包装 */
	    if ($order['pack_id'] > 0)
	    {
	        $pack               = pack_info($order['pack_id']);
	        $order['pack_name'] = addslashes($pack['pack_name']);
	    }
	    $order['pack_fee'] = $total['pack_fee'];
	
	
	    /* 祝福贺卡 */
	    if ($order['card_id'] > 0)
	    {
	        $card               = card_info($order['card_id']);
	        $order['card_name'] = addslashes($card['card_name']);
	    }
	    $order['card_fee']      = $total['card_fee'];
	
	    $order['order_amount']  = number_format($total['amount'], 2, '.', '');
	
	    /* 如果全部使用余额支付，检查余额是否足够 */
	    if ($payment['pay_code'] == 'balance' && $order['order_amount'] > 0)
	    {
	        if($order['surplus'] >0) //余额支付里如果输入了一个金额
	        {
	            $order['order_amount'] = $order['order_amount'] + $order['surplus'];
	            $order['surplus'] = 0;
	        }
	        if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line']))
	        {
	            show_message($_LANG['balance_not_enough']);
	        }
	        else
	        {
	            $order['surplus'] = $order['order_amount'];
	            $order['order_amount'] = 0;
	        }
	    }
	
	    /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
	    if ($order['order_amount'] <= 0)
	    {
	        $order['order_status'] = OS_CONFIRMED;
	        $order['confirm_time'] = gmtime();
	        $order['pay_status']   = PS_PAYED;
	        $order['pay_time']     = gmtime();
	        $order['order_amount'] = 0;
	    }
	
	    $order['integral_money']   = $total['integral_money'];
	    $order['integral']         = $total['integral'];
	
	    if ($order['extension_code'] == 'exchange_goods')
	    {
	        $order['integral_money']   = 0;
	        $order['integral']         = $total['exchange_integral'];
	    }
	
	    $order['from_ad']          = !empty($_SESSION['from_ad']) ? $_SESSION['from_ad'] : '0';
	    $order['referer']          = !empty($_SESSION['referer']) ? addslashes($_SESSION['referer']) : 'Android';
	
	    /* 记录扩展信息 */
	    if ($flow_type != CART_GENERAL_GOODS)
	    {
	        $order['extension_code'] = $_SESSION['extension_code'];
	        $order['extension_id'] = $_SESSION['extension_id'];
	    }
	
	    $affiliate = unserialize($_CFG['affiliate']);
	    if(isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 1)
	    {
	        //推荐订单分成
	        $parent_id = get_affiliate();
	        if($user_id == $parent_id)
	        {
	            $parent_id = 0;
	        }
	    }
	    elseif(isset($affiliate['on']) && $affiliate['on'] == 1 && $affiliate['config']['separate_by'] == 0)
	    {
	        //推荐注册分成
	        $parent_id = 0;
	    }
	    else
	    {
	        //分成功能关闭
	        $parent_id = 0;
	    }
	    $order['parent_id'] = $parent_id;
	
	    /* 插入订单表 */
	    $error_no = 0;
	    do
	    {
	        $order['order_sn'] = get_order_sn(); //获取新订单号
			
	        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), $order, 'INSERT');
	
	        $error_no = $GLOBALS['db']->errno();
	
	        if ($error_no > 0 && $error_no != 1062)
	        {
	            die($GLOBALS['db']->errorMsg());
	        }
	    }
	    while ($error_no == 1062); //如果是订单号重复则重新提交数据
	
	    $new_order_id = $db->insert_id();
	    $order['order_id'] = $new_order_id;
	
	    /* 插入订单商品 */
	    $sql = "INSERT INTO " . $ecs->table('order_goods') . "( " .
	                "order_id, goods_id, goods_name, goods_sn, goods_number, market_price, ".
	                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id) ".
	            " SELECT '$new_order_id', goods_id, goods_name, goods_sn, goods_number, market_price, ".
	                "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, goods_attr_id".
	            " FROM " .$ecs->table('cart') .
	            " WHERE session_id = '".SESS_ID."' AND rec_type = '$flow_type'";
	    $db->query($sql);
	    /* 修改拍卖活动状态 */
	    if ($order['extension_code']=='auction')
	    {
	        $sql = "UPDATE ". $ecs->table('goods_activity') ." SET is_finished='2' WHERE act_id=".$order['extension_id'];
	        $db->query($sql);
	    }
	
	    /* 处理余额、积分、红包 */
	    if ($order['user_id'] > 0 && $order['surplus'] > 0)
	    {
	        log_account_change($order['user_id'], $order['surplus'] * (-1), 0, 0, 0, sprintf($_LANG['pay_order'], $order['order_sn']));
	    }
	    if ($order['user_id'] > 0 && $order['integral'] > 0)
	    {
	        log_account_change($order['user_id'], 0, 0, 0, $order['integral'] * (-1), sprintf($_LANG['pay_order'], $order['order_sn']));
	    }
	
	    if ($order['bonus_id'] > 0 && $temp_amout > 0)
	    {
	        use_bonus($order['bonus_id'], $new_order_id);
	    }
	
	    /* 如果使用库存，且下订单时减库存，则减少库存 */
	    if ($_CFG['use_storage'] == '1' && $_CFG['stock_dec_time'] == SDT_PLACE)
	    {
	        //change_order_goods_storage($order['order_id'], true, SDT_PLACE);
	    }
	error_log('1',3,'flow.log');
	    /* 给商家发邮件 */
	    /* 增加是否给客服发送邮件选项 */
	    if ($_CFG['send_service_email'] && $_CFG['service_email'] != '')
	    {
	    	error_log('2',3,'flow.log');
	        $tpl = get_mail_template('remind_of_new_order');
			/*
			$smarty->assign('order', $order);
	        $smarty->assign('goods_list', $cart_goods);
	        $smarty->assign('shop_name', $_CFG['shop_name']);
	        $smarty->assign('send_date', date($_CFG['time_format']));
	        $content = $smarty->fetch('str:' . $tpl['template_content']);
	        */
			send_mail($_CFG['shop_name'], $_CFG['service_email'], $tpl['template_subject'], $content, $tpl['is_html']);
	    }
	error_log('2',3,'flow.log');
	    /* 如果需要，发短信 */
	    if ($_CFG['sms_order_placed'] == '1' && $_CFG['sms_shop_mobile'] != '')
	    {
	        include_once(RPC_ROOT.'includes/cls_sms.php');
	        $sms = new sms();
	        $msg = $order['pay_status'] == PS_UNPAYED ?
	            $_LANG['order_placed_sms'] : $_LANG['order_placed_sms'] . '[' . $_LANG['sms_paid'] . ']';
	        $sms->send($_CFG['sms_shop_mobile'], sprintf($msg, $order['consignee'], $order['tel']), 0);
	    }
	error_log('3',3,'flow.log');
	    /* 如果订单金额为0 处理虚拟卡 */
	    if ($order['order_amount'] <= 0)
	    {
	        $sql = " SELECT goods_id, goods_name, goods_number AS num FROM ".$GLOBALS['ecs']->table('cart') .
	               " WHERE is_real = 0 AND extension_code = 'virtual_card'".
	               " AND session_id = '".SESS_ID."' AND rec_type = '$flow_type'";
	
	        $res = $GLOBALS['db']->getAll($sql);
	error_log('4',3,'flow.log');
	        $virtual_goods = array();
	        foreach ($res AS $row)
	        {
	            $virtual_goods['virtual_card'][] = array('goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']);
	        }
	
	        if ($virtual_goods AND $flow_type != CART_GROUP_BUY_GOODS)
	        {
	            /* 虚拟卡发货 */
	            if (virtual_goods_ship($virtual_goods,$msg, $order['order_sn'], true))
	            {
	                /* 如果没有实体商品，修改发货状态，送积分和红包 */
	                $sql = "SELECT COUNT(*)" .
	                        " FROM " . $ecs->table('order_goods') .
	                        " WHERE order_id = '$order[order_id]' " .
	                        " AND is_real = 1";
	                if ($db->getOne($sql) <= 0)
	                {
	                    /* 修改订单状态 */
	                    update_order($order['order_id'], array('shipping_status' => SS_SHIPPED, 'shipping_time' => gmtime()));
	error_log('5',3,'flow.log');
	                    /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
	                    if ($order['user_id'] > 0)
	                    {
	                        /* 取得用户信息 */
	                        $user = user_info($order['user_id']);
	
	                        /* 计算并发放积分 */
	                        $integral = integral_to_give($order);
	                        log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($_LANG['order_gift_integral'], $order['order_sn']));
	error_log('6',3,'flow.log');
	                        /* 发放红包 */
	                        send_order_bonus($order['order_id']);
	                    }
	                }
	            }
	        }
	
	    }
	
	    /* 清空购物车 */
	    clear_cart($flow_type);
	error_log('7',3,'flow.log');
	    /* 清除缓存，否则买了商品，但是前台页面读取缓存，商品数量不减少 */
	    clear_all_files();
	
	    /* 插入支付日志 */
	    //$order['log_id'] = insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);
	
	   error_log('8',3,'flow.log'); 
		
		/*取得支付代码
	
		#取得支付信息，生成支付代码 
	    if ($order['order_amount'] > 0)
	    {
	        $payment = payment_info($order['pay_id']);
	
	        include_once('includes/modules/payment/' . $payment['pay_code'] . '.php');
	
	        $pay_obj    = new $payment['pay_code'];
	
	        $pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));
	
	        $order['pay_desc'] = $payment['pay_desc'];
	
	        $smarty->assign('pay_online', $pay_online);
	    }
		*/
	
	    if(!empty($order['shipping_name']))
	    {
	        $order['shipping_name']=trim(stripcslashes($order['shipping_name']));
	    }
	error_log('9',3,'flow.log');
	    /*
		#订单信息
	    $smarty->assign('order',      $order);
	    $smarty->assign('total',      $total);
	    $smarty->assign('goods_list', $cart_goods);
	    $smarty->assign('order_submit_back', sprintf($_LANG['order_submit_back'], $_LANG['back_home'], $_LANG['goto_user_center'])); // 返回提示
		*/
	    //user_uc_call('add_feed', array($order['order_id'], BUY_GOODS)); //推送feed到uc
	    unset($_SESSION['flow_consignee']); // 清除session中保存的收货人信息
	    unset($_SESSION['flow_order']);
	    unset($_SESSION['direct_shopping']);
	
	
		$order_done = array('order'=>$order,'total'=>$total,'cart_goods'=>$cart_goods);
		//var_dump($order_done);exit;
	error_log('10',3,'flow.log');	
		$order_id = $order['order_id'];
		$price = $order['order_amount'];
		$order = array ('order_id'=>$order_id,'order_number'=>$order['order_sn'],'price'=>$price) ;
		error_log('11',3,'flow.log');
		//print_r($order);die;
		jsonExit($order);
		
	}

}