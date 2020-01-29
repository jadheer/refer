<?php 
class ES_ChatQuestion  extends ESSource {
	var $index;
	var $type;
	public function __construct(){	
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
    public function __destruct()
	{	parent::__destruct();	}

	public function getQuestionById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['_source']	=	['question_id', 'session_id', 'employee_id', 'question', 'asked_on', 'is_approved', 'enabled', 'likes', 'tagged_employees'];
			$params['body']['query']['bool']['must'] = ['term' =>  ['question_id'=>(int)$object->question_id]];
			
			$params['body']['query']['bool']['should']	=	[['nested'	=>	[   'path' 		    => 'answers',
																			    'query'		    =>  ['bool' => ['must' => [['term' => ['answers.enabled' => (int)1]]]]],
																			    'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	(int)1]]],
															 ['nested'	=>	[   'path' 		    => 'comments',
																				'query'		    =>  ['bool' => ['must' => [['term' => ['comments.enabled' => (int)1]]]]],
																				'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	(int)1]]]];

			$result				=	$this->es_search($params);
			if (!empty($result['hits']['hits'][0]['_source'])):
				$empIds			=	[];
				$row			=	(object)$result['hits']['hits'][0]['_source'];
				$inner_doc		=	$result['hits']['hits'][0]['inner_hits']??[];
				
				if(isset($inner_doc['answers']['hits']['total'])):
					$row->answer_count		= 	$inner_doc['answers']['hits']['total'];
				endif;
				if(isset($inner_doc['comments']['hits']['total'])):
					$row->comment_count		= 	$inner_doc['comments']['hits']['total'];
				endif;
				
				$robject		=	new stdClass();
				$robject		=	$this->setObject($row, $object);
				$ES_Employee	=	new ES_Employee();
				$objEmployees	=	$ES_Employee->getEmployeeByIds([$row->employee_id]);
				
				if (!empty($objEmployees)):
					if (!empty($robject->employee->employee_id)):
						if (!empty($objEmployees)):
							$Key	=	array_search($robject->employee->employee_id, array_column($objEmployees, 'employee_id'));
							if ($Key!==false):
								if ($objEmployees[$Key]['enabled'] == 1):
									$robject->employee		=	(object)[	'employee_id'	 	=> 	(int)$objEmployees[$Key]['employee_id'],
																			'first_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['first_name']),
																			'middle_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['middle_name']),
																			'last_name'			=>	(string)$this->common->entityDecode($objEmployees[$Key]['last_name']),
																			'display_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['display_name']),
																			'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																												'image_path'	=>	(string)$objEmployees[$Key]['profile_picture']]];
								endif;
							endif;
						endif;
					endif;	
				endif;

				$ES_ChatSession	=	new ES_ChatSession();
				$objSession		=	$ES_ChatSession->getChatSessionById($robject->session_id);	
				
				if (!empty($objSession)):
					$object	=	new stdClass();
					$object	=	new stdClass();
					$object->session_id		=	(int)$objSession->session_id;
					$object->session_title	=	(string)$objSession->session_title;
					$object->start_datetime   	= 	(string)$objSession->start_datetime;
					$object->end_datetime   	= 	(string)$objSession->end_datetime;
					unset($robject->session_id);
					$object->question	=	$robject;

				endif;
			endif;
			return !empty($object)?$object:new stdClass();
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	
	//set employee object details for answers/replies
	private function setLatestAnswersEmployees($robject,$objEmployees){
		try{
			
			if(!empty($robject->latest_answer)):
				//answerd employee object
				if (!empty($robject->latest_answer->employee->employee_id)):
					$rKey	=	array_search($robject->latest_answer->employee->employee_id, array_column($objEmployees, 'employee_id'));
					if ($rKey!==false && $objEmployees[$rKey]['enabled'] == 1) :
							$robject->latest_answer->employee		=	(object)[	'employee_id' 		=> 	(int)$objEmployees[$rKey]['employee_id'],
																					'first_name'		=>	(string)$this->common->entityDecode($objEmployees[$rKey]['first_name']),
																					'middle_name'		=>	(string)$this->common->entityDecode($objEmployees[$rKey]['middle_name']),
																					'last_name'			=>	(string)$this->common->entityDecode($objEmployees[$rKey]['last_name']),
																					'display_name'		=>	(string)$this->common->entityDecode($objEmployees[$rKey]['display_name']),
																					'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																														'image_path'	=>	(string)$objEmployees[$rKey]['profile_picture']]];
					endif;
				endif;
				//tagged employee object
				if (!empty($robject->latest_answer->tagged_employees)):
					foreach ($robject->latest_answer->tagged_employees as $key => $value):
						$empKey	=	array_search($value, array_column($objEmployees, 'employee_id'));
							if ($empKey!==false && $objEmployees[$empKey]['enabled'] == 1):
								$robject->latest_answer->tagged_employees[$key]	=	(object)[	'employee_id' 		=> 	(int)$objEmployees[$empKey]['employee_id'],
																							'first_name'		=>	(string)$this->common->entityDecode($objEmployees[$empKey]['first_name']),
																							'middle_name'		=>	(string)$this->common->entityDecode($objEmployees[$empKey]['middle_name']),
																							'last_name'			=>	(string)$this->common->entityDecode($objEmployees[$empKey]['last_name']),
																							'display_name'		=>	(string)$this->common->entityDecode($objEmployees[$empKey]['display_name']),
																							'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																																		'image_path'	=>	(string)$objEmployees[$empKey]['profile_picture']]];
							else:
								unset($robject->latest_answer->tagged_employees[$key]);
						endif;
					endforeach;
				endif;
			else:
				$robject->latest_answer	= new stdClass();
			endif;
			return $robject;
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	
	public function addQuestion($object){
		try{
			$params = [];
			$params['body']  = 	[	'question_id'		=>	(int)$object->question_id,
									'session_id'		=>	(int)$object->session_id,
									'employee_id'		=>	(int)$object->employee_id,
									'question'			=>	(string)$this->common->entityEncode($object->question),
									'asked_on'			=>	(string)$object->asked_on,
									'is_approved'		=>	(int)$object->is_approved,
									'enabled'			=>	(int)$object->enabled,
									'answers'			=>	[],
									'comments'			=>	[],
									'likes'				=>	[],
									'tagged_employees'	=>	[]];
	
			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->question_id;
			return $this->es_index($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getQuestions($object){
		try{
			$params = [];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['_source']	=	['question_id', 'session_id', 'employee_id', 'question', 'asked_on', 'is_approved', 'enabled', 'likes', 'tagged_employees'];
	
			$params['body']['query']['bool']['must'] = [	['term' =>  ['enabled'=>(int)1]],['term' => ['session_id'=>(int)$object->session_id]],
															['bool'	=>	['should'	=>	[['term' => ['is_approved'=>(int)1]],['term' => ['employee_id'=>(int)$object->employee_id]]]]]];
		
			$params['body']['query']['bool']['should']	=	[['nested'	=>	[   'path' 		    => 'answers',
																				'query'		    =>  ['bool' => ['must' => [['term' => ['answers.enabled' => (int)1]]]]],
																				'inner_hits'	=>	['from'	=>	(int)$object->start, 'size'	=>	(int)1]]],
															['nested'	=>	[   'path' 		    => 'comments',
																				'query'		    =>  ['bool' => ['must' => [['term' => ['comments.enabled' => (int)1]]]]],
																				'inner_hits'	=>	['from'	=>	(int)$object->start, 'size'	=>	(int)1]]]];
			
			$params['body']['sort']	=	[ ['asked_on' => ['order' => 'desc']], ['question_id' => ['order' => 'asc']],['answers.answer_on' => ['order' => 'desc']]];
			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results,$object){
		try{
			$empIds			=	[];
			$tags_empIds	=	[];
			$robjects		=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						/* set answer count & comment count */
						if(isset($obj['inner_hits']['answers']['hits']['total'])):
							$row->answer_count		= 	$obj['inner_hits']['answers']['hits']['total'];
						endif;
						if(isset($obj['inner_hits']['comments']['hits']['total'])):
							$row->comment_count		= 	$obj['inner_hits']['comments']['hits']['total'];
						endif;
						if(isset($obj['inner_hits']['answers']['hits']['hits'][0]['_source'])):
							$docs						=	$obj['inner_hits']['answers']['hits']['hits'][0]['_source'];
							$aobject					=	new stdClass();
							$aobject->answer_id 		=	(int)$docs['answer_id'];
							$aobject->answer 			=	(string)$this->common->entityDecode($docs['answer']);
							$aobject->answer_on 		=	(string)$docs['answer_on'];
							$aobject->employee			=	(object)['employee_id' 	=> 	(int)$docs['employee_id']];
							$aobject->tagged_employees	=	[];
							if (!empty($docs['tagged_employees'])):
								$aobject->tagged_employees	=	$docs['tagged_employees'];;
								$tags_empIds				=	array_merge($tags_empIds,$aobject->tagged_employees);
							endif;  
							$row->latest_answer		= 	$aobject;
							array_push($empIds, $docs['employee_id']);
						endif;
						array_push($empIds, $row->employee_id);
						array_push($robjects, $this->setObject($row, $object));
					endif;
				endforeach;
			endif;
		
			$empIds		=	array_unique(array_merge($empIds,$tags_empIds));
			if (!empty($empIds)):
				$ES_Employee	=	new ES_Employee();
				$objEmployees	=	$ES_Employee->getEmployeeByIds($empIds);
			
				
				if (!empty($objEmployees)):
					foreach ($robjects as $robject):
						$Key	=	array_search($robject->employee->employee_id, array_column($objEmployees, 'employee_id'));
						if ($Key!==false):
							//if ($objEmployees[$Key]['enabled'] == 1):
								$robject->employee		=	(object)[	'employee_id' 		=> 	(int)$objEmployees[$Key]['employee_id'],
																		'first_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['first_name']),
																		'middle_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['middle_name']),
																		'last_name'			=>	(string)$this->common->entityDecode($objEmployees[$Key]['last_name']),
																		'display_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['display_name']),
																		'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																											'image_path'	=>	(string)$objEmployees[$Key]['profile_picture']]];
							//endif;
						endif;;
						$robject	=	$this->setLatestAnswersEmployees($robject, $objEmployees);
					endforeach;
				endif;
			endif;
			return $robjects;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setObject($row, $object){
		try{
			$robject =	new stdClass();
			$robject->session_id 		=	(int)$row->session_id;
			$robject->question_id 		=	(int)$row->question_id;
			$robject->question 			=	(string)$this->common->entityDecode($row->question);
			$robject->asked_on 			=	(string)$row->asked_on;
			$robject->approved			=	(boolean)($row->is_approved==1)?true:false;
			$robject->can_answer		=	(boolean)($object->employee_id==_CMD_EMPLOYEE_ID)?true:false;
			if (!$robject->can_answer):
				$robject->can_answer	=	(boolean)(in_array($object->employee_id, $row->tagged_employees)?true:false);
			endif;
			$robject->answer_count		=	(int)$row->answer_count??0;
			$robject->comment_count		=	(int)$row->comment_count??0;
			if(!empty($row->latest_answer)):
				$robject->latest_answer		=	$row->latest_answer;
			endif;
			$robject->likes				=	(object)[	'count'		=>	(int)count($row->likes),
														'is_liked'	=>	(boolean)(in_array($object->employee_id,$row->likes))];
			$robject->employee			=	(object)['employee_id' 	=> 	(int)$row->employee_id];
			$robject->tagged_employees	=	!empty($row->tagged_employees)?$row->tagged_employees:[];
			return $robject;
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
	
	public function getQuestionCounts($sessionIds){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$params['size']  	= 	1000;
			$params['scroll']	=	'30s';
			$params['_source']	=	['session_id', 'question_id'];
			$params['body']['query']['bool']['filter'] 	= 	[['terms' 	=> 	['session_id'	=> $sessionIds]]];
			$params['body']['query']['bool']['must']	=	[['term' => ['enabled'=>(int)1]],
															['term' => ['is_approved'=>(int)1]]];
			$results	=	$this->es_search($params);
			$arr		=	[];
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						$arr[$row->session_id][] =	$row->question_id;
					endif;
				endforeach;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return  $arr;
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}
	
	public function addAnswer($object){
		try{
			$params = [];
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->question_id;
	
			$doc 	= 	(object)[	'answer_id'			=>	(int)$object->answer_id,
									'created_by'		=>	(int)$object->created_by,
									'updated_by'		=>	(int)$object->updated_by,
									'employee_id'		=>	(int)$object->employee_id,
									'answer'			=>	(string)$this->common->entityEncode($object->answer),
									'answer_on'			=>	(string)$object->answer_on,
									'enabled'			=>	(int)$object->enabled,
									'tagged_employees'	=>	!empty($object->tagged_employees)?$object->tagged_employees:[]];
	
			$params['body']['script']['inline']	=	'ctx._source.answers.add(params.answers)';
			$params['body']['script']['params']	=	['answers' => $doc];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getAnswers($object){
		try{
			$params 			= 	[];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['_source']	=	false;
				
			$params['body']['query']['bool']['must'] = [[['term' 	=>	["question_id" => (int)$object->question_id]],
														['term' 	=> 	[ "enabled" => (int)1]]],
														['term' 	=> 	[ "is_approved" => (int)1]],
														['nested'	=>	[	'path' => 'answers',
																			'query'	=> ['bool' => ['must' => [['term' => ['answers.enabled' => (int)1]]]]],
																			'inner_hits'	=>	['from'	=>	(int)$object->start, 'size'	=>	(int)$object->end]]]];
														
			$params['body']['sort']	=	[['answers.answer_on' => ['order' => 'desc']], ['answers.answer_id' => ['order' => 'asc']]];
			$results	=	$this->es_search($params);
			$robjects	=	[];
			$empIds		=	[];
	
			if (isset($results['hits']['hits'][0]['inner_hits']['answers']['hits']['hits'])):
				if ($results['hits']['hits'][0]['inner_hits']['answers']['hits']['total']>0):
					$docs	=	$results['hits']['hits'][0]['inner_hits']['answers']['hits']['hits'];
					foreach ($docs as $obj):
						$obj	=	(object)$obj['_source'];
					
						
						$object		=	new stdClass();
						$object->answer_id 			=	(int)$obj->answer_id;
						$object->answer 			=	(string)$this->common->entityDecode($obj->answer);
						$object->answer_on 			=	(string)$obj->answer_on;
						$object->employee			=	(object)['employee_id' 	=> 	(int)$obj->employee_id];
						$object->tagged_employees	=	[];
						if (!empty($obj->tagged_employees)):
							$object->tagged_employees	=	$obj->tagged_employees;
							if (!empty($obj->tagged_employees)):
								$empIds	=	array_merge($empIds, $obj->tagged_employees);
							endif;
						endif;

						array_push($empIds, (string)$obj->employee_id);
						array_push($robjects, $object);
					endforeach;
				endif;
			endif;

			$objects	=	[];
			$empIds		=	array_unique($empIds);
			if (!empty($empIds)):
				$ES_Employee	=	new ES_Employee();
				$objEmployees	=	$ES_Employee->getEmployeeByIds($empIds);
	
				if (!empty($objEmployees)):
					foreach ($robjects as $robject):
						$Key	=	array_search($robject->employee->employee_id, array_column($objEmployees, 'employee_id'));
						if ($Key!==false):
							if ($objEmployees[$Key]['enabled'] == 1):
								$robject->employee		=	(object)[	'employee_id' 		=> 	(int)$objEmployees[$Key]['employee_id'],
																		'first_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['first_name']),
																		'middle_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['middle_name']),
																		'last_name'			=>	(string)$this->common->entityDecode($objEmployees[$Key]['last_name']),
																		'display_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['display_name']),
																		'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																											'image_path'	=>	(string)$objEmployees[$Key]['profile_picture']]];
																		
								if (!empty($robject->tagged_employees)):
									foreach ($robject->tagged_employees as $key => $value):
										$empKey	=	array_search($value, array_column($objEmployees, 'employee_id'));
										if ($empKey!==false):
											$robject->tagged_employees[$key]	=	(object)[	'employee_id' 		=> 	(int)$objEmployees[$empKey]['employee_id'],
																								'first_name'		=>	(string)$this->common->entityDecode($objEmployees[$empKey]['first_name']),
																								'middle_name'		=>	(string)$this->common->entityDecode($objEmployees[$empKey]['middle_name']),
																								'last_name'			=>	(string)$this->common->entityDecode($objEmployees[$empKey]['last_name']),
																								'display_name'		=>	(string)$this->common->entityDecode($objEmployees[$empKey]['display_name']),
																								'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																																	'image_path'	=>	(string)$objEmployees[$empKey]['profile_picture']]];
										else:
											unset($robject->tagged_employees[$key]);
										endif;
									endforeach;
								endif;
								array_push($objects,$robject);
							endif;
						endif;
					endforeach;
				endif;
			endif;
			return $objects;
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