<?php
class ES_Customer_accolade extends ESSource
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
	
	
	public function getAccoladeById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->accolade_id;
			
			$result			=	$this->es_get($params);
			$objEmployees	=	[];
			if (!empty($result['_source'])):
				$row		=	(object)$result['_source'];
				$empIds		=	$row->tagged_employees;
				if (!empty($result)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
				endif;
			
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Level		=	new ES_Level();
				$ES_Function	=	new ES_Function();
				
				$ObjGeos	=	$ES_Geography->getGeographies();
				$Objlocs	=	$ES_Location->getLocations();
				$ObjLevs	=	$ES_Level->getLevels();
				$ObjFuncs	=	$ES_Function->getFunctions();
				
				$ObjMaster	=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs];
				$robject	=	$this->setObject($row , $object, $ObjMaster ,$objEmployees);
			endif;
			return !empty($robject)?$robject:false;
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	
	
	public function getAccolades($object){
		try{
			$params = [];
			$params['from']  	= 	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]],
																['term' => ['published'=>(int)1]]]];
			
			$params['body']['sort']	=	[['date' => ['order' => 'desc']], ['accolade_id' => ['order' => 'desc']]];
			
			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results,$object){
		try{
			$arr 		=	[];
			$tag_arr	=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $accolade_obj):
					if (!empty($accolade_obj['_source'])):
						$row			=	(object)$accolade_obj['_source'];
						$tag_arr		=	array_merge($tag_arr,$row->tagged_employees);
					endif;
				endforeach;
				$empIds			=	array_unique($tag_arr);
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
				
				foreach ($results['hits']['hits'] as $accolade_obj):
					if (!empty($accolade_obj['_source'])):
						$row		=	(object)$accolade_obj['_source'];
						array_push($arr, $this->setObject($row, $object, $ObjMaster,$objEmployees));
					endif;
				endforeach;
			endif;
			return $arr;
		}catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	private function setObject($row, $object, $ObjMaster,$objEmployees){
		try{
			$robject					=	new stdClass();
			$robject->id				=	(int)$row->accolade_id;
			$robject->description		=	(string)$this->common->entityDecode($row->description);
			$robject->title				=	(string)$this->common->entityDecode($row->title);
			$robject->author			=	(string)$this->common->entityDecode($row->author);
			$robject->company_logo		=	(object)[	'base_url'		=>	(string)_AWS_URL._ACCOLADES_IMAGES_DIR,
														'image_path'	=>	(string)$row->logo_path];
			$robject->pub_datetime 		=	(string)$row->pub_datetime;
			$robject->date		 		=	(string)$row->date;
			$robject->tags				=	[];
			foreach ($row->tagged_employees as $key => $tag):
				if(isset($objEmployees[$tag]) && $objEmployees[$tag]['enabled']==1):
					$emp					=	new stdClass();
					$emp->employee_id 		= 	(int)$objEmployees[$tag]['employee_id'];
					$first_name				=	$objEmployees[$tag]['first_name']?$objEmployees[$tag]['first_name']." ":"";
					$middle_name			=	$objEmployees[$tag]['middle_name']?$objEmployees[$tag]['middle_name']." ":"";
					$last_name				=	$objEmployees[$tag]['last_name']??"";
					$emp->employee_name		=	(string)$first_name.$middle_name.$last_name;
					$emp->employee_code		=	(string)$objEmployees[$tag]['employee_code'];
					$emp->profile_picture	=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
															'image_path'	=>	(string)$objEmployees[$tag]['profile_picture']];
					
					$emp->geography	=	new stdClass();
					$Key	=	array_search($objEmployees[$tag]['geography_id'], array_column($ObjMaster->geographies, 'geography_id'));
					if ($Key!==false):
					$emp->geography	= (object)['geography_id' => (int)$ObjMaster->geographies[$Key]->geography_id,
												'title' => (string)$ObjMaster->geographies[$Key]->title];
					endif;
					
					$emp->location	=	new stdClass();
					$Key	=	array_search($objEmployees[$tag]['location_id'], array_column($ObjMaster->locations, 'location_id'));
					if ($Key!==false):
					$emp->location	= (object)[	'location_id' => (int)$ObjMaster->locations[$Key]->location_id,
												'title' => (string)$ObjMaster->locations[$Key]->title];
					endif;
					
					$emp->function	=	new stdClass();
					$Key	=	array_search($objEmployees[$tag]['function_id'], array_column($ObjMaster->functions, 'function_id'));
					if ($Key!==false):
					$emp->function	= (object)[	'function_id' => (int)$ObjMaster->functions[$Key]->function_id,
												'title' => (string)$ObjMaster->functions[$Key]->title];
					endif;
					
					$emp->level		=	new stdClass();
					$Key	=	array_search($objEmployees[$tag]['level_id'], array_column($ObjMaster->levels, 'level_id'));
					if ($Key!==false):
					$emp->level		= (object)[	'level_id' => (int)$ObjMaster->levels[$Key]->level_id,
												'title' => (string)$ObjMaster->levels[$Key]->title];
					endif;
					
					$emp->layer		=	new stdClass();
					$Key	=	array_search($objEmployees[$tag]['layer_id'], array_column($ObjMaster->layers, 'layer_id'));
					if ($Key!==false):
						$emp->layer		= (object)[	'layer_id' => (int)$ObjMaster->layers[$Key]->layer_id,
													'title' 	=> (string)$ObjMaster->layers[$Key]->title];
					endif;
					
					array_push($robject->tags, $emp);
				endif;
			endforeach;
			return $robject;
			
		}catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
}
?>