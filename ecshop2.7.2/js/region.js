/* $Id : region.js 4865 2007-01-31 14:04:10Z paulgao $ */

var region = new Object();

region.isAdmin = false;

region.loadRegions = function(parent, type, target)
{
  Ajax.call(region.getFileName(), 'type=' + type + '&target=' + target + "&parent=" + parent , region.response, "GET", "JSON");
}

/* *
 * 载入指定的国家下所有的省份
 *
 * @country integer     国家的编号
 * @selName string      列表框的名称
 */
region.loadProvinces = function(country, selName)
{
  var objName = (typeof selName == "undefined") ? "selProvinces" : selName;

  region.loadRegions(country, 1, objName);
}

/* *
 * 载入指定的省份下所有的城市
 *
 * @province    integer 省份的编号
 * @selName     string  列表框的名称
 */
region.loadCities = function(province, selName)
{
  var objName = (typeof selName == "undefined") ? "selCities" : selName;

  region.loadRegions(province, 2, objName);
}

/* *
 * 载入指定的城市下的区 / 县
 *
 * @city    integer     城市的编号
 * @selName string      列表框的名称
 */
region.loadDistricts = function(city, selName)
{
  var objName = (typeof selName == "undefined") ? "selDistricts" : selName;

  region.loadRegions(city, 3, objName);
}

/* *
 * 载入指定的区 / 县下的门店
 *
 * @city    integer     城市的编号
 * @selName string      列表框的名称
 */
function get_Store(){
	//alert("111111")
	var district_id=document.getElementById("selDistricts").value;
	//alert(district_id);
	if(district_id==0||district_id==""){
		//alert("123")
	}else{
		Ajax.call(region.getFileName(), 'style=' + 1 + '&district_id=' + district_id , store_response, "GET", "JSON");
	}
}

function get_Store2(districts, store){
	//alert("111111"+store)
	var district_id=document.getElementById(districts).value;
	//alert(district_id);
	if(district_id==0||district_id==""){
		//alert("123")
	}else{
		Ajax.call(region.getFileName(), 'storeOption=' + store + '&style=' + 1 + '&district_id=' + district_id , store_response2, "GET", "JSON");
	}
}

function store_response(result){
	//alert("22222222222*"+result.stores.length)
	//alert(result)
	document.getElementById("u_store").length = 0;
	var opt = document.createElement("OPTION");
    opt.value = 0;
    opt.text  = '请选择账号绑定门店';
    document.getElementById("u_store").options.add(opt);
	 if (result.stores)
	 {
	    for (i = 0; i < result.stores.length; i ++ )
	    {
	      var opt = document.createElement("OPTION");
	      opt.value = result.stores[i].store_id;
	      opt.text  = result.stores[i].store_remark;
	      document.getElementById("u_store").options.add(opt);
	    }
	  }
}

function store_response2(result){
	//alert("22222222222*"+result.stores.length)
	//alert(result)
	//alert(result.storeOption)
	document.getElementById(result.storeOption).length = 0;
	var opt = document.createElement("OPTION");
    opt.value = 0;
    opt.text  = '请选择账号绑定门店';
    document.getElementById(result.storeOption).options.add(opt);
	 if (result.stores)
	 {
	    for (i = 0; i < result.stores.length; i ++ )
	    {
	      var opt = document.createElement("OPTION");
	      opt.value = result.stores[i].store_id;
	      opt.text  = result.stores[i].store_remark;
	      document.getElementById(result.storeOption).options.add(opt);
	    }
	  }
}


/* *
 * 处理下拉列表改变的函数
 *
 * @obj     object  下拉列表
 * @type    integer 类型
 * @selName string  目标列表框的名称
 */
region.changed = function(obj, type, selName)
{
  var parent = obj.options[obj.selectedIndex].value;

  region.loadRegions(parent, type, selName);
}

region.response = function(result, text_result)
{
//	document.getElementById("u_store").length = 0;
//	var opt = document.createElement("OPTION");
//    opt.value = 0;
//    opt.text  = '请选择账号绑定门店';
//    document.getElementById("u_store").options.add(opt);
  var sel = document.getElementById(result.target);

  sel.length = 1;
  sel.selectedIndex = 0;
//  sel.style.display = (result.regions.length == 0 && ! region.isAdmin && result.type + 0 == 3) ? "none" : '';

  if (document.all)
  {
    sel.fireEvent("onchange");
  }
  else
  {
    var evt = document.createEvent("HTMLEvents");
    evt.initEvent('change', true, true);
    sel.dispatchEvent(evt);
  }

  if (result.regions)
  {
    for (i = 0; i < result.regions.length; i ++ )
    {
      var opt = document.createElement("OPTION");
      opt.value = result.regions[i].region_id;
      opt.text  = result.regions[i].region_name;

      sel.options.add(opt);
    }
  }
}

region.getFileName = function()
{
  if (region.isAdmin)
  {
    return "../region.php";
  }
  else
  {
    return "region.php";
  }
}
