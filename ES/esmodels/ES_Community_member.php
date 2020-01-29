<?php
class ES_Community_member extends ESSource
{
	var $index;
	var $type;
	public function __construct()
	{
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this)))."s";
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
	
	public function __destruct()
	{	parent::__destruct();	}
	
	
	public function addCommunityMember($object){
		try
		{
			$params = [];	
			$params['body']  		= 	[	'member_id'		=>	(int)$object->member_id,
											'employee_id'	=>	(int)$object->employee_id,
											'community_id'	=>	(int)$object->community_id,
											'joined_on'		=>	(string)$object->joined_on,
											'approved'		=>	(int)0,
											'enabled'		=>	(int)1,
											'rejected'		=>	(int)0];
			
			
			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->member_id;
			return  $this->es_index($params);
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}
	
	public function deleteCommunityMember($object){
		try
		{
			$params = [];
			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['body']['query']['bool']	=	[ 'must' => [	['term' 	=> 	['community_id'	=>	(int)$object->community_id]],
																	['term' 	=> 	['employee_id'	=>	(int)$object->employee_id]],
													]];
			return $this->es_deleteByQuery($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function checkMembership($object){
		try
		{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$params['size']  	= 	1;
			$params['body']['query']['bool']	=	[ 'must' => [	['term' 	=> 	['community_id'	=>	(int)$object->community_id]],
																	['term' 	=> 	['employee_id'	=>	(int)$object->employee_id]]]];
			$result	=	$this->es_search($params);
			return  (!empty($result['hits']['hits'])?(object)$result['hits']['hits'][0]['_source']:[]);
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}

	public function getCommunityMembersById($object){
		try
		{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['body']['query']['bool']	=	[ 'must' => [	['term' 	=> 	['community_id'	=>	(int)$object->community_id]],
																	['term' 	=> 	['rejected'	=>	(int)0]],
																	['term' 	=> 	['approved'	=>	(int)1]],
																	['term' 	=> 	['enabled'	=>	(int)1]]
														]];
			$params['body']['sort']				=	[['employee_id' => ['order' => 'desc']]];
			$results	=	$this->es_search($params);
			$arr		=	[];
			if ($results['hits']['total']>0):
				$empIds		=	[];
				/*get the employee ids from community array */
				foreach ($results['hits']['hits'] as $member_obj):
					if (!empty($member_obj['_source'])):
						$row	=	(object)$member_obj['_source'];
						array_push($empIds, $row->employee_id);
					endif;
				endforeach;
		
				/* get employee information */
				$empIds			=	array_unique($empIds);
				$objEmployees	=	[];
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
				endif;
				
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Level		=	new ES_Level();
				$ES_Function	=	new ES_Function();
				$ES_Layer		=	new ES_Layer();
				
				$ObjGeos		=	$ES_Geography->getGeographies();
				$Objlocs		=	$ES_Location->getLocations();
				$ObjLevs		=	$ES_Level->getLevels();
				$ObjFuncs		=	$ES_Function->getFunctions();
				$ObjLays		=	$ES_Layer->getLayers();
				
				$ObjMaster		=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers' => $ObjLays ];
				
				foreach ($results['hits']['hits'] as $member_obj):
					if (!empty($member_obj['_source']) && !empty($objEmployees)):
						$row		=	(object)$member_obj['_source'];
						if ($objEmployees[$row->employee_id]['enabled'] == 1):
							array_push($arr, $this->setOutput($row,$object,$objEmployees[$row->employee_id],$ObjMaster));
						endif;
					endif;
				endforeach;
			endif;
			return  $arr;
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}
	
	public function getAllCommunityMembers($object){
		try
		{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$params['size']  	= 	1000;
			$params['scroll']	=	'30s';
			$params['body']['query']['bool']	=	[ 'must' => [	['term' 	=> 	['community_id'	=>	(int)$object->community_id]],
																	['term' 	=> 	['rejected'	=>	(int)0]],
																	['term' 	=> 	['approved'	=>	(int)1]],
																	['term' 	=> 	['enabled'	=>	(int)1]]
													]];
			$params['body']['query']['bool']['must_not']	=	['term' 	=> ['employee_id'	=>	(int)$object->employee_id]];
			$params['body']['sort']				=	[['employee_id' => ['order' => 'desc']]];
			$results	=	$this->es_search($params);
			$arr		=	[];
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				$empIds		=	[];
				/*get the employee ids from community array */
				foreach ($results['hits']['hits'] as $member_obj):
					if (!empty($member_obj['_source'])):
						$row	=	(object)$member_obj['_source'];
						array_push($empIds, $row->employee_id);
					endif;
				endforeach;
					
				/* get employee information */
				$empIds			=	array_unique($empIds);
				$objEmployees	=	[];
				if (!empty($empIds)):
					$ES_Employee		=		new ES_Employee();
					$object->ids		=		$empIds;
					$objEmployees		=		$ES_Employee->getEmployeeByIdsAndFilters($object);
					$objEmployees		=		array_column($objEmployees,NULL,"employee_id");
				endif;
				
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Level		=	new ES_Level();
				$ES_Function	=	new ES_Function();
				$ES_Layer		=	new ES_Layer();
				
				$ObjGeos		=	$ES_Geography->getGeographies();
				$Objlocs		=	$ES_Location->getLocations();
				$ObjLevs		=	$ES_Level->getLevels();
				$ObjFuncs		=	$ES_Function->getFunctions();
				$ObjLays		=	$ES_Layer->getLayers();
				
				$ObjMaster		=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers' => $ObjLays ];
				
				foreach ($results['hits']['hits'] as $member_obj):
					if (!empty($member_obj['_source']) && !empty($objEmployees)):
						$row		=	(object)$member_obj['_source'];
						if (isset($objEmployees[$row->employee_id]) && $objEmployees[$row->employee_id]['enabled'] == 1):
							array_push($arr, $this->setOutput($row,$object,$objEmployees[$row->employee_id],$ObjMaster));
						endif;
					endif;
				endforeach;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			
			$sort_arr 		= 	array_column($arr,NULL,'employee_name');
			ksort($sort_arr);
			return array_values($sort_arr);
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}	

	private function setOutput($row,$object,$objEmployee,$ObjMaster){
		$robject					=	new stdClass();
		$robject->member_id			=	(int)$row->member_id;
		$robject->joined_on			=	(string)$row->joined_on;
		$robject->employee_id		=	(int)$row->employee_id;
		$first_name					=	$objEmployee['first_name']?$this->common->entityDecode($objEmployee['first_name'])." ":"";
		$middle_name				=	$objEmployee['middle_name']?$this->common->entityDecode($objEmployee['middle_name'])." ":"";
		$last_name					=	$objEmployee['last_name']?$this->common->entityDecode($objEmployee['last_name']):"";
		$robject->first_name		=	$objEmployee['first_name']?$this->common->entityDecode($objEmployee['first_name']):"";
		$robject->middle_name		=	$objEmployee['middle_name']?$this->common->entityDecode($objEmployee['middle_name']):"";
		$robject->last_name			=	$objEmployee['last_name']?$this->common->entityDecode($objEmployee['last_name']):"";
		$robject->employee_name		=	(string)$first_name.$middle_name.$last_name;
		$robject->employee_code		=	(string)$objEmployee['employee_code'];
		$robject->profile_picture	=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
													'image_path'	=>	(string)$objEmployee['profile_picture']];
		
		$robject->geography	=	new stdClass();
		$Key	=	array_search($objEmployee['geography_id'], array_column($ObjMaster->geographies, 'geography_id'));
		if ($Key!==false):
			$robject->geography	= (object)['geography_id' => (int)$ObjMaster->geographies[$Key]->geography_id,
											'title' => (string)$ObjMaster->geographies[$Key]->title];
		endif;
		
		$robject->location	=	new stdClass();
		$Key	=	array_search($objEmployee['location_id'], array_column($ObjMaster->locations, 'location_id'));
		if ($Key!==false):
			$robject->location	= (object)[	'location_id' => (int)$ObjMaster->locations[$Key]->location_id,
											'title' => (string)$ObjMaster->locations[$Key]->title];
		endif;
		
		$robject->function	=	new stdClass();
		$Key	=	array_search($objEmployee['function_id'], array_column($ObjMaster->functions, 'function_id'));
		if ($Key!==false):
			$robject->function	= (object)[	'function_id' => (int)$ObjMaster->functions[$Key]->function_id,
											'title' => (string)$ObjMaster->functions[$Key]->title];
		endif;
		
		$robject->level		=	new stdClass();
		$Key	=	array_search($objEmployee['level_id'], array_column($ObjMaster->levels, 'level_id'));
		if ($Key!==false):
			$robject->level		= (object)[	'level_id' => (int)$ObjMaster->levels[$Key]->level_id,
											'title' => (string)$ObjMaster->levels[$Key]->title];
		endif;		
		
		$robject->layer		=	new stdClass();
		$Key	=	array_search($objEmployee['layer_id'], array_column($ObjMaster->layers , 'layer_id'));
		if ($Key!==false):
			$robject->layer		= (object)[	'layer_id' => (int)$ObjMaster->layers[$Key]->layer_id,
											'title' => (string)$ObjMaster->layers[$Key]->title];
		endif;	
		
		return $robject;
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
	
	
	public function getCommunityMembers($object,$community_ids){
		try
		{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$params['size']  	= 	1000;
			$params['scroll']	=	'30s';
			$params['body']['query']['bool']['filter'] 	= 	[['terms' 	=> 	['community_id'	=> $community_ids]]];
			
			$results	=	$this->es_search($params);
			$arr		=	[];
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				$empIds		=	[];
				/*get the employee ids from community array */
				foreach ($results['hits']['hits'] as $member_obj):
					if (!empty($member_obj['_source'])):
						$row	=	(object)$member_obj['_source'];
						array_push($empIds, $row->employee_id);
					endif;
				endforeach;
				
				/* get employee information */
				$empIds			=	array_unique($empIds);
				$objEmployees	=	[];
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
				endif;
			
				foreach ($results['hits']['hits'] as $member_obj):
					if (!empty($member_obj['_source']) && !empty($objEmployees)):
					$row		=	(object)$member_obj['_source'];
						if ($objEmployees[$row->employee_id]['enabled'] == 1):
							/*approved members */
							if($row->approved ==1 && $row->rejected==0 && $row->enabled==1):
								$arr[$row->community_id]["total_members"][]	=	$row;
							endif;
							if($object->employee_id == $row->employee_id):
								$arr[$row->community_id]["member"] =	$row;
							endif;
						endif;
					endif;
				endforeach;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return  $arr;
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}	
}
?>