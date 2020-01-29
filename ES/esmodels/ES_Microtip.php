<?php
class ES_Microtip  extends ESSource
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
	
	
	public function getMicrotipById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->microtip_id;
			
			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				$row						=	(object)$result['_source'];
				$robject 					=	new stdClass();
				$categories					=	$this->getMicrotipCategory();
				
				$robject->microtip_id 		=	(int)$row->microtip_id;
				$robject->image 			=	[	'base_url'		=>	(string)_AWS_URL._MICROTIPS_IMAGES_DIR,
													'image_path'	=>	(string)$row->image];
				
				$robject->summary			=	(string)$this->common->truncate_str(trim(strip_tags($this->common->entityDecode($row->body))),200);
				$robject->text 				=	(string)$this->common->entityDecode($row->body);
				$robject->pub_datetime 		=	(string)$row->pub_datetime;
				
				$robject->category			=	(object)[	'category_id'	=>	(int)$row->microtip_type_id,
															'name'			=>	(string)(isset($categories[$row->microtip_type_id])?$this->common->entityDecode($categories[$row->microtip_type_id]->value):'')];
				$robject->like_count 		=	(int)count($row->likes);
				$robject->liked 			=	(boolean)(in_array($object->employee_id,$row->likes));
			endif;
			
			return !empty($robject)?$robject:new stdClass();
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	
	public function getMicrotips($object){
		try
		{
			$params = [];
			$params['from']  	= 	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
			$categories			=	$this->getMicrotipCategory();
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>1]],
																['term' => ['published'=>1]],
																[['bool'=> ['should' =>	['terms' =>	['microtip_type_id' => array_keys($categories)]]]]]]];
			
			if (!empty($object->category_id)):
				$query	=	['match' => ['microtip_type_id'		=>	(int)$object->category_id	]];
				array_push($params['body']['query']['bool']['must'], $query);
			endif;
			$params['body']['sort']	= [['pub_datetime' => ['order'	=>	'desc']], ['microtip_id' =>	['order' =>	'desc']]];
			
			return $this->setOutput($this->es_search($params),$object,$categories);		
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	private function setOutput($results,$object,$categories){
		try{
			$arr 	=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $microtip_obj):
					if (!empty($microtip_obj['_source'])):
						$row						=	(object)$microtip_obj['_source'];
						$robject 					=	new stdClass();
						$robject->microtip_id 		=	(int)$row->microtip_id;
						$robject->image 			=	[	'base_url'		=>	(string)_AWS_URL._MICROTIPS_IMAGES_DIR,
															'image_path'	=>	(string)$row->image];
						$robject->summary			=	(string)$this->common->truncate_str(trim(strip_tags(html_entity_decode($this->common->entityDecode($row->body), ENT_QUOTES))),200);
						$robject->text 				=	(string)$this->common->entityDecode($row->body);
						$robject->pub_datetime 		=	(string)$row->pub_datetime;
				
	 					$robject->category			=	(object)[	'category_id'	=>	(int)$row->microtip_type_id,
	 																'name'			=>	(string)(isset($categories[$row->microtip_type_id])?$this->common->entityDecode($categories[$row->microtip_type_id]->value):'')];
	 					$robject->like_count 		=	(int)count($row->likes);
	 					$robject->liked 			=	(boolean)(in_array($object->employee_id,$row->likes));
	 				endif;
					array_push($arr, $robject);
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getMicrotipCategory(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	"microtip_categories";
			$params['type'] 	= 	"category";
			
			$params['body']['query']['bool']['must'] = ['match' => ['enabled'=>1]];
			
			$objects	=	$this->es_search($params);
			$arr 		=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$row	=	(object)$object['_source'];
						$row->value	=	$this->common->entityDecode($row->value);
						$arr[$row->microtip_type_id]	=	$row;
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>