<?php 
class ES_Classified extends ESSource 
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
    
	public function insert($object,$gallery){
		try
		{
			$params = [];
			$pictures	=	[];
			if(!empty($gallery)):
				foreach ($gallery	as $pic):
					$picture					=	new stdClass();;
					$picture->picture_id		=	(int)$pic->picture_id;
					$picture->picture_path	 	=	(string)$pic->picture_path;
					$picture->picture_caption	=	(string)$this->common->entityEncode($pic->picture_caption);
					$picture->created_on		=	(string)$pic->created_on;
					$picture->updated_on		=	(string)$pic->updated_on;
					$picture->enabled			=	(int)1;
					array_push($pictures,$picture);
				endforeach;
			endif;
			
			$params['body']  		= 	[	'classified_id'		=>	(int)$object->classified_id,
											'category_id'		=>	(int)$object->category_id,
											'employee_id'		=>	(int)$object->employee_id,
											'end_date'			=>	(string)$object->end_date,
											'title'				=>	(string)$this->common->entityEncode($object->title),
											'description'		=>	(string)$this->common->entityEncode($object->description),
											'price'				=>	(string)$this->common->entityEncode($object->price),
											'city'				=>	(string)$this->common->entityEncode($object->city),
											'address'			=>	(string)$this->common->entityEncode($object->address),
											'country'			=>	(string)$this->common->entityEncode($object->country),
											'promo_image'		=>	(string)$object->promo_image,
											'pub_datetime'		=>	(string)$object->pub_datetime,
											'published'			=>	(int)0,
											'active' 			=>	(int)1,
											'rejected'			=>	(int)0,
											'created_on'		=>	(string)$object->created_on,
											'updated_on' 		=> 	(string)$object->updated_on,
											'enabled'			=>	(int)1,
											'reports'			=>	[],
											'gallery'			=>	$pictures];
			
			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->classified_id ;
			
			return $this->es_index($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function updateField($objectId, $field, $value){
		try
		{
			$params = [];
			$params['body']	= [	'doc' => [	"$field" =>	$value ]];
			
			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	$objectId;
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function update($object,$gallery){
		try
		{
			$pictures	= [];
			if(!empty($gallery)):
				foreach ($gallery	as $pic):
					$picture					=	new stdClass();;
					$picture->picture_id		=	(int)$pic->picture_id;
					$picture->picture_path	 	=	(string)$pic->picture_path;
					$picture->picture_caption	=	(string)$this->common->entityEncode($pic->picture_caption);
					$picture->created_on		=	(string)$pic->created_on;
					$picture->updated_on		=	(string)$pic->updated_on;
					$picture->enabled			=	(int)1;
					array_push($pictures,$picture);
				endforeach;
			endif;
			$params['body']	= [	'doc' 			=>	[	'classified_id'		=>	(int)$object->classified_id,
														'category_id'		=>	(int)$object->category_id,
														'employee_id'		=>	(int)$object->employee_id,
														'end_date'			=>	(string)$object->end_date,
														'title'				=>	(string)$this->common->entityEncode($object->title),
														'description'		=>	(string)$this->common->entityEncode($object->description),
														'price'				=>	(string)$this->common->entityEncode($object->price),
														'city'				=>	(string)$this->common->entityEncode($object->city),
														'address'			=>	(string)$this->common->entityEncode($object->address),
														'country'			=>	(string)$this->common->entityEncode($object->country),
														'promo_image'		=>	(string)$object->promo_image,
														'pub_datetime'		=>	(string)$object->pub_datetime,
														'published'			=>	(int)0,
														'updated_on' 		=> 	(string)$object->updated_on,
														'gallery'			=>	$pictures],
													'doc_as_upsert'	=>	true];
			
			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	(int)$object->classified_id;
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getClassifiedByEmployeeId($object){
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]],
																['term' => ['employee_id'=>(int)$object->employee_id]]]];
			
			if (!empty($object->active)):
			$active		=	[['term' => ['rejected' => (int)0]],['term' => ['active' => (int)1]],['range' => ['end_date' => ['gte'=> (string)date('Y-m-d'), "format"=> "yyyy-MM-dd"]]]];
				array_push($params['body']['query']['bool']['must'], $active);
			else:
			$active		=	[['term' => ['active' => (int)0]],['term' => ['rejected' => (int)1]],['range' => ['end_date' => ['lt'=>(string)date('Y-m-d'), "format"=> "yyyy-MM-dd"]]]];
				array_push($params['body']['query']['bool']['must'],[ 'bool' => ['should'=>$active]]);
			endif;
			
			$params['body']['sort']	=	[	['created_on'		=>	['order'	=>	'desc']],
											['classified_id'	=>	['order'	=>	'desc']]];

			
			$results	=	$this->es_search($params);
			$arr		=	[];
			if ($results['hits']['total']>0):
				$category		=	new ES_Classified_category();
				$categories		=	$category->getCategories();
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						if(isset($categories[$row->category_id])):
							array_push($arr, $this->setOutput($row, $object, $categories));
						endif;
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getClassifiedsByCategoryId($object){
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' 	=> ['enabled'	=>	(int)1]],
																['term' 	=> ['published'	=>	(int)1]],
																['term' 	=> ['active'	=>	(int)1]],
																['term' 	=> ['rejected'	=>	(int)0]],
																['range'	=> ['end_date' 	=> 	['gte' => (string)date('Y-m-d'), "format"=> "yyyy-MM-dd"]]]]];
			if (!empty($object->category_id)):
				$cat	=	['term' 	=> ['category_id'	=>	(int)$object->category_id]];
				array_push($params['body']['query']['bool']['must'], $cat);
			endif;
			
			$params['body']['query']['bool']['must_not']	=	['term' 	=> ['employee_id'	=>	(int)$object->employee_id]];
			
			$params['body']['sort']	=	[	['created_on'		=>	['order'	=>	'desc']],
											['classified_id'	=>	['order'	=>	'desc']]];
			
			$results	=	$this->es_search($params);
			
			$arr		=	[];
			$empIds		=	[];
			
			if ($results['hits']['total']>0):
				$category		=	new ES_Classified_category();
				$categories		=	$category->getCategories();
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						array_push($empIds,$row->employee_id);
					endif;
				endforeach;
				$empIds			=	array_unique($empIds);

				$objEmployees	=	[];
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
				endif;
				
				foreach ($results['hits']['hits'] as $classified_obj):
					if (!empty($classified_obj['_source']) && !empty($objEmployees)):
						$row	=	(object)$classified_obj['_source'];
						/*get classifieds from enabled category and enabled employee */
						if (isset($objEmployees) && $objEmployees[$row->employee_id]['enabled']==1 && !empty($categories[$row->category_id])):
							array_push($arr, $this->setOutput($row, $object, $categories,$objEmployees[$row->employee_id]));
						endif;
					endif;
				endforeach;
				
			endif;
			
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($row,$object,$categories,$objEmployee=NULL){
		$robject					=	new stdClass();
		$robject->classified_id  	= 	(int)$row->classified_id;
		$robject->title         	= 	(string)$this->common->entityDecode($row->title);
		$robject->description    	= 	(string)$this->common->entityDecode($row->description);
		$robject->city           	= 	(string)$this->common->entityDecode($row->city);
		$robject->country        	= 	(string)$this->common->entityDecode($row->country);
		$robject->address        	= 	(string)$this->common->entityDecode($row->address);
		$robject->price          	= 	(string)$this->common->entityDecode($row->price);
		$robject->promo_image    	= 	(object)[ 	'base_url' 		=>  (string)_AWS_URL._CLASSIFIEDS_IMAGES_DIR,
												  	'image_path' 	=>  (string)$row->promo_image];
		$robject->approved       	= 	(boolean)(!empty($row->published)?true:false);
		$robject->rejected       	= 	(boolean)(!empty($row->rejected)?true:false);
		$robject->active         	= 	(boolean)(!empty($row->active)?true:false);
		$robject->end_date       	= 	(string)$row->end_date;
		$robject->created_on    	= 	(string)$row->created_on;
		$robject->category		 	=	(object)[	'category_id'	=>	(int)$categories[$row->category_id]->category_id,
													'post_title'	=>	(string)$categories[$row->category_id]->post_title,
													'explore_title'	=>	(string)$categories[$row->category_id]->explore_title];
	
		$pictures	=	[];
		if (!empty($row->gallery)):
			foreach ($row->gallery as $pic):
				$pObj	=	new stdClass();
				$pObj->picture_path		=	(string)$pic['picture_path'];
				$pObj->picture_caption	=	(string)$this->common->entityDecode($pic['picture_caption']);
				array_push($pictures, $pObj);
			endforeach;
		endif;
		$robject->gallery	=	(object)[	'base_url'	=>	(string)_AWS_URL._CLASSIFIEDS_IMAGES_DIR,
											'pictures'	=>	$pictures];
		
		if(!empty($objEmployee)):
			$robject->report 		=	(boolean)(in_array($object->employee_id,$row->reports));
			$robject->employee		=	(object)[	'employee_id' 		=> 	(int)$objEmployee['employee_id'],
													'first_name'		=>	(string)$this->common->entityDecode($objEmployee['first_name']),
													'middle_name'		=>	(string)$this->common->entityDecode($objEmployee['middle_name']),
													'last_name'			=>	(string)$this->common->entityDecode($objEmployee['last_name']),
													'display_name'		=>	(string)$this->common->entityDecode($objEmployee['display_name']),
													'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																						'image_path'	=>	(string)$objEmployee['profile_picture']]];
		endif;
		return $robject;
	}
	
	
	public function getClassifiedsBySearch($object){
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			/*Matched category ids */
			$category			=	new ES_Classified_category();
			$category_ids		=	$category->getCategoryBySearch($object->query);
			$params['body']['query']['bool']	=	['must' => [['term' 	=> ['enabled'	=>	(int)1]],
																['term' 	=> ['published'	=>	(int)1]],
																['term' 	=> ['active'	=>	(int)1]],
																['term' 	=> ['rejected'	=>	(int)0]],
																['range'	=> ['end_date' 	=> 	['gte' => (string)date('Y-m-d'), "format"=> "yyyy-MM-dd"]]]]];
		
			$params['body']['query']['bool']['must_not']	=	['term' 	=> ['employee_id'	=>	(int)$object->employee_id]];
			
			
			/* search in clasiifieds columns and category's explore title */
			if (!empty($object->query)):
				$this->search_token		=	trim($object->query);
				$this->setFullTextToken();
				$query	=	['bool' => ['should' => [ 
							['query_string' => ['query' => $this->search_token , 'fields' => ['title^2','description','city','country','address']]],
							['terms'	=>	 ['category_id' => $category_ids]]]]];
				
				array_push($params['body']['query']['bool']['must'] ,$query);
			endif;
			
			$results	=	$this->es_search($params);
			$arr		=	[];
			$empIds		=	[];
			
			if ($results['hits']['total']>0):
				$category		=	new ES_Classified_category();
				$categories		=	$category->getCategories();
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						array_push($empIds,$row->employee_id);
					endif;
				endforeach;
				$empIds			=	array_unique($empIds);
					
					$objEmployees	=	[];
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
				endif;
					
				foreach ($results['hits']['hits'] as $classified_obj):
					if (!empty($classified_obj['_source']) && !empty($objEmployees)):
						$row	=	(object)$classified_obj['_source'];
						if (isset($objEmployees) && $objEmployees[$row->employee_id]['enabled']==1 && !empty($categories[$row->category_id])):
							array_push($arr, $this->setOutput($row, $object, $categories,$objEmployees[$row->employee_id]));
						endif;
					endif;
				endforeach;
			endif;
			return $arr;

		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}
	
	public function refresh(){
		try
		{
			$params = [];
			$params['index']  		= 	$this->index;
			return $this->es_indices_refresh($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>