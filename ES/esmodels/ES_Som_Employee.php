<?php 
class ES_Som_Employee extends ESSource 
{
	var $index;
	var $type;
	var $years;
	public function __construct()
	{	
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}

    public function __destruct()
	{	parent::__destruct();	}


	public function getSomEmployees($object) {
		try{
			$params 			= 	[];
			$params['from'] 	= 	0;
			$params['size'] 	= 	2000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	[	'must' => 	[['term' => ['enabled'=>1]], 
																	 ['term' => ['som_id'=> (int)$object->som_id]]]];
			
			$params['body']['query']['bool']['should']	=	[['nested'	=>	[   'path' 		    => 'testimonials',
																				'query'		    =>  ['bool' => ['must' => ['range' => ['testimonials.not_without' => ['gte'=> (int)0]]]]],
																				'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	(int)100 , 'sort' =>[['testimonials.not_without' => ['order'	=>	'desc' ]],['testimonials.testimonial_id' => ['order'	=>	'asc' ]]]]]]];
			
			if (!empty($object->years)):
				array_push($params['body']['query']['bool']['must'], ['terms'	=>	['year_of_completion'	=>	array_map('intval', explode(',', $object->years))]]);
			endif;
			
			$params['body']['sort']				=	['year_of_completion' => ['order'	=>	'desc']];
			$arr				=		$object->year_filters;
			
			$results			=		$this->es_search($params);
			if ($results['hits']['total']>0):
				$empIds		=	[];
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						array_push($empIds, $obj['_source']['employee_id']);
					endif;
				endforeach;
				/*get all employee details*/
				if(!empty($empIds)):
					$object->ids		=		$empIds;
					$ES_Employee		=		new ES_Employee();
					$objEmployees		=		$ES_Employee->getEmployeeByIdsAndFilters($object);
					$objEmployees		=		array_column($objEmployees,NULL,"employee_id");
					
					foreach ($results['hits']['hits'] as $obj):
						if (!empty($obj['_source'])):
							$row				=	(object)$obj['_source'];
							$row->inner_doc		=	( ($obj['inner_hits']['testimonials']['hits']['total'] > 0) ?$obj['inner_hits']['testimonials']['hits']['hits']:[]);
							$year	=	(int)$row->year_of_completion;
							if(isset($objEmployees[$row->employee_id])):
								array_push($arr[$year],$this->setObject($row,$objEmployees,$object));
							endif;
						endif;
					endforeach;
					/* sort employees by name*/
					function sortByName($a, $b) {
						$a	=	(array)$a;
						$b	=	(array)$b;
						return strcmp($a['sort'] , $b['sort']);
					}
					foreach ($arr as $year => $narr):
						if(!empty($narr) ):
							usort($narr, 'sortByName');
							$arr[$year] =	$narr;
							foreach ($narr as $key => $obj):
								unset($obj->sort);
							endforeach;
						endif;
					endforeach;
				endif;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getEmployeeByStarId($object) {
		try{
			$params 			= 	[];
			$params['index'] 	= 	$this->index;
			$params['from'] 	= 	0;
			$params['size'] 	= 	1;
			$params['body']['query']['bool']	=	[	'must' => 	[['term' => ['star_employee_id'=> (int)$object->star_employee_id]]]];
			$params['body']['query']['bool']['should']	=	[['nested'	=>	[   'path' 		    => 'testimonials',
																				'query'		    =>  ['bool' => ['must' => ['range' => ['testimonials.not_without' => ['gte'=> (int)0]]]]],
																				'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	(int)100 , 'sort' =>[['testimonials.not_without' => ['order'	=>	'desc' ]],['testimonials.testimonial_id' => ['order'	=>	'asc' ]]]]]]];
			
			
			$result				=		$this->es_search($params);
			$objEmployees		=		[];
			if ($result['hits']['total']>0):
				$row				=		(object)$result['hits']['hits'][0]['_source'];
				$row->inner_doc		=		( ($result['hits']['hits'][0]['inner_hits']['testimonials']['hits']['total'] > 0) ?$result['hits']['hits'][0]['inner_hits']['testimonials']['hits']['hits']:[]);
				$ES_Employee		=		new ES_Employee();
				$objEmployees		=		array_column($ES_Employee->getEmployeeByIds([$row->employee_id]),NULL,'employee_id');
				$ES_Som				=		new  ES_Star_Microland();
				$som_obj			=		$ES_Som->getSOMById($row);
				$object->title		=		$som_obj->title;
				$robject			=		$this->setObject($row,$objEmployees,$object);
			endif;
			return !empty($robject)?$robject:0;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setObject($row, $objEmployees,$object){
		try{
			$robject	=	new stdClass();
			$robject->star_employee_id		= 	(int)$row->star_employee_id;
			$robject->summary				= 	(string)$this->common->entityDecode($row->summary);
			$robject->description			= 	(string)$this->common->entityDecode($row->description);
			$robject->year_of_completion	= 	(int)$row->year_of_completion;
			$robject->promo_image			=	(object)[	'base_url'		=>	(string)_AWS_URL._SOM_IMAGES_DIR,
															'image_path'	=>	(string)$row->promo_image ];
			
		
			
			
			$emp							=	new stdClass();
			$emp->employee_id 				= 	(int)$objEmployees[$row->employee_id]['employee_id'];
			$first_name						=	!empty($objEmployees[$row->employee_id]['first_name'])?$this->common->entityDecode($objEmployees[$row->employee_id]['first_name']):"";
			$middle_name					=	!empty($objEmployees[$row->employee_id]['middle_name'])?$this->common->entityDecode($objEmployees[$row->employee_id]['middle_name']):"";
			$last_name						=	!empty($objEmployees[$row->employee_id]['last_name'])?$this->common->entityDecode($objEmployees[$row->employee_id]['last_name']):"";
			$emp->first_name				=	$first_name;
			$emp->middle_name				=	$middle_name;
			$emp->last_name					=	$last_name;
			$sfirst_name					=	!empty($first_name)?$first_name." ":"";
			$smiddle_name					=	!empty($middle_name)?$middle_name." ":"";
			$emp->employee_name				=	(string)$sfirst_name.$smiddle_name.$last_name;
			$emp->position_title			=	(string)$this->common->entityDecode($objEmployees[$row->employee_id]["position_title"]);
			$emp->employee_code				=	(string)$objEmployees[$row->employee_id]['employee_code'];
			$emp->year_of_joining			=	(string)date('Y',strtotime($objEmployees[$row->employee_id]['date_of_joining']));
			$emp->profile_picture			=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
															'image_path'	=>	(string)$objEmployees[$row->employee_id]['profile_picture']];
			
			$robject->employee				=	$emp;
			$robject->comment_count 		=	(int)count($row->comments);
			$robject->likes					= 	(object)[	'count'		=>	(int)count($row->likes),
															'is_liked'	=>	(boolean)in_array($object->employee_id,$row->likes)];
			
			$items					=	[];
			if (!empty($row->inner_doc)):
				foreach ($row->inner_doc as $item):
					$item	=	$item['_source'];
					$pObj	=	new stdClass();
					$pObj->testimonial_id		=	(int)$item['testimonial_id'];
					$pObj->is_not_without		=	(boolean)( ($item['not_without'] == 1)?true:false);
					$pObj->name					=	(string)$this->common->entityDecode($item['name']);
					$pObj->designation			=	(string)$this->common->entityDecode($item['designation']);
					$pObj->description			=	(string)$this->common->entityDecode($item['description']);
					array_push($items, $pObj);
				endforeach;
			endif;
			
			$robject->testimonials			=	$items;
			$robject->som_info				= 	(object)[	'som_id'	=> (int)$row->som_id,
															'title'		=> 	(string)$object->title];
			$robject->sort					=	$emp->employee_name;
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function setSomLinks($empIds){
		try{
			
			$params 			= 	[];
			$params['from'] 	= 	0;
			$params['size'] 	= 	2000;
			$params['index'] 	= 	$this->index;
		
			$params['body']['query']['bool']	=	[	'must' => 	[   ['term' => ['enabled'=>1]],
																		['terms' => ['employee_id'=> array_values($empIds)]]]];
			
			
			$results			=		$this->es_search($params);
			$arr				=		[];
			if ($results['hits']['total']>0):
				$obj				= 	new stdClass();
				$obj->start      	=   0;
				$obj->end        	=  	2000;
				$ES_Som				=	new ES_Star_Microland();
				$som_obj			=	$ES_Som->getSomList($obj);
				foreach ($som_obj as $key => $sobj):
					foreach ($results['hits']['hits'] as $obj):
						if (!empty($obj['_source'])):
							$row 		=	(object)$obj['_source'];
							if($sobj->som_id == $row->som_id):
								$pObj	=	new stdClass();
								$pObj->title				=	(string)$sobj->title;
								$pObj->profile_id			=	(int)$row->star_employee_id;
								$arr[$row->employee_id][]	=	$pObj;
							endif;
						endif;
					endforeach;
				endforeach;
			endif;
			return $arr;
		}catch(Exception $e)
		{	$this->es_error($e);	}
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