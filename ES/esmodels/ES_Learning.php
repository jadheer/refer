<?php 
class ES_Learning extends ESSource 
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

	public function getLearningsByType($object) {
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	[	'must' => [	['term' => ['enabled'=>1]],
																	['term' => ['published'=>1]]]];
			if ($object->type=="past"):
			$condition	=	['range'=>	[ 'end_datetime' => [ 'lte' => (string)date('Y-m-d H:i:s'),"format"=> "yyyy-MM-dd HH:mm:ss"]]];
				array_push($params['body']['query']['bool']['must'], $condition);
			else:
			$condition	=	['range'=>	[ 'end_datetime' => [ 'gt' => (string)date('Y-m-d H:i:s'),"format"=> "yyyy-MM-dd HH:mm:ss"]]];
				array_push($params['body']['query']['bool']['must'], $condition);
			endif;
			
			if (!empty($object->geography_id)):
				array_push($params['body']['query']['bool']['must'], ['terms'	=>	['geographies'	=>	array_map('intval', explode(',', $object->geography_id))]]);
			endif;
			if (!empty($object->location_id)):
				array_push($params['body']['query']['bool']['must'], ['terms'	=>	['locations'	=>	array_map('intval', explode(',', $object->location_id))]]);
			endif;
			if (!empty($object->function_id)):
				array_push($params['body']['query']['bool']['must'], ['terms'	=>	['functions'	=>	array_map('intval', explode(',', $object->function_id))]]);
			endif;
			if (!empty($object->level_id)):
				array_push($params['body']['query']['bool']['must'], ['terms'	=>	['levels'	=>	array_map('intval', explode(',', $object->level_id))]]);
			endif;
			if (!empty($object->layer_id)):
				array_push($params['body']['query']['bool']['must'], ['terms'	=>	['layers'	=>	array_map('intval', explode(',', $object->layer_id))]]);
			endif;
			
			if ($object->type=="past"):
				$params['body']['sort']	=	[['start_datetime' => ['order'	=>	'desc']], ['learning_id' => ['order' => 'asc']]];
			else:
				$params['body']['sort']	=	[['start_datetime' => ['order'	=>	'asc']], ['learning_id' => ['order' => 'asc']]];
			endif;

			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getLearningByDate($object) {
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	[	'must' => [	['term' => ['enabled'=>1]],
																	['term' => ['published'=>1]],
																	['range'=>	[ 'start_date' => ['lte' => (string)$object->date, "format"=> "yyyy-MM-dd"]]],
																	['range'=>	[ 'end_date' => ['gte' => (string)$object->date, "format"=> "yyyy-MM-dd"]]]]];
																	
			if (!empty($object->geography_id)):
				array_push($params['body']['query']['bool']['must'], ['terms'	=>	['geographies'	=>	array_map('intval', explode(',', $object->geography_id))]]);
			endif;
			if (!empty($object->location_id)):
				array_push($params['body']['query']['bool']['must'], ['terms'	=>	['locations'	=>	array_map('intval', explode(',', $object->location_id))]]);
			endif;
			if (!empty($object->function_id)):
				array_push($params['body']['query']['bool']['must'], ['terms'	=>	['functions'	=>	array_map('intval', explode(',', $object->function_id))]]);
			endif;
			if (!empty($object->level_id)):
				array_push($params['body']['query']['bool']['must'], ['terms'	=>	['levels'	=>	array_map('intval', explode(',', $object->level_id))]]);
			endif;
			if (!empty($object->layer_id)):
				array_push($params['body']['query']['bool']['must'], ['terms'	=>	['layers'	=>	array_map('intval', explode(',', $object->layer_id))]]);
			endif;
			$params['body']['sort']	=	[['start_datetime' => ['order'	=>	'asc']], ['learning_id' => ['order' => 'asc']]];

			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{  $this->es_error($e);   }
	}

	public function getLearningById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->learning_id;
				
			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Level		=	new ES_Level();
				$ES_Function	=	new ES_Function();
				
				$ObjGeos	=	$ES_Geography->getGeographies();
				$Objlocs	=	$ES_Location->getLocations();
				$ObjLevs	=	$ES_Level->getLevels();
				$ObjFuncs	=	$ES_Function->getFunctions();
				
				$ES_Layer		=	new ES_Layer();
				$ObjLays		=	$ES_Layer->getLayers();
				$ObjMaster		=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers' => $ObjLays ];
				$componentMapping	=	new ComponentMapping($ObjMaster);
				
				$robject	=	$this->setObject((object)$result['_source'], $object, $componentMapping);
			endif;
			return !empty($robject)?$robject:new stdClass();
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	
	public function getUniqueLearningDatesByType($type) {
		try
		{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$condition			=	($type	==	"upcoming")?"gte":"lte";
			$params['size']  	= 	1000;
			$params['scroll']	=	'30s';
			$params['_source']	=	['start_date'];
			$dates				=	[];
			
			$params['body']['query']['bool']	=	['must' => [['term' 	=> 	['enabled'=>(int)1]],
																['term' 	=> 	['published'	=>	(int)1]],
																['range' 	=> 	['end_date'	=> [ $condition => date("Y-m-d",strtotime("now")) ,"format"=> "yyyy-MM-dd"]]]]];
			$results	=	$this->es_search($params);
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				foreach ($results['hits']['hits'] as $result):
					$row	=	(object)$result['_source'];
					$date	=	date('Y-m-d',strtotime($row->start_date));
					array_push($dates,$date);
				endforeach;
				$results 	= 	$this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			function date_sort($a, $b) {
				return strtotime($a) - strtotime($b);
			}
			$dates	=	array_values(array_unique($dates));
			usort($dates, "date_sort");
			return  $dates;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results,$object){
		try{
			$arr 		=	[];
			if ($results['hits']['total']>0):
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Level		=	new ES_Level();
				$ES_Function	=	new ES_Function();
		
				$ObjGeos	=	$ES_Geography->getGeographies();
				$Objlocs	=	$ES_Location->getLocations();
				$ObjLevs	=	$ES_Level->getLevels();
				$ObjFuncs	=	$ES_Function->getFunctions();
				
				$ES_Layer		=	new ES_Layer();
				$ObjLays		=	$ES_Layer->getLayers();
				$ObjMaster		=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers' => $ObjLays ];
				
				$componentMapping	=	new ComponentMapping($ObjMaster);
				foreach ($results['hits']['hits'] as $ijp_obj):
					if (!empty($ijp_obj['_source'])):
						$row	=	(object)$ijp_obj['_source'];
					endif;
					array_push($arr, $this->setObject($row, $object, $componentMapping));
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setObject($row, $object, $componentMapping){
		try{
			$robject	=	new stdClass();
			$robject->learning_id		=	(int)$row->learning_id;
			$robject->author			=	(string)$this->common->entityDecode($row->author);
			$robject->title				=	(string)$this->common->entityDecode($row->title);
			$robject->description		=	(string)$this->common->entityDecode($row->description);
			$robject->summary			=	(string)html_entity_decode($this->common->truncate_str(trim(strip_tags($this->common->entityDecode($row->description))),300),ENT_QUOTES);
			$robject->start_date		=	(string)$row->start_date;
			$robject->end_date			=	(string)$row->end_date;
			$robject->start_time		=	(string)$row->start_time;
			$robject->end_time			=	(string)$row->end_time;
			$robject->promo_image 		=	(object)[	'base_url'		=>	(string)_AWS_URL._LEARNINGS_IMAGES_DIR,
														'image_path'	=>	(string)$row->promo_image];
	
			$pictures	=	[];
			if (!empty($row->gallery)):
				foreach ($row->gallery as $pic):
					$pObj	=	new stdClass();			
					$pObj->picture_path		=	(string)$pic['picture_path'];
					$pObj->picture_caption	=	(string)$this->common->entityDecode($pic['picture_caption']);
					array_push($pictures, $pObj);
				endforeach;
			endif;
			$robject->gallary			=	(object)[	'base_url'		=>	(string)_AWS_URL._LEARNINGS_IMAGES_DIR,
														'pictures'		=>	$pictures];
	
			$allowedRegistration		=	(boolean)(!empty($row->registrations)?true:false);
			if ($allowedRegistration):
				$allowedRegistration 	=	(strtotime($robject->end_date.' '.$robject->end_time)>time())?true:false;
				if (strtotime($row->registrations_last_date) < strtotime(date('Y-m-d'))):
					$allowedRegistration	=	(boolean)false;
				endif;
				if ($allowedRegistration):
					$allowedRegistration	=	($row->max_registrations==count($row->registrations_details))?false:true;
				endif;
			endif;
			
			$robject->registrations		=	(object)[	'registration_required'	=>	(boolean)(!empty($row->registrations)?true:false),
														'allowed_register' 	=> 	$allowedRegistration,
														'last_date'			=>	(string)$row->registrations_last_date,
														'max_limit'			=>	(int)$row->max_registrations,
														'total_register'	=>	(int)count($row->registrations_details),
														'is_registered'		=>	(boolean)in_array($object->employee_id,$row->registrations_details),
														'past_event'		=>	(boolean)(strtotime($robject->end_date.' '.$robject->end_time)<time())?true:false];
			
			$robject->learning_location		=	(string)$this->common->entityDecode($row->learning_location);
			$robject->geo_locations			=	(array)$componentMapping->getGeoLocations(implode(",",$row->geographies),implode(",",$row->locations));
			$robject->functions				=	(array)$componentMapping->getFunctions(implode(",",$row->functions));
			$robject->level_layers			=	(array)$componentMapping->getLevelLayers(implode(",",$row->levels),implode(",",$row->layers));
			$robject->likes					=	(object)[	'count'		=>	(int)count($row->likes),
															'is_liked'	=>	(boolean)in_array($object->employee_id,$row->likes)];
	
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function apply($object){
		try {
			$params = [];
			$params = [];
			$params['index']  	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->learning_id;
				
			$params['body']['script']['inline']	=	'ctx._source.registrations_details.add(params.registrations_details)';
			$params['body']['script']['params']	=	['registrations_details' => (int)$object->employee_id];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
    public function refresh(){
		try{
			$params = [];
			$params['index']  		= 	$this->index;
			return $this->es_indices_refresh($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>