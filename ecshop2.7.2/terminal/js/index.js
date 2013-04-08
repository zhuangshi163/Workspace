var btnInfo = [{"name":"特价", "url":"shop_specials.php"}, 
				{"name":"蔬菜", "url":"shop_category.php?act=default&top_cat_id=200"}, 
				{"name":"水果", "url":"shop_category.php?act=default&top_cat_id=211"},
				{"name":"肉类", "url":"shop_category.php?act=default&top_cat_id=212"},
				{"name":"特产", "url":"shop_category.php?act=default&top_cat_id=213"},
				{"name":"会员", "url":"shop_user.php"},
				{"name":"闪购", "url":"shop_convenient.php"}
				];
window.onload = function (){
	var str = "";
	for(var i=0; i<btnInfo.length; i++){
		str += "<div class='mainbutton' onmousedown='reLoadFrames("+i+")'>"+
					"<div class='contentDiv'>"+
						"<div id='wrap'>"+
							"<div id='subwrap'>"+
								"<div id='content' style=\"vertical-align:middle;line-height:150px;\">"+btnInfo[i].name+"</div>"+
							"</div>"+
						"</div>"+
					"</div>"+
					"<img class='bgStyle' src='img/mainbuttondown.png' style='display:none;' id='layoutBgImgDown"+i+"'/>"+
					"<img id='layoutBgImg"+i+"' class='bgStyle' src='img/mainbutton"+i+".png'>"+
				"</div>";
	}
	document.getElementById("navTd").innerHTML = str;
}

function reLoadFrames(i){
	document.getElementById("mainFrame").src = btnInfo[i].url;
	bgImgClickEffect(i);
}

function bgImgClickEffect(index){
	try{
		for(var i=0; i<btnInfo.length; i++){
			document.getElementById("layoutBgImgDown"+i).style.display = "none";
			document.getElementById("layoutBgImg"+i).style.display = "block";
		}
		document.getElementById("layoutBgImg"+index).style.display = "none";
		document.getElementById("layoutBgImgDown"+index).style.display = "block";
	}catch(e){};
	
}