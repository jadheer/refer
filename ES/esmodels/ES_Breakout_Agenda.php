<?php 
class ES_Breakout_Agenda  extends ESSource {
	var $index;
	var $type;
	public function __construct(){	
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
	
    public function __destruct()
	{	parent::__destruct();	}
    
	/* for all sessions*/
	public function getSessionsByDate($object){
		try{
			$params 			= 	[];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['scroll']	=	'30s';
			
			$params['body']['query']['bool']['must'] 		= 	[['term' => ['enabled'=>1]] , ['term' => ['breakout_id'=>(int)$object->breakout_id ]]];
			$params['body']['sort']							=	[['start_datetime'=>['order'=> 'asc']],['session_id'=>['order'=>'asc']]];
			
			
			if(!empty($object->date)):
				array_push($params['body']['query']['bool']['must'], ['range'=>	[ 'start_datetime' => ['gte' => (string)$object->date." 00:00:00", "format"=> "yyyy-MM-dd HH:mm:ss"]]]);
				array_push($params['body']['query']['bool']['must'], ['range'=>	[ 'start_datetime' => ['lte' => (string)$object->date." 23:59:00", "format"=> "yyyy-MM-dd HH:mm:ss"]]]);
			endif;
 
			$results	=	$this->es_search($params);
			$empIds		=	[];
			$arr		=	[];
			
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				foreach ($results['hits']['hits'] as $result):
					/*get the employee ids*/
					if (!empty($result['_source'])):
						$row			=	(object)$result['_source'];
						$emp_speakers	=	[];
						if(!empty($row->speakers)):
							$emp_speakers	=	array_values(array_column($row->speakers,"employee_id"));
						endif;
						$empIds	=	array_merge($empIds,array_filter($emp_speakers));
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
					array_push($arr,$this->setObject($row,$objEmployees,$object));
				endforeach;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getUniqueDates($object) {
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$params['size']  	= 	1000;
			$params['scroll']	=	'30s';
			$params['_source']	=	['start_datetime'];
			$dates				=	[];
			
			$params['body']['query']['bool']	=	[	'must' => [	['term' 	=> 	['enabled'		=>	(int)1]],
																	['term' 	=> 	['breakout_id'	=>	(int)$object->breakout_id]]]];
			
			$results	=	$this->es_search($params);
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				foreach ($results['hits']['hits'] as $result):
					$row	=	(object)$result['_source'];
					$date	=	date('Y-m-d',strtotime($row->start_datetime));
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
	
	private function setObject($row, $objEmployees,$aobject){
		try{
			$robject	=	new stdClass();
			$robject->session_id    		= 	(int)$row->session_id;
			$robject->title					= 	(string)$this->common->entityDecode($row->title);
			$robject->summary				= 	(string)$this->common->entityDecode($row->summary);
			$robject->created_on			= 	(string)$row->created_on;
			$robject->start_datetime		= 	(string)$row->start_datetime;
			$robject->end_datetime			= 	(string)$row->end_datetime;
			//media object
			$robject->media					=	(object)[ 	"pdf" =>		$row->pdf_links,
															"text" =>		$row->text_links,
															"youtube" => 	$row->youtube_links ];	
			
			$robject->enable_speaker 		=	(boolean)(!empty($row->enable_speaker)?true:false);
			$robject->enable_rate			=	(boolean)(!empty($row->enable_rate)?true:false);
			
			//speakers
			$robject->speakers				=	[];
			$robject->question_count		=	0;
			$sort_speakers					=	[];
			if(!empty($row->speakers) && !empty($row->enable_speaker)):
				foreach ($row->speakers	as $key => $speaker):
					$emp	=	$this->setSpeakerObject($objEmployees,(object)$speaker);
					array_push($robject->speakers, $emp);
				endforeach;
				$sort_speakers	=	array_column($robject->speakers,NULL,'sort');
				ksort($sort_speakers);
				$robject->speakers		=	array_values($sort_speakers);
				
				//get question count 
				$questions	=	[];
				foreach ($row->questions as $key => $question):
					if(in_array($question['speaker_id'],array_column($row->speakers,"speaker_id"))):
						array_push($questions,$question);
					endif;
				endforeach;
				$robject->question_count		=	count($questions);
			endif;
			
			//ratings
			$robject->ratings				=	new stdClass();
			
			if(!empty($row->ratings)):
				$ratings			=	array_column($row->ratings,"rate","employee_id");
				$row->employee_id	=	isset($ratings[$aobject->employee_id])?$aobject->employee_id:0;
				$row->rate_value	=	isset($ratings[$aobject->employee_id])?$ratings[$aobject->employee_id]:0;
				$row->average_rate	=	array_sum(array_column($row->ratings,"rate"))/count($row->ratings);
			endif;
			
			if(!empty($row->enable_rate)):
				$robject->ratings			=	(object)[	"is_rated" 		=> 	(boolean)(!empty($row->employee_id)?true:false),
															"rate_value" 	=> 	(int)(!empty($row->rate_value)?$row->rate_value:0),
															"avg_rate" 		=> 	(string)(!empty($row->average_rate)?round($row->average_rate,1):"")
														];
			endif;
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	private function setSpeakerObject($objEmployees,$speaker){
		try{
			
			$emp					=	new stdClass();
			$emp->speaker_id 		= 	(int)$speaker->speaker_id;
			$emp->employee_id 		= 	(int)$speaker->employee_id;
			$emp->guest_speaker		=	(boolean)(empty($speaker->employee_id)?true:false);

			if(!empty($emp->employee_id)):
				$emp->first_name		=	(string)$this->common->entityDecode($objEmployees[$speaker->employee_id]['first_name']);
				$emp->middle_name		=	(string)$this->common->entityDecode($objEmployees[$speaker->employee_id]['middle_name']);
				$emp->last_name			=	(string)$this->common->entityDecode($objEmployees[$speaker->employee_id]['last_name']);
				$emp->employee_code		=	(string)$objEmployees[$speaker->employee_id]['employee_code'];
				$emp->profile_picture	=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
														'image_path'	=>	(string)$objEmployees[$speaker->employee_id]['profile_picture']];
				$emp->position_title	=	(string)$this->common->entityDecode($objEmployees[$speaker->employee_id]["position_title"]);
				$emp->sort				=	$emp->first_name." ".$emp->middle_name." ".$emp->last_name;
			else:
				$emp->first_name		=	(string)$this->common->entityDecode($speaker->name);
				$emp->middle_name		=	(string)"";
				$emp->last_name			=	(string)"";
				$emp->profile_picture	=	(object)[	'base_url'		=>	(string)_AWS_URL._BREAKOUTS_IMAGES_DIR,
														'image_path'	=>	(string)$speaker->picture_path];
				$emp->employee_code		=	(string)"";
				$emp->position_title	=	(string)$this->common->entityDecode($speaker->designation);
				$emp->sort				=	$emp->first_name;
			endif;
			
			return $emp;
			
		}catch(Exception $e){
			$this->es_error($e);
		}
	}

	public function getAgendaById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->session_id;
			
			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				$robject	=	(object)$result['_source'];
			endif;
			return !empty($robject)?$robject:0;
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}

	//rate agenda sessions
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
			
			$params['body']['script']['inline']	=	'for (int i = 0; i < ctx._source.ratings.size(); i++){if( ctx._source.ratings[i].employee_id == params.employee_id){ctx._source.ratings.remove(i);}}';
			$params['body']['script']['params']	=	['employee_id' => (int)$object->employee_id  ];
			return $this->es_update($params);
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
									'is_anonymous'		=>  (int)$object->is_anonymous,
									'enabled'			=>	(int)$object->enabled];
			
			$params['body']['script']['inline']	=	'ctx._source.questions.add(params.questions)';
			$params['body']['script']['params']	=	['questions' => $doc];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getQuestions($object){
		try{
			$params 			= 	[];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			
			$params['body']['query']['bool']['must']= 	[	[['term' 	=> ["session_id" => (int)$object->session_id]]],
															['nested'	=> ['path' => 'questions',
																			'query'	=> ['bool'	 => ['should' => [['term' => ['questions.is_anonymous' => (int)1]],['term' => ['questions.is_anonymous' => (int)0]]]]],
																			'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	50000 , 'sort'=>['questions.asked_on' => ['order' => 'desc']]]]]];
		
			$result				= 	$this->es_search($params);
			$empIds				=	[];
			$arr				=	[];

			if (!empty($result['hits']['total']) > 0 ):
				$row			=	(object)$result['hits']['hits'][0]['_source'];
				
				//speakers 
				$emp_speakers	=	[];
				if(!empty($row->speakers)):
					$emp_speakers	=	array_values(array_column($row->speakers,"employee_id"));
				endif;
				$empIds	=	array_merge($empIds,array_filter($emp_speakers));
				
				//questions
				if(!empty($row->questions)):
					$qempIds	=	array_column($row->questions, "employee_id");
					$empIds		=	array_merge($empIds,$qempIds);
				endif;
		
				/* get employee information */
				$empIds			=	array_unique($empIds);
				$objEmployees	=	[];
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
				endif;
				$docs	= [];
				if(!empty($row->speakers)):
					$docs = $result['hits']['hits'][0]['inner_hits']['questions']['hits']['hits'];
					foreach ($row->speakers	as $key => $speaker):
						$speaker	=	(object)$speaker;
						$id 		= 	$speaker->employee_id;
						$emp	=	$this->setSpeakerObject($objEmployees,$speaker);
						$emp->questions			=	[];
						foreach ($docs as $key => $question):
							$question	=	(object)$question['_source'];
							if($question->speaker_id == $speaker->speaker_id ):
								$quest						=	new stdClass();
								$quest->question_id 		=	(int)$question->question_id;
								$quest->speaker_id			=	(int)$question->speaker_id;
								$quest->question			=	(string)$this->common->entityDecode($question->question);
								$quest->asked_on 			=	(string)$question->asked_on;
								$quest->anonymous 			=	(boolean)(!empty($question->is_anonymous)?true:false);
								$quest->employee			=	 new stdClass();
								if(!$quest->anonymous):
									$quest->employee		=	$this->setEmployeeObject($objEmployees,$question->employee_id);
								endif;
								array_push($emp->questions , $quest);
							endif;
						endforeach;
						
						if(!empty($emp->questions)):
							array_push($arr, $emp);
						endif;
					endforeach;
					$sort_speakers	=	array_column($arr,NULL,'sort');
					ksort($sort_speakers);
					$arr =	array_values($sort_speakers);
				endif;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setEmployeeObject($objEmployees,$id){
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
			
			$emp->position_title	=	(string)$this->common->entityDecode($objEmployees[$id]["position_title"]);
			return $emp;
			
		}catch(Exception $e){
			$this->es_error($e);
		}
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