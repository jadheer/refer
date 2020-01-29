<?php
class ES_Article  extends ESSource
{
	var $index;
	var $type;
	public function __construct()
	{
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
	
	public function __destruct()
	{	parent::__destruct();	}
	
	public function getArticleById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->article_id;
				
			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				$row						=	(object)$result['_source'];
				$robject					=	new stdClass();
				$robject->article_id    	=	(int)$row->article_id;
				$robject->pub_datetime 		=	(string)$row->pub_datetime;
				$robject->title 			=	(string)$this->common->entityDecode($row->title);
				$robject->image 			=	[	'base_url'		=>	(string)_AWS_URL._ARTICLES_IMAGES_DIR,
													'image_path'	=>	(string)$row->image];
				$robject->body 				=	(string)$this->common->entityDecode($row->body);
				$robject->enable_like 		=	(boolean)(!empty($row->enable_like)?true:false);
				$robject->enable_comment 	=	(boolean)(!empty($row->enable_comment)?true:false);
					
				$robject->comment_count 	=	(int)count($row->comments);
				$robject->like_count 		=	(int)count($row->likes);
				$robject->liked 			=	(boolean)(in_array($object->employee_id,$row->likes));
			endif;
			return !empty($robject)?$robject:new stdClass();
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	
	public function getArticles($object){
		try{
			$params = [];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
				
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]],
																['term' => ['published'=>(int)1]],
																['term' => ['publish_dearml'=>(int)1]],
																['range'=> [ 'pub_datetime' => [ 'lte' => (string)date('Y-m-d H:i:s') ,"format"=> "yyyy-MM-dd HH:mm:ss"]]]]];
			if (!empty($object->query)):
				$query	=	['multi_match'	=>	['query' => (string)trim($object->query), 'fields' => ['title', 'body']]];
				array_push($params['body']['query']['bool']['must'], $query);
			endif;
			$params['body']['sort']	=	[['pub_datetime' => ['order' => 'desc']], ['article_id' => ['order' => 'desc']]];
				
			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results,$object){
		$arr 		=	[];
		if ($results['hits']['total']>0):
			foreach ($results['hits']['hits'] as $article_obj):
				if (!empty($article_obj['_source'])):
					$row						=	(object)$article_obj['_source'];
					$robject					=	new stdClass();
					$robject->article_id    	=	(int)$row->article_id;
					$robject->pub_datetime 		=	(string)$row->pub_datetime;
					$robject->title 			=	(string)$this->common->entityDecode($row->title);
					$robject->image 			=	[	'base_url'		=>	(string)_AWS_URL._ARTICLES_IMAGES_DIR,
														'image_path'	=>	(string)$row->image];
					$robject->body 				=	(string)$this->common->entityDecode($row->body);
					$robject->enable_like 		=	(boolean)(!empty($row->enable_like)?true:false);
					$robject->enable_comment 	=	(boolean)(!empty($row->enable_comment)?true:false);
					$robject->comment_count 	=	(int)count($row->comments);
					$robject->like_count 		=	(int)count($row->likes);
					$robject->liked 			=	(boolean)(in_array($object->employee_id,$row->likes));
				endif;
				array_push($arr, $robject);
			endforeach;
		endif;
		return $arr;
	}
}
?>