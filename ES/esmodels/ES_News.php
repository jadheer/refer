<?php
class ES_News  extends ESSource
{
	var $index;
	var $type;
	public function __construct()
	{
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this)));
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
	
	public function __destruct()
	{	parent::__destruct();	}
	
	public function getNewsById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->news_id;
			
			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				$row						=	(object)$result['_source'];
				$robject					=	new stdClass();
				$robject->news_id 			=	(int)$row->news_id;
				$robject->pub_datetime 		=	(string)$row->pub_datetime;
				$robject->title 			=	(string)$this->common->entityDecode($row->title);
				$robject->author 			=	(string)$this->common->entityDecode($row->author);
				$robject->image 			=	[	'base_url'		=>	(string)_AWS_URL._NEWS_IMAGES_DIR,
													'image_path'	=>	(string)$row->image];
				$robject->url 				=	(string)$row->url;
				$robject->body 				=	(string)$this->common->entityDecode($row->body);
				$robject->announcement		=	(boolean)(!empty($row->is_announcement)?true:false);
				$robject->enable_like 		=	(boolean)(!empty($row->enable_like)?true:false);
				$robject->enable_comment 	=	(boolean)(!empty($row->enable_comment)?true:false);
				$robject->load_via_url 		=	(boolean)(!empty($row->load_via_url)?true:false);
				$robject->comment_count 	=	(int)count($row->comments);
				$robject->like_count 		=	(int)count($row->likes);
				$robject->liked 			=	(boolean)(in_array($object->employee_id,$row->likes));
			endif;
			return !empty($robject)?$robject:new stdClass();
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	
	public function getNews($object){
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]],
																['term' => ['published'=>(int)1]],
																['term' => ['is_announcement'=>(int)$object->announcement]],
																['range'=> ['pub_datetime' => ['lte' => (string)date('Y-m-d H:i:s'), "format"=> "yyyy-MM-dd HH:mm:ss"]]]]];
			if (!empty($object->year)):
				$params['body']['query']['bool']['filter']	=	['range'=> ['pub_datetime' => ['gte' => (string)$object->year.'-01-01','lte' => (string)$object->year.'-12-31',"format"=> "yyyy-MM-dd"]]];
			endif;
			
			if (!empty($object->query)):
				$this->search_token		=	trim($object->query);
				$this->setFullTextToken();
				array_push($params['body']['query']['bool']['must'], ['query_string'=> ['fields' => ['author', 'url', 'title^2', 'source', 'body'],'query' => $this->search_token]]);
			endif;
			
			$params['body']['sort']	=	[	['pub_datetime'	=>	['order'	=>	'desc']],
 											['news_id'		=>	['order'	=>	'desc']]];
			
			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getNewsYear() {
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$params['size']  	= 	1000;
			$params['scroll']	=	'30s';
			$params['_source']	=	['pub_datetime'];
			$params['body']['query']['bool']	=	['must' => [['term' 	=> 	['enabled'			=>	(int)1]],
																['term' 	=> 	['published'		=>	(int)1]],
																['term' 	=> 	['is_announcement'	=>	(int)0]]]];
			

			$results	=	$this->es_search($params);
			$years		=	[];
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				foreach ($results['hits']['hits'] as $result):
					$row	=	(object)$result['_source'];
					$year	=	date('Y',strtotime($row->pub_datetime));
					array_push($years,$year);
				endforeach;
				$results 	= 	$this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			$years	=	array_values(array_unique($years));
			rsort($years);
			return  $years;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	private function setOutput($results,$object){
		$arr 		=	[];
		if ($results['hits']['total']>0):
			foreach ($results['hits']['hits'] as $news_obj):
				if (!empty($news_obj['_source'])):
					$row						=	(object)$news_obj['_source'];
		
					$robject					=	new stdClass();
					$robject->news_id 			=	(int)$row->news_id;
					$robject->pub_datetime 		=	(string)$row->pub_datetime;
					$robject->title 			=	(string)$this->common->entityDecode($row->title);
					$robject->author 			=	(string)$this->common->entityDecode($row->author);
					$robject->image 			=	[	'base_url'		=>	(string)_AWS_URL._NEWS_IMAGES_DIR,
														'image_path'	=>	(string)$row->image];
					$robject->url 				=	(string)$row->url;
					$robject->body 				=	(string)$this->common->entityDecode($row->body);
					$robject->announcement		=	(boolean)(!empty($row->is_announcement)?true:false);
					$robject->enable_like 		=	(boolean)(!empty($row->enable_like)?true:false);
					$robject->enable_comment 	=	(boolean)(!empty($row->enable_comment)?true:false);
					$robject->load_via_url 		=	(boolean)(!empty($row->load_via_url)?true:false);
					
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