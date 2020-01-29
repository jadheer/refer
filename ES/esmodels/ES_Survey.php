<?php
class ES_Survey extends ESSource
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
	
	public function getSurveys($object) {
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	[	'must' => [	['term' => ['enabled'=>(int)1]],
																	['term' => ['published'=>(int)1]],
																	['range'=> ['start_datetime' => ['lte' => (string)date('Y-m-d H:i:s') , "format"=> "yyyy-MM-dd HH:mm:ss"]]]
			]];
			
			$params['body']['query']['bool']['filter']	=	 [ 		['term' =>	['geographies'	=>	$object->employee->geography->geography_id]],
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
				foreach ($results['hits']['hits'] as $survey_obj):
					if (!empty($survey_obj['_source'])):
						$row	=	(object)$survey_obj['_source'];
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
			
			$robject->survey_id        	=  	(int)$row->survey_id;
			$robject->title       		=  	(string)$this->common->entityDecode($row->title);
			$robject->question_count    =  	count($row->questions);
			$robject->questions     	=   [];
			
			foreach($row->questions as $key => $question):
				$obj  				=	new stdClass();
				$question			=	(object)$question;	
				$obj->question_id   =	(int)$question->question_id;
				$obj->question		=	(string)$this->common->entityDecode($question->question);
				$obj->type			=	(string)$question->type;
				$obj->compulsory 	=	(boolean)$question->compulsory;
				$obj->picture		= 	(object)[ 	'base_url'    =>  (string)_AWS_URL._SURVEYS_IMAGES_DIR,
													'image_path'  =>  (string)$question->image];
				/* to set option type for a question */
				if($obj->type=="T"):
					$obj->type		=	"text";
					$obj->text 		= 	(string)"";
				elseif($obj->type=="R"):
					$obj->type		=	"rating";
					$obj->rating 	=	(int)0;
				else:
					$obj->type		=	($obj->type=="S")?"single":"multiple";
					$obj->options 	=	[];
					/*set the options type*/
					foreach($question->options as $key => $option ):
						$opt  				= 	new stdClass();
						$option				=	(object)$option;	
						$opt->option_id   	=	(int)$option->option_id;
						$opt->option_text  	=	(string)$this->common->entityDecode($option->option_text);
						$opt->selected		=	(boolean)false;
						array_push($obj->options, $opt);
					endforeach;
				endif;
				array_push($robject->questions , $obj);
			endforeach;
	
			$robject->start_datetime 	= 	(string)$row->start_datetime;
			$robject->end_datetime   	= 	(string)$row->end_datetime;
			$robject->promo_image    	= 	(object)[  'base_url'    =>  (string)_AWS_URL._SURVEYS_IMAGES_DIR,
													   'image_path'  =>  (string)$row->promo_image];
			$robject->geo_locations		=	(array)$componentMapping->getGeoLocations(implode(",",$row->geographies),implode(",",$row->locations));
			$robject->functions			=	(array)$componentMapping->getFunctions(implode(",",$row->functions));
			$robject->level_layers		=	(array)$componentMapping->getLevelLayers(implode(",",$row->levels),implode(",",$row->layers));
			$robject->participated 		=	(boolean)(in_array($object->employee->employee_id,$row->participants));
			$robject->active         	= 	(boolean)(date('Y-m-d H:i:s',strtotime($row->end_datetime)) >= date('Y-m-d H:i:s'));
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getSurveyById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->survey_id;
			
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
	
	public function addParticipant($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->survey_id;
			
			$params['body']['script']['inline']	=	'ctx._source.participants.add(params.participant)';
			$params['body']['script']['params']	=	['participant' => (int)$object->employee->employee_id];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function deleteParticipant($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->survey_id;
			
			$params['body']['script']['inline']	=	"ctx._source.participants.removeAll(Collections.singleton(params.participant))";
			$params['body']['script']['params']	=	['participant' => (int)$object->employee->employee_id];
			
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