<?php 
class ES_Joinee_AgendaSession  extends ESSource {
	var $index;
	var $type;
	public function __construct(){	
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
    public function __destruct()
	{	parent::__destruct();	}

	public function getSessionById($object) {
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->session_id;
			
			$result	=	$this->es_get($params);
			$objEmployees	=	[];
			$robject		=	new stdClass();
			$ObjMaster		=	[];
			if (!empty($result['_source'])):
				if(!empty($result['_source']['speakers'])):
					$empIds		=	$result['_source']['speakers'];
					if (!empty($empIds)):
						$ES_Employee	=	new ES_Employee();
						$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
					endif;
				endif;
				
				$ObjMaster	=	$this->setMasterObject();
				$robject	=	$this->setObject((object)$result['_source'],$objEmployees,$ObjMaster,$object);
			endif;
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	private function setMasterObject(){
		try{
			$ES_Geography	=	new ES_Geography();
			$ES_Location	=	new ES_Location();
			$ES_Function	=	new ES_Function();
			
			$ObjGeos	=	$ES_Geography->getGeographies();
			$Objlocs	=	$ES_Location->getLocations();
			$ObjFuncs	=	$ES_Function->getFunctions();
			
			$ObjMaster	=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'functions' => $ObjFuncs];
			return $ObjMaster;
		}catch(Exception $e)
		{	$this->es_error($e);	}
	}
	

	public function getAgendaSession($object){
		try{
			$params = [];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['type'] 	= 	$this->type;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']['must'] 		= 	[['term' => ['enabled'=>1]]];
			
			$params['body']['query']['bool']['filter']		=	 [ 		['term' =>	['geographies'	=>	$object->employee->geography->geography_id]],
																		['term'	=>	['locations'	=>	$object->employee->location->location_id]],
																		['term'	=>	['functions'	=>	$object->employee->function->function_id]]];
			
			$params['body']['sort']							=	[['start_datetime'=>['order'=> 'asc']],['session_id'=>['order'=>'asc']] , ['questions.asked_on' => ['order' => 'desc']], ['questions.question_id' => ['order' => 'asc']]];
			
			
			
			$results = $this->setOutput($this->es_search($params),$object);
			return $results;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getRateSession($object){
		try{
			$params = [];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['type'] 	= 	$this->type;
			$params['index'] 	= 	$this->index;
			
			$params['_source']	=	['session_id', 'title', 'summary', 'created_on', 'start_datetime', 'end_datetime','ratings'];
			
			$params['body']['query']['bool']['must'] 		= 	[['term' => ['enabled'=>1]]];
			
			$params['body']['query']['bool']['filter']		=	 [ 		['term' =>	['geographies'	=>	$object->employee->geography->geography_id]],
																		['term'	=>	['locations'	=>	$object->employee->location->location_id]],
																		['term'	=>	['functions'	=>	$object->employee->function->function_id]]];
			
			$params['body']['sort']							=	[['start_datetime'=>['order'=> 'asc']],['session_id'=>['order'=>'asc']]];
			
			$arr		=	[];
			$results 	= 	$this->es_search($params);
			
			foreach ($results['hits']['hits'] as $result):
				$row 							=	(object)$result['_source'];
				$robject						=	new stdClass();
				$robject->session_id    		= 	(int)$row->session_id;
				$robject->title					= 	(string)$this->common->entityDecode($row->title);
				$robject->summary				= 	(string)$this->common->entityDecode($row->summary);
				$robject->created_on			= 	(string)$row->created_on;
				$robject->start_datetime		= 	(string)$row->start_datetime;
				$robject->end_datetime			= 	(string)$row->end_datetime;
				
				//ratings
				if(!empty($row->ratings)):
					$ratings			=	array_column($row->ratings,"rate","employee_id");
					$row->employee_id	=	isset($ratings[$object->employee->employee_id])?$object->employee->employee_id:0;
					$row->rate_value	=	isset($ratings[$object->employee->employee_id])?$ratings[$object->employee->employee_id]:0;
					$row->average_rate	=	array_sum(array_column($row->ratings,"rate"))/count($row->ratings);
				endif;
				
				$robject->ratings				=	(object)[	"is_rated" 		=> 	(boolean)(!empty($row->employee_id)?true:false),
																"can_rate" 		=> 	(boolean) (( strtotime(date("Y-m-d")) > strtotime("+"._JOINEES_AGENDA_RATE_UPTO." day", strtotime($row->end_datetime)))? false:true),
																"rate_value" 	=> 	(int)(!empty($row->rate_value)?$row->rate_value:0),
																"avg_rate" 		=> 	(string)(!empty($row->average_rate)?round($row->average_rate,1):"")
															];
				array_push($arr,$robject);
			endforeach;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getInfoSession($object){
		try{
			$params = [];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['type'] 	= 	$this->type;
			$params['index'] 	= 	$this->index;
			
			$params['_source']								=	['session_id', 'title', 'summary', 'created_on', 'start_datetime', 'end_datetime','pdf_links','youtube_links','text_links'];
			$params['body']['query']['bool']['must'] 		= 	[['term' => ['enabled'=>1]]];
			$params['body']['query']['bool']['filter']		=	 [ 		['term' =>	['geographies'	=>	$object->employee->geography->geography_id]],
																		['term'	=>	['locations'	=>	$object->employee->location->location_id]],
																		['term'	=>	['functions'	=>	$object->employee->function->function_id]]];
			
			$params['body']['sort']							=	[['start_datetime'=>['order'=> 'asc']],['session_id'=>['order'=>'asc']]];
			
			$arr		=	[];
			$results 	= 	$this->es_search($params);
			
			foreach ($results['hits']['hits'] as $result):
				$row 	=	(object)$result['_source'];
				if(!empty($row->text_links) || !empty($row->pdf_links) ||  !empty($row->youtube_links)):
					$robject						=	new stdClass();
					$robject->session_id    		= 	(int)$row->session_id;
					$robject->title					= 	(string)$this->common->entityDecode($row->title);
					$robject->summary				= 	(string)$this->common->entityDecode($row->summary);
					$robject->created_on			= 	(string)$row->created_on;
					$robject->start_datetime		= 	(string)$row->start_datetime;
					$robject->end_datetime			= 	(string)$row->end_datetime;
					$robject->media					=	(object)[ 	"pdf" =>		$row->pdf_links,
																	"text" =>		$row->text_links,
																	"youtube" => 	$row->youtube_links
													];
					array_push($arr,$robject);
				endif;
			endforeach;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getQuestionSession($object){
		try{
			$params = [];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['type'] 	= 	$this->type;
			$params['index'] 	= 	$this->index;
			$params['_source']	=	['session_id', 'title', 'summary', 'created_on', 'start_datetime', 'end_datetime','speakers','questions'];
			
			$params['body']['query']['bool']['must'] 		= 	[['term' => ['enabled'=>1]]];
			
			$params['body']['query']['bool']['filter']		=	 [ 		['term' =>	['geographies'	=>	$object->employee->geography->geography_id]],
																		['term'	=>	['locations'	=>	$object->employee->location->location_id]],
																		['term'	=>	['functions'	=>	$object->employee->function->function_id]]];
			
			$params['body']['sort']							=	[['start_datetime'=>['order'=> 'asc']],['session_id'=>['order'=>'asc']] , ['questions.asked_on' => ['order' => 'desc']], ['questions.question_id' => ['order' => 'asc']]];
			
			
			
			$results 	= 	$this->es_search($params);
			$empIds		=	[];
			foreach ($results['hits']['hits'] as $result):
				/*get the employee ids from community array */
				if (!empty($result['_source'])):
					$row		=	(object)$result['_source'];
					if(!empty($row->speakers)):
						$empIds		=	array_merge($empIds,$row->speakers);
					endif;
					if(!empty($row->questions)):
						$qempIds	=	array_column($row->questions, "employee_id");
						$empIds		=	array_merge($empIds,$qempIds);
					endif;
				endif;
			endforeach;
			
			$ObjMaster	=	$this->setMasterObject();
			
			/* get employee information */
			$empIds			=	array_unique($empIds);
			$objEmployees	=	[];
			if (!empty($empIds)):
				$ES_Employee	=	new ES_Employee();
				$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
			endif;
			$arr	=	[];
			foreach ($results['hits']['hits'] as $obj):
				if (!empty($obj['_source'])):
					$row		=	(object)$obj['_source'];
					if(!empty($row->questions)):
						$robject	=	new stdClass();
						$robject->session_id    		= 	(int)$row->session_id;
						$robject->title					= 	(string)$this->common->entityDecode($row->title);
						$robject->summary				= 	(string)$this->common->entityDecode($row->summary);
						$robject->created_on			= 	(string)$row->created_on;
						$robject->start_datetime		= 	(string)$row->start_datetime;
						$robject->end_datetime			= 	(string)$row->end_datetime;
						$robject->speakers				=	[];	
						if(!empty($row->speakers)):
							foreach ($row->speakers	as $key => $id):
								if(isset($objEmployees[$id]) && $objEmployees[$id]['enabled']==1):
									$emp	=	$this->setSpeakerObject($objEmployees,$id,$ObjMaster);	
									$emp->questions			=	[];
									foreach ($row->questions as $key => $question):
										$question	=	(object)$question;
										if($question->speaker_id == $id	):
											$quest						=	new stdClass();
											$quest->question_id 		=	(int)$question->question_id;
											$quest->question			=	(string)$this->common->entityDecode($question->question);
											$quest->asked_on 			=	(string)$question->asked_on;
											$quest->employee			=	$this->setSpeakerObject($objEmployees,$question->employee_id,$ObjMaster);
											$quest->speaker_id			=	(int)$question->speaker_id;
											array_push($emp->questions , $quest);
										endif;
									endforeach;
									if(!empty($emp->questions)):
										array_push($robject->speakers, $emp);
									endif;
								endif;
							endforeach;
						endif;
						array_push($arr,$robject);
					endif;
				endif;
			endforeach;
			return $arr;
			}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results,$aobject){
		try{
			$empIds	=	[];
			$objects	=	[];
			if ($results['hits']['total']>0):
				/*get all speaker details*/
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source']['speakers'])):
						$empIds =	array_merge($empIds, $obj['_source']['speakers']);
					endif;
					if (!empty($obj['_source']['questions'])):
						$qempIds	=	array_column($obj['_source']['questions'], "employee_id");
						$empIds 	=	array_merge($empIds, $qempIds);
					endif;
				endforeach;
				
				$empIds		=	array_unique($empIds);
				
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
				endif;
				
				$ObjMaster	=	$this->setMasterObject();
				
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row		=	(object)$obj['_source'];
						array_push($objects, $this->setObject($row, $objEmployees,$ObjMaster,$aobject));
					endif;
				endforeach;
			endif;
			return $objects;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setObject($row, $objEmployees,$ObjMaster,$aobject){
		try{
			$robject	=	new stdClass();
			$robject->session_id    		= 	(int)$row->session_id;
			$robject->title					= 	(string)$this->common->entityDecode($row->title);
			$robject->summary				= 	(string)$this->common->entityDecode($row->summary);
			$robject->created_on			= 	(string)$row->created_on;
			$robject->start_datetime		= 	(string)$row->start_datetime;
			$robject->end_datetime			= 	(string)$row->end_datetime;
			$robject->speakers				=	[];
	
			if(!empty($row->speakers)):
				foreach ($row->speakers	as $key => $id):
					if(isset($objEmployees[$id]) && $objEmployees[$id]['enabled']==1):
						$emp	=	$this->setSpeakerObject($objEmployees,$id,$ObjMaster);
						$emp->questions			=	[];
						foreach ($row->questions as $key => $question):
							$question	=	(object)$question;
							if($question->speaker_id == $id	):	
								$quest						=	new stdClass();
								$quest->question_id 		=	(int)$question->question_id;
								$quest->question			=	(string)$this->common->entityDecode($question->question);
								$quest->asked_on 			=	(string)$question->asked_on;
								$quest->employee			=	$this->setSpeakerObject($objEmployees,$question->employee_id,$ObjMaster);
								$quest->speaker_id			=	(int)$question->speaker_id;
								array_push($emp->questions , $quest);
							endif;
						endforeach;
						array_push($robject->speakers, $emp);
					endif;
				endforeach;
			endif;
			
			//media object
			$robject->media				=	(object)[ 	"pdf" =>		$row->pdf_links,
														"text" =>		$row->text_links,
														"youtube" => 	$row->youtube_links
													];
			//ratings
			if(!empty($row->ratings)):
				$ratings			=	array_column($row->ratings,"rate","employee_id");
				$row->employee_id	=	isset($ratings[$aobject->employee->employee_id])?$aobject->employee->employee_id:0;
				$row->rate_value	=	isset($ratings[$aobject->employee->employee_id])?$ratings[$aobject->employee->employee_id]:0;
				$row->average_rate	=	array_sum(array_column($row->ratings,"rate"))/count($row->ratings);
				
			endif;
			
			$robject->ratings			=	(object)[	"is_rated" 		=> 	(boolean)(!empty($row->employee_id)?true:false),
														"can_rate" 		=> 	(boolean) (( strtotime(date("Y-m-d")) > strtotime("+"._JOINEES_AGENDA_RATE_UPTO." day", strtotime($row->end_datetime)))? false:true),
														"rate_value" 	=> 	(int)(!empty($row->rate_value)?$row->rate_value:0),
														"avg_rate" 		=> 	(string)(!empty($row->average_rate)?round($row->average_rate,1):"")
													];
		
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	

	public function addQuestion($object){
		try{
			$params = [];
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->session_id;
			
			$doc 	= 	(object)[	'question_id'		=>	(int)$object->question_id,
									'employee_id'		=>	(int)$object->employee_id,
									'speaker_id'		=>	(int)$object->speaker_id,
									'question'			=>	(string)$this->common->entityEncode($object->question),
									'asked_on'			=>	(string)$object->asked_on,
									'enabled'			=>	(int)$object->enabled];
							
			$params['body']['script']['inline']	=	'ctx._source.questions.add(params.questions)';
			$params['body']['script']['params']	=	['questions' => $doc];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function rateSession($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->session_id;
			
			$doc 	= 	(object)[	'employee_id'		=>	(int)$object->employee_id,
									'rate'				=>	(int)$object->rate,		
									'rate_on'			=>	(string)$object->rate_on];
			
			$params['body']['script']['inline']	=	'ctx._source.ratings.add(params.ratings)';
			$params['body']['script']['params']	=	['ratings' => $doc];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function deleteRate($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->session_id;
			
			$params['body']['script']['inline']	=	'for (int i = 0; i < ctx._source.ratings.size(); i++){if(ctx._source.ratings[i].employee_id == params.employee_id){ctx._source.ratings.remove(i);}}';
			$params['body']['script']['params']	=	['employee_id' => (int)$object->employee_id];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	/* for all sessions*/
	public function getAllSessions($object){
		try{
			$params = [];
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['scroll']	=	'30s';
			$params['_source']	=	['session_id', 'title', 'summary', 'created_on', 'start_datetime', 'end_datetime','speakers'];
			
			
			$params['body']['query']['bool']['must'] 		= 	[['term' => ['enabled'=>1]]];
			
			$params['body']['query']['bool']['filter']		=	 [ 	['term' =>	['geographies'	=>	$object->employee->geography->geography_id]],
																	['term'	=>	['locations'	=>	$object->employee->location->location_id]],
																	['term'	=>	['functions'	=>	$object->employee->function->function_id]]];
			
			$params['body']['sort']		=	[['start_datetime'=>['order'=> 'asc']],['session_id'=>['order'=>'asc']]];
			
			$results	=	$this->es_search($params);
			$empIds		=	[];
			$arr		=	[];
			
			$ObjMaster	=	$this->setMasterObject();
			
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				foreach ($results['hits']['hits'] as $result):
					/*get the employee ids from community array */
					if (!empty($result['_source'])):
						$row	=	(object)$result['_source'];
						$empIds	=	array_merge($empIds,$row->speakers);
					endif;
				endforeach;
				
				/* get employee information */
				$empIds			=	array_unique($empIds);
				$objEmployees	=	[];
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
				endif;
				
				foreach ($results['hits']['hits'] as $result):
					$row		=	(object)$result['_source'];
					$robject	=	new stdClass();
					$robject->session_id    		= 	(int)$row->session_id;
					$robject->title					= 	(string)$this->common->entityDecode($row->title);
					$robject->summary				= 	(string)$this->common->entityDecode($row->summary);
					$robject->created_on			= 	(string)$row->created_on;
					$robject->start_datetime		= 	(string)$row->start_datetime;
					$robject->end_datetime			= 	(string)$row->end_datetime;
					$robject->speakers				=	[];
					
					if(!empty($row->speakers)):
						foreach ($row->speakers	as $key => $id):
							if(isset($objEmployees[$id]) && $objEmployees[$id]['enabled']==1):
								$emp	=	$this->setSpeakerObject($objEmployees,$id,$ObjMaster);
								array_push($robject->speakers, $emp);
							endif;
						endforeach;
					endif;
					array_push($arr,$robject);
				endforeach;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setSpeakerObject($objEmployees,$id,$ObjMaster){
		try{
			
			$emp					=	new stdClass();
			$emp->employee_id 		= 	(int)$objEmployees[$id]['employee_id'];
			$first_name				=	$objEmployees[$id]['first_name']?$objEmployees[$id]['first_name']." ":"";
			$middle_name			=	$objEmployees[$id]['middle_name']?$objEmployees[$id]['middle_name']." ":"";
			$last_name				=	$objEmployees[$id]['last_name']??"";
			$emp->employee_name		=	(string)$first_name.$middle_name.$last_name;
			$emp->employee_code		=	(string)$objEmployees[$id]['employee_code'];
			$emp->profile_picture	=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
													'image_path'	=>	(string)$objEmployees[$id]['profile_picture']];
			
			$emp->position_title	=	(string)$objEmployees[$id]["position_title"];
			$emp->geography			=	new stdClass();
			$Key					=	array_search($objEmployees[$id]['geography_id'], array_column($ObjMaster->geographies, 'geography_id'));
			if ($Key!==false):
				$emp->geography		= 	(object)['geography_id' => (int)$ObjMaster->geographies[$Key]->geography_id,
											  'title' => (string)$ObjMaster->geographies[$Key]->title];
			endif;
			
			$emp->location			=	new stdClass();
			$Key					=	array_search($objEmployees[$id]['location_id'], array_column($ObjMaster->locations, 'location_id'));
			if ($Key!==false):
				$emp->location			= 	(object)[	'location_id' => (int)$ObjMaster->locations[$Key]->location_id,
														'title' => (string)$ObjMaster->locations[$Key]->title];
			endif;
			
			$emp->function	=	new stdClass();
			$Key	=	array_search($objEmployees[$id]['function_id'], array_column($ObjMaster->functions, 'function_id'));
			if ($Key!==false):
				$emp->function		= 	(object)[	'function_id' => (int)$ObjMaster->functions[$Key]->function_id,
													'title' => (string)$ObjMaster->functions[$Key]->title];
			endif;
			return $emp;
			
		}catch(Exception $e){
			$this->es_error($e);
		}
	}
	
	public function updateField($objectId, $field, $value){
		try{
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