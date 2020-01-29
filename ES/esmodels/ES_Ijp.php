<?php
class ES_Ijp  extends ESSource
{
	var $index;
	var $type;
	var $indentstatus_arr;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
		$this->indentstatus_arr		=	 array("IN"=>"In-Process","CL"=>"Closed","CAN"=>"Cancelled","P"=>"Planned","OH"=>"On-Hold");

	}
	
	public function __destruct()
	{	parent::__destruct();	}
	
	public function getIjpById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->ijp_id;
	
			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Level		=	new ES_Level();
				$ES_Function	=	new ES_Function();
				$ES_Layer		=	new ES_Layer();
				
				$ObjGeos	=	$ES_Geography->getGeographies();
				$Objlocs	=	$ES_Location->getLocations();
				$ObjLevs	=	$ES_Level->getLevels();
				$ObjFuncs	=	$ES_Function->getFunctions();
				$ObjLays	=	$ES_Layer->getLayers();
				
				$ObjMaster	=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers' => $ObjLays];
				
				$emp_ijps			=	[];
				if(!isset($object->is_xijp)):
					$ES_ijp_apply		=	new ES_Ijp_apply();
					$emp_ijps			=	$ES_ijp_apply->getEmployeeIjpApply($object->employee_id);
				endif;
				
				$componentMapping	=	new ComponentMapping($ObjMaster);
				$row		=	(object)$result['_source'];
				$robject	=	$this->setObject($row, $object, $componentMapping,$emp_ijps);
			endif;
			return !empty($robject)?$robject:new stdClass();
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	
	public function getIjps($object){
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	[	'must' => [	['term' => ['enabled'=>1]], ['term'=> ['indent_status'=>'IN']]]];
			
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
			
			$params['body']['sort']	=	[['created_on' => ['order'	=>	'desc']], ['ijp_id' => ['order' => 'desc']]];

			return $this->setOutput($this->es_search($params),$object);	
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
				$ES_Layer		=	new ES_Layer();
				
				
				$ObjGeos	=	$ES_Geography->getGeographies();
				$Objlocs	=	$ES_Location->getLocations();
				$ObjLevs	=	$ES_Level->getLevels();
				$ObjFuncs	=	$ES_Function->getFunctions();
				$ObjLays	=	$ES_Layer->getLayers();
		
				$ObjMaster			=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers' => $ObjLays];
				$componentMapping	=	new ComponentMapping($ObjMaster);
				
				$emp_ijps			=	[];
				if(!isset($object->is_xijp)):
					$ES_ijp_apply		=	new ES_Ijp_apply();
					$emp_ijps			=	$ES_ijp_apply->getEmployeeIjpApply($object->employee_id);
				endif;
				
				foreach ($results['hits']['hits'] as $ijp_obj):
					if (!empty($ijp_obj['_source'])):
						$row		=	(object)$ijp_obj['_source'];
					endif;
					array_push($arr, $this->setObject($row, $object, $componentMapping,$emp_ijps));
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setObject($row, $object, $componentMapping,$emp_ijps=NULL){
		try {
			$robject						=	new stdClass();
			$robject->ijp_id    			= 	(int)$row->ijp_id;
			$robject->title     			= 	(string)$this->common->entityDecode($row->title);
			$robject->indent_number  		= 	(string)$row->indent_number;
			$robject->indent_status  		= 	(string)isset($this->indentstatus_arr[$row->indent_status])?$this->indentstatus_arr[$row->indent_status]:'';
			$robject->business_unit			= 	(string)$this->common->entityDecode($row->business_unit);
			$robject->description    		= 	(string)$this->common->entityDecode($row->description);
			$robject->qualification  		= 	(string)$this->common->entityDecode($row->qualification);
			$robject->min_years      		= 	(float)$row->min_years;
			$robject->max_years     	 	= 	(float)$row->max_years;
			$robject->applied       		= 	(boolean)false;
			if(!empty($emp_ijps)):
				$robject->applied       	= 	(boolean)in_array($row->ijp_id ,$emp_ijps);
			endif;
			$robject->expiry_on				= 	(string)$row->expiry_on;
			$robject->active    			= 	(boolean)(($row->enabled == 1 ) && ($row->indent_status== "IN") && (empty($row->expiry_on) || $row->expiry_on >= date("Y-m-d"))?true:false);
			$robject->referral  			= 	(boolean)(!empty($row->referral)?true:false);
			$robject->created_on  	 		= 	(string)$row->created_on;
			$robject->expiry_on  	 		= 	(string)$row->expiry_on;
			$robject->geo_locations			=	(array)$componentMapping->getGeoLocations(implode(",",$row->geographies),implode(",",$row->locations));
			$robject->functions				=	(array)$componentMapping->getFunctions(implode(",",$row->functions));
			$robject->level_layers			=	(array)$componentMapping->getLevelLayers(implode(",",$row->levels),implode(",",$row->layers));
			$url							=	(string)$this->getSlugUrl($robject->title);
			$robject->share_link			=	(string)'';
			$robject->views		  			= 	(int)$row->views;
			$robject->talk_to_us  			= 	(boolean)(!empty($row->talk_to_us)?true:false);
			$robject->pinned	  			= 	(boolean)(!empty($row->pinned)?true:false);
			if(!empty($url)):
				$robject->share_link		=	(string)_WEB_MICROLAND_URL."/careers/apply/".$robject->ijp_id."/".$url;
			endif;
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
			$params['id']    	= 	(int)$object->ijp_id;
			
			$params['body']['script']['inline']	=	'ctx._source.applicants.add(params.applicants)';
			$params['body']['script']['params']	=	['applicants' => (int)$object->employee_id];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function addIjpView($object){
		try {
			$params = [];
			$params['index']  	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->ijp_id;
			
			$params['body']['script']['inline']	=	'ctx._source.views++';
			return $this->es_update($params);
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
	
	public function getXIjps($object){
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	[	'must' => [	['term' => ['enabled'=>1]],['term'=> ['indent_status'=>'IN']]]];
	
			if(!empty($object->experience)):
				$range	=	$object->experience;
				if($range	== "25+"):
					$params['body']['query']['bool']['must'][2]['bool']['should'][0]['bool']['must'][0]	=	['range'=>	[ 'min_years' => [ 'gt' => (int)25]]];
				else:
					$params['body']['query']['bool']['must'][2]['bool']['should'][0]['bool']['must'][0]	=	['range'=>	[ 'min_years' => [ 'lte' => (int)$range]]];
					$params['body']['query']['bool']['must'][2]['bool']['should'][0]['bool']['must'][1]	=	['range'=>	[ 'max_years' => [ 'gte' => (int)$range]]];
				endif;
			endif;
			
			if (!empty($object->query)):
				
				$query	=	['nested'	=>	[	'path' => 'skills',
												'query'	=>  ['bool' => ['must' => [ ['exists' => ['field'=>'skills.skillname']] ,
																					['match' => ['skills.skillname' => $object->query ]]]
												]]]];
				array_push($params['body']['query']['bool']['must'], $query);
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
	
			if(!empty($object->filter)):
				$params['body']['sort']	=	[['created_on' => ['order'	=>	'desc']], ['ijp_id' => ['order' => 'desc']]];		
			elseif(!empty($object->most_viewed)):
				$params['body']['sort']	=	[['views' => ['order'	=>	'desc']],['pinned' => ['order'	=>	'desc']], ['created_on' => ['order' => 'desc']],['ijp_id' => ['order' => 'desc']]];		
			else:
				$params['body']['sort']	=	[['pinned' => ['order'	=>	'desc']],['created_on' => ['order'	=>	'desc']], ['ijp_id' => ['order' => 'desc']]];
			endif;

			if (!empty($object->query)):
				$params['body']['sort']	=	['skills.skill_name' => ['order'	=>	'asc']];
			endif;

			$results	=	$this->es_search($params);	
			$rObject	=	(object)['count' => (int)0, 'ijps' => []];
			if ($results['hits']['total']>0):
				$rObject->count	=	(int)$results['hits']['total'];
				$rObject->ijps	=	$this->setOutput($results,$object);
			endif;
			return $rObject;
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}
	
	//tallint ijps functions
	
	public function insert($object){
		try
		{
			$params = [];
			
			$params['body']  		= 	[	'ijp_id'					=>	(int)$object->ijp_id,
											'title'						=>	(string)$this->common->entityEncode($object->title),
											'indent_number'				=>	(string)$object->indent_number,
											'indent_status'				=>	(string)$object->indent_status,
											'employee_type'				=> 	(string)$object->employee_type,
											'business_unit'				=>	(string)$this->common->entityEncode($object->business_unit),
											'so_type'					=>	(string)$this->common->entityEncode($object->so_type),
											'qualification'				=>	(string)$this->common->entityEncode($object->qualification),
											'min_years'					=>	(string)$object->min_years,
											'max_years'					=>	(string)$object->max_years,
											'description'				=>	(string)$this->common->entityEncode($object->description),
											'referral'					=>	(int)$object->referral,
											'expiry_on'					=>	(string)$object->expiry_on,
											'is_notification_sent' 		=>	(int)$object->is_notification_sent,
											'created_on'				=>	(string)$object->created_on,
											'updated_on' 				=> 	(string)$object->updated_on ,
											'pinned' 					=>	(int)$object->pinned,
											'talk_to_us' 				=>	(int)$object->talk_to_us,
											'views' 					=>	(int)0,
											'skills' 					=>	[],
											'enabled'					=>	(int)$object->enabled,
											'geographies'				=>	(array)$object->geographies,
											'locations'					=>	(array)$object->locations,
											'functions'					=>	(array)$object->functions,
											'levels'					=>	(array)$object->levels,
											'layers'					=>	(array)$object->layers,
											'target_geographies'		=>	(array)$object->target_geographies,
											'target_locations'			=>	(array)$object->target_locations,
											'target_functions'			=>	(array)$object->target_functions,
											'target_levels'				=>	(array)$object->target_levels
										];
			
			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->ijp_id;
			return $this->es_index($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function update($object){
		try
		{
			$params 		= 	[];
			$params['body']	= 	[	'doc' 		=>	[	'ijp_id'					=>	(int)$object->ijp_id,
														'title'						=>	(string)$this->common->entityEncode($object->title),
														'indent_number'				=>	(string)$object->indent_number,
														'indent_status'				=>	(string)$object->indent_status,
														'employee_type'				=> 	(string)$object->employee_type,
														'business_unit'				=>	(string)$this->common->entityEncode($object->business_unit),
														'so_type'					=>	(string)$this->common->entityEncode($object->so_type),
														'min_years'					=>	(string)$object->min_years,
														'max_years'					=>	(string)$object->max_years,
														'description'				=>	(string)$this->common->entityEncode($object->description),
														'qualification'				=>	(string)$this->common->entityEncode($object->qualification),
														'referral'					=>	(int)$object->referral,
														'expiry_on'					=>	(string)$object->expiry_on,
														'is_notification_sent' 		=>	(int)$object->is_notification_sent,
														'created_on'				=>	(string)$object->created_on,
														'updated_on' 				=> 	(string)$object->updated_on,
														'pinned' 					=>	(int)$object->pinned,
														'views' 					=>	(int)$object->views,
														'talk_to_us' 				=>	(int)$object->talk_to_us,
														'skills' 					=>	[],
														'enabled'					=>	(int)$object->enabled,
														'geographies'				=>	(array)$object->geographies,
														'locations'					=>	(array)$object->locations,
														'functions'					=>	(array)$object->functions,
														'levels'					=>	(array)$object->levels,
														'layers'					=>	(array)$object->layers,
														'target_geographies'		=>	(array)$object->target_geographies,
														'target_locations'			=>	(array)$object->target_locations,
														'target_functions'			=>	(array)$object->target_functions,
														'target_levels'				=>	(array)$object->target_levels
														],
					'doc_as_upsert'	=>	true];
			
			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	(int)$object->ijp_id;
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getIjpsByIds($ids){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['size'] 	= 	1000;
			$params['scroll']	=	'30s';
			$params['_source']	=	'ijp_id,title,indent_number,indent_status';
			$params['body']['query']['ids']		=  ['values' => (array)array_values($ids)];
			$results	=	$this->es_search($params);
			$rObject	=	[];
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						$row	 =	(object)$object['_source'];
						$rObject[$row->ijp_id]= $row;
					endforeach;
				endif;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return $rObject;
		}
		catch(Exception $e)
		{	$this->es_error($e);   }
	}
 
}
?>