function getLodop(oOBJECT,oEMBED){
/**************************
  本函数根据浏览器类型决定采用哪个对象作为控件实例：
  IE系列、IE内核系列的浏览器采用oOBJECT，
  其它浏览器(Firefox系列、Chrome系列、Opera系列、Safari系列等)采用oEMBED。
**************************/
        var strHtml1="<br><font color='#FF00FF'>打印控件未安装!点击这里<a href='install_lodop.exe'>执行安装</a>,安装后请刷新页面或重新进入。</font>";
        var strHtml2="<br><font color='#FF00FF'>打印控件需要升级!点击这里<a href='install_lodop.exe'>执行升级</a>,升级后请重新进入。</font>";
        var strHtml3="<br><br><font color='#FF00FF'>注意：<br>1：如曾安装过Lodop旧版附件npActiveXPLugin,请在【工具】->【附加组件】->【扩展】中先卸它;<br>2：如果浏览器表现出停滞不动等异常，建议关闭其“plugin-container”(网上搜关闭方法)功能;</font>";
        var LODOP=oEMBED;		
	try{		     
	     if (navigator.appVersion.indexOf("MSIE")>=0) LODOP=oOBJECT;

	     if ((LODOP==null)||(typeof(LODOP.VERSION)=="undefined")) {
		 if (navigator.userAgent.indexOf('Firefox')>=0)
  	         document.documentElement.innerHTML=strHtml3+document.documentElement.innerHTML;
		 if (navigator.appVersion.indexOf("MSIE")>=0) document.write(strHtml1); else
		 document.documentElement.innerHTML=strHtml1+document.documentElement.innerHTML;
		 return LODOP; 
	     } else if (LODOP.VERSION<"6.0.5.8") {
		 if (navigator.appVersion.indexOf("MSIE")>=0) document.write(strHtml2); else
		 document.documentElement.innerHTML=strHtml2+document.documentElement.innerHTML; 
		 return LODOP;
	     }
	     //*****如下空白位置适合调用统一功能:*********	     


	     //*******************************************
	     return LODOP; 
	}catch(err){
	     document.documentElement.innerHTML="Error:"+strHtml1+document.documentElement.innerHTML;
	     return LODOP; 
	}
}

        var LODOP; //声明为全局变量
			
	function CreatePrintPage(json) {
		//获取控件对象
			LODOP = getLodop(document.getElementById('LODOP_OB'), document
					.getElementById('LODOP_EM'));
			//初始化 
			LODOP.PRINT_INIT("打印控件功能演示_Lodop功能_名片");
			var hPos = 10;//小票上边距
			var pageWidth = 580;//小票宽度
			var rowHeight = 15; //小票行距
			
			var printTimes=0;
			for (printTimes=0;printTimes<json.print_times;printTimes++){
				LODOP.SET_PRINT_STYLE("Bold",1);
				LODOP.SET_PRINT_STYLE("FontSize",11);
				//添加小票标题文本
				LODOP
						.ADD_PRINT_TEXT(hPos, 12, pageWidth, rowHeight,
								json.title);
								LODOP.SET_PRINT_STYLE("Bold",0);
				LODOP.SET_PRINT_STYLE("FontSize",9);
				//上边距往下移
				hPos += rowHeight*2;
				
				LODOP.ADD_PRINT_TEXT(hPos, 1, pageWidth, rowHeight, "姓名:");
				LODOP.ADD_PRINT_TEXT(hPos, 30, pageWidth, rowHeight, json.name);
				//hPos+=rowHeight; //电话不换行
				LODOP.ADD_PRINT_TEXT(hPos, 80, pageWidth, rowHeight, "电话:");
				LODOP.ADD_PRINT_TEXT(hPos, 110, pageWidth, rowHeight,
						json.phone);
				hPos += rowHeight;
				LODOP.ADD_PRINT_TEXT(hPos, 1, pageWidth, rowHeight, "下单时间:");
				LODOP.ADD_PRINT_TEXT(hPos, 60, pageWidth, rowHeight,
						json.orderTime);
				hPos += rowHeight;
				LODOP.ADD_PRINT_TEXT(hPos, 1, pageWidth, rowHeight, "订单编号:");
				LODOP.ADD_PRINT_TEXT(hPos, 60, pageWidth, rowHeight,
						json.orderNo);
				hPos += rowHeight;
				LODOP.ADD_PRINT_TEXT(hPos, 1, pageWidth, rowHeight, "取货门店:");
				LODOP.ADD_PRINT_TEXT(hPos, 60, pageWidth, rowHeight, json.shop);
				hPos += rowHeight;
				LODOP.ADD_PRINT_TEXT(hPos, 1, pageWidth, rowHeight, "订单来源:");
				LODOP.ADD_PRINT_TEXT(hPos, 60, pageWidth, rowHeight, json.source);
				hPos += rowHeight;
				LODOP.ADD_PRINT_LINE(hPos, 2, hPos, pageWidth, 2, 1);
				hPos += 5;
				LODOP.ADD_PRINT_TEXT(hPos, 1, pageWidth, rowHeight, "商品名称");
				LODOP.ADD_PRINT_TEXT(hPos, 70, pageWidth, rowHeight, "单价");
				LODOP.ADD_PRINT_TEXT(hPos, 110, pageWidth, rowHeight, "数量");
				LODOP.ADD_PRINT_TEXT(hPos, 140, pageWidth, rowHeight, "小计");
				hPos += rowHeight;
				//遍历json的商品数组
				for ( var i = 0; i < json.goodsList.length; i++) {
					if (json.goodsList[i].name.length < 6) {
						LODOP.ADD_PRINT_TEXT(hPos, 1, pageWidth, rowHeight,
								json.goodsList[i].name);
					} else {
						//商品名字过长,其他字段需要换行
						LODOP.ADD_PRINT_TEXT(hPos, 1, pageWidth, rowHeight,
								json.goodsList[i].name);
						hPos += rowHeight;
					}
					LODOP.ADD_PRINT_TEXT(hPos, 70, pageWidth, rowHeight,
							json.goodsList[i].price.toFixed(2));
					LODOP.ADD_PRINT_TEXT(hPos, 115, pageWidth, rowHeight,
							json.goodsList[i].count);
					LODOP.ADD_PRINT_TEXT(hPos, 140, pageWidth, rowHeight,
							json.goodsList[i].total.toFixed(2));
					hPos += rowHeight;
				}
				//商品遍历打印完毕,空一行

				LODOP.ADD_PRINT_LINE(hPos, 2, hPos, pageWidth, 2, 1);
				hPos += 5;
				//合计
				LODOP.ADD_PRINT_TEXT(hPos, 1, pageWidth, rowHeight, "合计:");
				LODOP.ADD_PRINT_TEXT(hPos, 30, pageWidth, rowHeight, "￥"
						+ json.total.toFixed(2));
				LODOP.ADD_PRINT_TEXT(hPos, 80, pageWidth, rowHeight, "余额 ￥"
						+ json.users_money.toFixed(2));

				hPos += rowHeight;
				LODOP.ADD_PRINT_TEXT(hPos, 2, pageWidth, rowHeight,
						(new Date()).toLocaleDateString() + " "
								+ (new Date()).toLocaleTimeString())
				hPos += rowHeight;
				LODOP.ADD_PRINT_TEXT(hPos, 25, pageWidth, rowHeight,
						"谢谢惠顾,欢迎下次光临!");
				if(printTimes!=json.print_times-1){
				hPos += rowHeight*3;
				LODOP.ADD_PRINT_LINE(hPos, 2, hPos, pageWidth, 4, 20);
				hPos += rowHeight*2;
				
				}
			}
			
			//初始化打印页的规格
			LODOP.SET_PRINT_PAGESIZE(3, pageWidth,80, "健康盾网购机订单");
		
	};	

		/**
		* 样例函数,服务器确认订单后执行
		*/
		//json 创建模拟服务器响应的订单信息对象
/*
		var json = {"title":"健康盾网购机订单信息", "name":"张三", "phone": "138123456789", "orderTime": "2012-10-11 15:30:15", 
		"orderNo": "20122157481315", "shop":"好客连锁", "total":64.50,"totalCount":6,
		"goodsList":[
		{"name":"菜心(无公害食品)", "price":5.00, "count":2, "total":10.08}, 
		{"name":"菜心(无公害视频)", "price":5.00, "count":2, "total":10.02}, 
		{"name":"旺菜", "price":4.50, "count":1, "total":4.50},
		{"name":"黄心番薯(有机食品)", "price":4.50, "count":1, "total":4.50}
		]
		}
*/	
	

			//创建小票打印页
			CreatePrintPage(json);
			//开始打印
			LODOP.PRINT();
		