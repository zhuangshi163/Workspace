<?php
include_once ('includes/modules/article.model.php');
/**
 * 文章接口
 */
class ArticleAction {
	public function index () {
		$this->article_list();
	}
    //文章列表
    public function article_list () {
    	$article_list = index_get_new_articles();
		jsonExit($article_list);
    }
	//文章信息
	public function info () {
		$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$article = array();
		if($article_id > 0)
		{
			$article = get_article_info($article_id);
		}
		jsonExit($article);
	}
	//只查询cat_id=13(店铺公告)作为精品推荐
	public function ShopAnnouncement () {
		$cat_id = 13;
		$related_articles = index_get_new_articles($cat_id);
		//var_dump($related_articles);exit;
		jsonExit($related_articles);
	}
	//精品推荐
	public function recommendGoods () {
		$article_id = isset($_GET['id']) ? intval($_GET['id']) : 14;
		$article = array();
		if($article_id > 0)
		{
			//文章详细信息
			$article = get_article_info($article_id);
			//文章关联产品
			$article_related_goods = article_related_goods($article_id);		
			$articleArr = array('article'=>$article,'article_related_goods'=>$article_related_goods);
		}
		//var_dump($articleArr);exit;
		jsonExit($articleArr);
	}

}