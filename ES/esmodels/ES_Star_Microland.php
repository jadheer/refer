<?php 
class ES_Star_Microland extends ESSource 
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

	
	public function getSomList($object) {
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']					=	[ 'must' => ['term' => ['enabled'=>1]]];
			$params['body']['sort']								=	['start_date' => ['order'	=>	'desc']];
			
			$results	=	$this->es_search($params);	
			$arr		=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $som):
					if (!empty($som['_source'])):
						$row						=	(object)$som['_source'];
						$robject					=	new stdClass();
						$robject->som_id			=	(int)$row->som_id;
						$robject->title				=	(string)$this->common->entityDecode($row->title);
						$robject->description		=	(string)$this->common->entityDecode($row->description);
						$robject->promo_image 		=	(object)[	'base_url'		=>	(string)_AWS_URL._SOM_IMAGES_DIR,
																	'image_path'	=>	(string)$row->promo_image];
						array_push($arr,$robject);
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	

	public function updateTags($object) {
		try{
			$params['index'] 		= 	$this->index;
			$params['type']			=	$this->type;
			$params['body']['query']['bool']					=	[ 'must' => ['term' => ['som_id'=>(int)$object->som_id]]];
			$params['body']['script']['inline']		=	'for (int i = 0; i < ctx._source.gallery.size(); i++){if(ctx._source.gallery[i].picture_id == params.picture_id){ctx._source.gallery[i].tags = params.tags;}}';
			$params['body']['script']['params']		=	['picture_id' => (int)$object->picture_id , 'tags' => array_map('intval',explode(',',$object->tags))];
			
			return $this->es_updateByQuery($params);
			
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	

	public function getSomMediaById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['body']['query']['bool']		=	[ 'must' => [['term' => ['som_id'=>(int)$object->som_id]]]
															];	
			$params['body']['query']['bool']['should']	=	[['nested'	=>	[   'path' 		    => 'forewords',
																				'query'		    =>  ['bool' => ['must' => [['term' => ['forewords.enabled' => (int)1]]]]],
																				'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	(int)100 , 'sort' =>['forewords.sort_order' => ['order'	=>	'asc' ]]]]]];
	
			$result			=	$this->es_search($params);
			$tag_arr	=	[];
			if ($result['hits']['total']>0):
				$row			=	(object)$result['hits']['hits'][0]['_source'];
				$inner_doc		=	$result['hits']['hits'][0]['inner_hits']['forewords']??[];
				if (!empty($row->gallery)):
					foreach ($row->gallery as $pic):
						$tag_arr	=	array_merge($tag_arr,$pic['tags']);
					endforeach;
				endif;

				if(!empty($tag_arr)):
					$ES_Employee		=		new ES_Employee();
					$objEmployees		=		array_column($ES_Employee->getEmployeeByIds($tag_arr),NULL,"employee_id");
				endif;
				
				$robject					=	new stdClass();
				$robject->som_id    		=	(int)$row->som_id;
				$robject->title 			=	(string)(!empty($row->title)?$this->common->entityDecode($row->title):"");
				$robject->book				=	(object)[	'url'	 => (string)(!empty($row->pdf_url)?_AWS_URL._SOM_PDF_DIR.$row->pdf_url:'')];
				$robject->youtube			=	(object)[	'url'	 => (string)(!empty($row->youtube_url)?$row->youtube_url:''),
															'label'  => (string)$row->youtube_label ];
				
				$forewords_arr	=	[];
				if(!empty($inner_doc['hits']['total'])):
					foreach ($inner_doc['hits']['hits'] as $foreword):
						if (!empty($foreword['_source'])):
							$foreword					= 	(object)$foreword['_source'];
							$forewords					=	new stdClass();
							$forewords->foreword_id		=	(int)$foreword->foreword_id;
							$forewords->name			=	(string)$this->common->entityDecode($foreword->name);
							$forewords->designation		=	(string)$this->common->entityDecode($foreword->designation);
							$forewords->summary			=	(string)$this->common->entityDecode($foreword->summary);
							$forewords->description		=	(string)$this->common->entityDecode($foreword->description);
							$forewords->promo_image 	=	(object)[	'base_url'		=>	(string)_AWS_URL._SOM_IMAGES_DIR,
																		'image_path'	=>	(string)$foreword->promo_image];
							array_push($forewords_arr,$forewords);
						endif;
					endforeach;
				endif;
				
				$pictures	=	[];
				if (!empty($row->gallery)):
					foreach ($row->gallery as $pic):
						$pObj	=	new stdClass();
						$pObj->picture_id		=	(int)$pic['picture_id'];
						$pObj->som_id			=	(int)$row->som_id;
						$pObj->picture_path		=	(string)$pic['picture_path'];
						$pObj->picture_caption	=	(string)$this->common->entityDecode($pic['picture_caption']);
						$pObj->tags				=	[];
						if(!empty($pic['tags'])):
							foreach($pic['tags'] as $id):
								if(isset($objEmployees[$id]) && $objEmployees[$id]['enabled']==1):
									$emp	=	$this->setEmpObject($objEmployees,$id);
									array_push($pObj->tags, $emp);
								endif;
							endforeach;
							if(!function_exists('sortByName')):
								function sortByName($a, $b) {
									$a	=	(array)$a;
									$b	=	(array)$b;
									return strcmp($a['employee_name'] , $b['employee_name']);
								}
							endif;
							usort($pObj->tags, 'sortByName');		
						endif;
						array_push($pictures, $pObj);
					endforeach;
				endif;
				
				$robject->gallery	=	(object)[	'base_url'	=>	(string)_AWS_URL._SOM_IMAGES_DIR,
													'pictures'	=>	$pictures];
				$robject->forewords	=	$forewords_arr;
			endif;
			
			return !empty($robject)?$robject:new stdClass();
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}
	
	
	public function getGalleryByPictureId($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['_source']	=	'title,som_id,gallery';
			$params['body']['query']['bool']['must']= 	[	['term' 	=> ["gallery_ids" => (int)$object->picture_id ]],
															['nested'	=>	['path' => 'gallery',
																			'query'	=> ['bool' => ['must' => [['term' => ['gallery.picture_id' => (int)$object->picture_id]]]]],
																			'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	(int)1]]]];
			
			$tag_arr	=	[];
			$pictures	=	[];
			$result		=	$this->es_search($params);
			
			if (!empty($result['hits']['hits'][0]['_source'])):

				$row			=	(object)$result['hits']['hits'][0]['_source'];
				$inner_doc		=	$result['hits']['hits'][0]['inner_hits']??[];
				$gallery		=	[];
				if(isset($inner_doc['gallery']['hits']['hits'][0]['_source'])):
					$gallery	= 	$inner_doc['gallery']['hits']['hits'][0]['_source'];
				endif;
			
				if (!empty($gallery)):
					$tag_arr	=	$gallery['tags'];
					if(!empty($tag_arr)):
						$ES_Employee		=		new ES_Employee();
						$objEmployees		=		array_column($ES_Employee->getEmployeeByIds($tag_arr),NULL,"employee_id");
					endif;
					
					$pObj	=	new stdClass();
					$pObj->picture_id		=	(int)$gallery['picture_id'];
					$pObj->som_id			=	(int)$row->som_id;
					$pObj->title			=	(string)$this->common->entityDecode($row->title);
					$pObj->base_url			=	(string)_AWS_URL._SOM_IMAGES_DIR;
					$pObj->picture_path		=	(string)$gallery['picture_path'];
					$pObj->picture_caption	=	(string)$this->common->entityDecode($gallery['picture_caption']);
					$pObj->tags				=	[];
					if(!empty($gallery['tags'])):
						foreach($gallery['tags'] as $id):
							if(isset($objEmployees[$id]) && $objEmployees[$id]['enabled']==1):
								$emp	=	$this->setEmpObject($objEmployees,$id);
								array_push($pObj->tags, $emp);
							endif;
						endforeach;
						
						function sortByName($a, $b) {
							$a	=	(array)$a;
							$b	=	(array)$b;
							return strcmp($a['employee_name'] , $b['employee_name']);
						}
						usort($pObj->tags, 'sortByName');
					endif;
					$robject =	$pObj;
				endif;
			endif;
			return !empty($robject)?$robject:0;
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	

	private function setEmpObject($objEmployees,$id){
		try{
			
			$emp						=	new stdClass();
			$emp->employee_id 			= 	(int)$objEmployees[$id]['employee_id'];
			$first_name					=	!empty($objEmployees[$id]['first_name'])?$this->common->entityDecode($objEmployees[$id]['first_name']):"";
			$middle_name				=	!empty($objEmployees[$id]['middle_name'])?$this->common->entityDecode($objEmployees[$id]['middle_name']):"";
			$last_name					=	!empty($objEmployees[$id]['last_name'])?$this->common->entityDecode($objEmployees[$id]['last_name']):"";
			$emp->first_name			=	$first_name;
			$emp->middle_name			=	$middle_name;
			$emp->last_name				=	$last_name;
			$sfirst_name				=	!empty($first_name)?$first_name." ":"";
			$smiddle_name				=	!empty($middle_name)?$middle_name." ":"";
			$emp->employee_name			=	(string)$sfirst_name.$smiddle_name.$last_name;
			$emp->position_title		=	(string)$this->common->entityDecode($objEmployees[$id]["position_title"]);
			$emp->employee_code			=	(string)$objEmployees[$id]['employee_code'];
			$emp->display_name			=	(string)$objEmployees[$id]['display_name'];
			$emp->profile_picture		=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
													'image_path'	=>	(string)$objEmployees[$id]['profile_picture']];
			
			return $emp;
			
		}catch(Exception $e){
			$this->es_error($e);
		}
	}
	
	public function getSOMById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->som_id;
			$params['_source']	=	['som_id', 'title'];
			
			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				$row						=	(object)$result['_source'];
				$robject					=	new stdClass();
				$robject->som_id    		=	(int)$row->som_id;
				$robject->title 			=	(string)(!empty($row->title)?$this->common->entityDecode($row->title):"");
			endif;
			return !empty($robject)?$robject:new stdClass();
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
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