<?php
class ES_Poll extends ESSource
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
	
	public function getPolls($object) {
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']				=	[	'must' => [	['term' => ['enabled'=>1]],
																				['term' => ['published'=>1]],
																				['range'=> ['start_datetime' => ['lte' => (string)date('Y-m-d H:i:s') , "format"=> "yyyy-MM-dd HH:mm:ss"]]]
																]];
			
			$params['body']['query']['bool']['filter']			=	[ ['term'	=>	['geographies'	=>	$object->employee->geography->geography_id]],
																	['term'	=>	['locations'	=>	$object->employee->location->location_id]],
																	['term'	=>	['functions'	=>	$object->employee->function->function_id]],
																	['term'	=>	['levels'		=>	$object->employee->level->level_id]],
																	['term'	=>	['layers'		=>	$object->employee->layer->layer_id]]
																	];
			
			$params['body']['sort']	=	[['start_datetime' => ['order'	=>	'desc']], ['end_datetime' => ['order' => 'asc']]];
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
				
				$ObjGeos	=	$ES_Geography->getGeographies();
				$Objlocs	=	$ES_Location->getLocations();
				$ObjLevs	=	$ES_Level->getLevels();
				$ObjFuncs	=	$ES_Function->getFunctions();
				
				$ES_Layer		=	new ES_Layer();
				$ObjLays		=	$ES_Layer->getLayers();
				$ObjMaster		=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers' => $ObjLays ];
				
				$componentMapping	=	new ComponentMapping($ObjMaster);
				foreach ($results['hits']['hits'] as $poll_obj):
					if (!empty($poll_obj['_source'])):
						$row	=	(object)$poll_obj['_source'];
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
			$robject->poll_id        		= 	(int)$row->poll_id;
			$robject->question       		= 	(string)$this->common->entityDecode($row->question);
			$robject->start_datetime 		= 	(string)$row->start_datetime;
			$robject->end_datetime   		= 	(string)$row->end_datetime;
			$robject->active         		= 	(boolean)(date('Y-m-d H:i:s',strtotime($row->end_datetime)) >= date('Y-m-d H:i:s'));
			$robject->promo_image   	 	= 	(object)[ 'base_url'    =>  (string)_AWS_URL._POLLS_IMAGES_DIR,
														  'image_path'  =>  (string)$row->promo_image];
			$robject->geo_locations			=	(array)$componentMapping->getGeoLocations(implode(",",$row->geographies),implode(",",$row->locations));
			$robject->functions				=	(array)$componentMapping->getFunctions(implode(",",$row->functions));
			$robject->level_layers			=	(array)$componentMapping->getLevelLayers(implode(",",$row->levels),implode(",",$row->layers));
			$robject->voted    				= 	(boolean)(!empty($row->votes))?in_array($object->employee_id,array_column($row->votes,"employee_id")):false;
		
			/*Set poll option values with percentage */
			$objOptions       				= 	$row->options;
			$total							=	count($row->votes);
			$totalPer						=	0;
			$count_array					=	array_count_values(array_column($row->votes,"option_id"));
		
			for ($i=0; $i<count($objOptions); $i++):
				$objOptions[$i]['option_id']		=	(int)$objOptions[$i]['option_id'];
				$objOptions[$i]['title']			=	(string)$this->common->entityDecode($objOptions[$i]['option_text']);
				$objOptions[$i]['option_text']		=	(string)$this->common->entityDecode($objOptions[$i]['option_text']);
				$objOptions[$i]['count']			=	(int)0;
				$objOptions[$i]['percentage']		=	(int)0;
				
				if($total>0):
					$objOptions[$i]['count']		=	$count_array[$objOptions[$i]['option_id']]??0;
					$objOptions[$i]['percentage']	=	number_format(($objOptions[$i]['count']*100)/$total, 3);
// 					if ($i==(count($objOptions)-1)):
// 						$objOptions[$i]['percentage']		=	(int)100-$totalPer;
// 					else:
// 						$totalPer	=	$totalPer+$objOptions[$i]['percentage'];
// 					endif;
				endif;
				unset($objOptions[$i]['created_on']);
				unset($objOptions[$i]['updated_on']);
			endfor;

			$robject->results  = (object)['total' => $total, 'options' => (array) $objOptions];
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getPollByPollId($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->poll_id;
			
			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Level		=	new ES_Level();
				$ES_Function	=	new ES_Function();
				
				$ObjGeos		=	$ES_Geography->getGeographies();
				$Objlocs		=	$ES_Location->getLocations();
				$ObjLevs		=	$ES_Level->getLevels();
				$ObjFuncs		=	$ES_Function->getFunctions();
				
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
}
?>