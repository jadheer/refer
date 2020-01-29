<?php

class ES_Cheer_Employee_Award extends ESSource {

	public function __construct()
	{	parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}

	public function __destruct()
	{	parent::__destruct();	}


	public function addEmployee($object){
		try
		{
			$params 	= 	[];
			$params['body']  		= 	[	'cheer_employee_award_id'		=>	(int)$object->cheer_employee_award_id,
											'award_id'						=>	(int)$object->award_id,
											'award_key'						=>	(string)$object->award_key,
											'employee_id'					=>	(int)$object->employee_id,
											'created_on'					=>	(string)$object->created_on,
											'citation_id'					=>	(int)$object->citationid,
											'approved_on'					=>	(string)$object->approved_on,
											'approved'						=>	(int)$object->approved,
											'approved_by'					=>	(int)0,
											'approval_through'				=>	(string)$object->approval_through,
											'points'						=>	(int)0,
											'extra_points'					=>	(int)0,
											'enabled'						=>	(int)1,
											'comments'						=>	[],
											'likes'							=>	[]];


			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->cheer_employee_award_id;
			return  $this->es_index($params);
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
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
	
	public function getRewards($object,$master_obj) {
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	[	'must' => [['term' => ['approved'=>1]],['term' => ['enabled'=>1]] ]];
			$params['body']['sort']				=	[['approved_on' => ['order'	=>	'desc']], ['cheer_employee_award_id' => ['order' => 'desc']]];
			
			
	     	
	     	// awards of current logged in employee 
			if(!empty($object->type) && $object->type == "received"):
				array_push($params['body']['query']['bool']['must'], ['term' => ['employee_id' => $object->employee_id]]);
			endif;

			$results 	=	$this->es_search($params);
			$arr		=	[];
			if ($results['hits']['total']>0):
				$empIds		=	[];
				$nomination_arr	=	[];
				$cheerIds		=	[];
				$citationIds	=	[];
				
				$es_icon				=		new ES_Icon();
				$objIcons				=		$es_icon->getIcons();
				
				
				/*get the employee ids & award ids */
				foreach ($results['hits']['hits'] as $award_obj):
					if (!empty($award_obj['_source'])):
						$row	=	(object)$award_obj['_source'];
						array_push($empIds, $row->employee_id);
						array_push($citationIds,$row->citation_id);
						if(!empty($row->approved_by)):
							array_push($empIds, $row->approved_by);
						endif;
						array_push($cheerIds, $row->cheer_employee_award_id);
					endif;
				endforeach;
	
				
				$es_nomination					=		new ES_Cheer_Employee_Nomination();
				$nomination_arr					=		$es_nomination->getNominations($cheerIds);

				/* get employee information */
				$empIds			=	array_unique($empIds);
				$objEmployees	=	[];
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
				endif;
				
				//get citations
				$citationIds			=	array_filter(array_unique($citationIds));
				$citation_obj			=	[];
				if (!empty($citationIds)):
					$ES_award			=		new ES_Cheer_Award();
					$citation_obj		=		$ES_award->getCitationsByIds($citationIds);
				endif;
				
				// get award informations 
				$ES_cheer			=		new ES_Cheer_Award();
				$objAwards			=		array_column($ES_cheer->getAwards($master_obj),NULL,"award_id");

				foreach ($results['hits']['hits'] as $award_obj):
					if (!empty($award_obj['_source'])):
						$row								=		(object)$award_obj['_source'];
						//show awards of enabled only
						if(!empty($objAwards[$row->award_id])):
							$robject							=		new stdClass();
							$robject->type						=		"reward";
							$robject->cheer_employee_award_id	= 		(int)$row->cheer_employee_award_id;
							$robject->comment_count 			=		(int)count($row->comments);
							$robject->likes						= 		(object)[	'count'		=>	(int)count($row->likes),
																					'is_liked'	=>	(boolean)in_array($object->employee_id,$row->likes)];
					
							$robject->created_on				=		(string)$row->created_on;
							$robject->award_details				= 		$objAwards[$row->award_id];
							$robject->timeframe					=		new stdClass();
							if($robject->award_details->award_frequency == "Monthly"):
								$robject->timeframe					=		(object)['start_date'=> date('Y-m-d', strtotime($row->created_on." -1 month"))];
							elseif($robject->award_details->award_frequency == "Quarterly"):
								$robject->timeframe					=		$this->common->getPrevisiousQuarterDates($row->created_on);
							endif;
							$robject->award_details->show_certificate			= 		(boolean) ($object->employee_id == $row->employee_id)? true:false;
							
							
							$robject->citation					=		(string)!empty($citation_obj[$row->citation_id])?$citation_obj[$row->citation_id]:"";
							$robject->employee					=		$this->setEmpObject($objEmployees, $row->employee_id);
							
							$robject->approver					=		new stdClass();
							if(!empty($row->approved_by)):
								$robject->approver				=		$this->setEmpObject($objEmployees, $row->approved_by);
								$robject->approver->approved_on	=		$row->approved_on;
							endif;
							
							$robject->points					=		(int)$row->points;
							$robject->extra_points				=		(int)$row->extra_points;
							$robject->nominations				=		$nomination_arr[$row->cheer_employee_award_id]??[];
							array_push($arr,$robject);
						endif;
					endif;
					endforeach;
				endif;
			return  $arr;
		}
		catch(Exception $e)
		{$this->es_error($e);	}
	}
	

	public function refresh(){
		try{
			$params = [];
			$params['index']  	= 	$this->index;
			return $this->es_indices_refresh($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	private function setEmpObject($objEmployees,$id){
		try{
			
			$emp						=	new stdClass();
			$emp->employee_id 			= 	(int)$objEmployees[$id]['employee_id'];
			$first_name					=	!empty($objEmployees[$id]['first_name'])?$this->common->entityDecode($objEmployees[$id]['first_name']):"";
			$middle_name				=	!empty($objEmployees[$id]['middle_name'])?$this->common->entityDecode($objEmployees[$id]['middle_name']):"";
			$last_name					=	!empty($objEmployees[$id]['last_name'])?$this->common->entityDecode($objEmployees[$id]['last_name']):"";
			$emp->first_name			=	$first_name;
			$emp->middle_name			=	$middle_name;
			$emp->last_name				=	$last_name;
			$sfirst_name				=	!empty($first_name)?$first_name." ":"";
			$smiddle_name				=	!empty($middle_name)?$middle_name." ":"";
			$emp->employee_name			=	(string)$sfirst_name.$smiddle_name.$last_name;
			$emp->position_title		=	(string)$this->common->entityDecode($objEmployees[$id]["position_title"]);
			$emp->employee_code			=	(string)$objEmployees[$id]['employee_code'];
			$emp->display_name			=	(string)$objEmployees[$id]['display_name'];
			$emp->profile_picture		=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
														'image_path'	=>	(string)$objEmployees[$id]['profile_picture']];
			
			return $emp;
			
		}catch(Exception $e){
			$this->es_error($e);
		}
	}

	
	public function getApprovalRequests($obj,$master_obj){
		try{
	
			//get all approval nominations
			$es_cheer			=		new ES_Cheer_Employee_Nomination();
			$cheer_ids			=       $es_cheer->getNominationForApprovals($obj);
			
			$params = [];
			$params['from']  	= 	$obj->start??0;
			$params['size']  	= 	$obj->end??1000;
			$params['index'] 	= 	$this->index;
			$rObjects			=	[];
			
			if(!empty($cheer_ids)):
				
				$params['body']['query']['bool']['must']		=			[['terms' =>	['cheer_employee_award_id' => array_values($cheer_ids) ]], ['term' => ['enabled'=>1]] ];
			
			
				//for pending & all filters
				if (empty($obj->approved)):
					array_push($params['body']['query']['bool']['must'], ['term' => ['approved' => 0 ]]);
					$params['body']['sort']								= 			['cheer_employee_award_id' => ['order' => 'asc']];
				else:
					$params['body']['query']['bool']['must'][2]['bool']['should']			=	[   [ 'bool' 	=>     [ 'must' =>  [ 'term' => ['approved'	=>	0 ]]]],
																									[ 'bool'	=>	   [ 'must' =>	 [[ 'terms' => ['approved'	=>	[1,2] ]],['term' => ['approved_by'	=>$obj->employee_id ]]]]]];
				
					$params['body']['sort']								= 	['approved_on' => ['order' => 'desc'] , 'cheer_employee_award_id' => ['order' => 'desc']];
				endif;

				$results		=		$this->es_search($params);
				$approvalIds	=		$citationIds	=	$awardIds	=	$arr = $empIds = []; 

				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						$row						=		(object)$object['_source'];
						array_push($citationIds,$row->citation_id);
						array_push($awardIds,$row->award_id);
						array_push($approvalIds,$row->cheer_employee_award_id);
						array_push($empIds,$row->employee_id);
						array_push($arr,$row);
					endforeach;
				endif;
					
				$es_award				=		new ES_Cheer_Award();
				$award_obj				=		array_column($es_award->getAwards($master_obj,false),NULL,"award_id");
				
				$es_nomination			=		new ES_Cheer_Employee_Nomination();
				$nomination_obj			=		$es_nomination->getNominations($approvalIds);
				
				$empIds					=		array_unique($empIds);
				$objEmployees			=		[];
				if (!empty($empIds)):
					$ES_Employee	=	new ES_Employee();
					$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
				endif;
		
				$citationIds			=	array_filter(array_unique($citationIds));
				$citation_obj			=	[];
				if (!empty($citationIds)):
					$ES_award			=		new ES_Cheer_Award();
					$citation_obj		=		$ES_award->getCitationsByIds($citationIds);
				endif;

				foreach($arr as $key => $row):
					$qobj								=		new stdClass();
					$qobj->cheer_employee_award_id		=		(int)$row->cheer_employee_award_id;
					$qobj->employee						=		$this->setEmpObject($objEmployees,$row->employee_id);		
					$qobj->award_details				=		$award_obj[$row->award_id];
					if(!empty($row->citation_id)):
						$qobj->citation					=		(string)$citation_obj[$row->citation_id]??"";
					else:	
						$citation						=		array_column($nomination_obj[$row->cheer_employee_award_id],'citation');
						$citation						=		array_filter(array_unique($citation));
						$qobj->citation					=		(string)implode(', ',$citation);
					endif;
					$qobj->created_on					=		(string)$row->created_on;
					$qobj->points						=		(int)max(array_column($nomination_obj[$row->cheer_employee_award_id],'points'));
					$qobj->extra_points					=		(int)max(array_column($nomination_obj[$row->cheer_employee_award_id],'extra_points'));
					$qobj->request_type					=		(string)$row->approved == 1 ? "Approved":($row->approved == 2 ?"Rejected":"Pending");
					$qobj->request_datetime				=		(string)$row->approved_on;
					$qobj->nominations					=		$nomination_obj[$row->cheer_employee_award_id]??[];
					array_push($rObjects,$qobj);
				endforeach;
			endif;
		
			return $rObjects;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function checkEmployeeAward($object){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	[['term' => ['award_key'	=>	$object->award_key ]],['term' => ['employee_id'	=>	$object->employee_id ]]];
			
			if(!empty($object->frequency)):
				$tMonth		=	 	date('n');
				$tyear		=		date('Y');
				
				if($object->frequency == "Monthly"):
					$NJS			=	'doc.created_on.date.monthOfYear=='.$tMonth.' && doc.created_on.date.year=='.$tyear;
					$condition		=	['script' => ['script' => ['source' => "$NJS", 'lang' => 'painless']]];
					$params['body']['query']['bool']['filter'] = $condition;
				elseif($object->frequency == "Quarterly"):
					$dates			=		$this->common->getQuarterDates();
					$condition      =		[	['range'=>	[ 'created_on' => ['gte' => (string)$dates->start_date, "format"=> "yyyy-MM-dd HH:mm:ss"]]],
												['range'=>	[ 'created_on' =>   ['lte' => (string)$dates->end_date, "format"=> "yyyy-MM-dd HH:mm:ss"]]]];
					array_push($params['body']['query']['bool']['must'],$condition);
				endif;
			endif;
			
			$results	=	$this->es_search($params);
			return (isset($results['hits']['hits']) && ($results['hits']['total'])>0)?$results['hits']['hits'][0]['_source']['cheer_employee_award_id']:false;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getCheerEmployeeByIds($arr,$object) {
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['scroll']  	= 	'30s';
			$rObjects			=	[];
			
			$params['body']['query']['bool']	=	[	'must' => [ ['terms' => ['cheer_employee_award_id'=> array_values($arr)]]], ['term' => ['enabled'=>1]]];
			
			$results 	=	$this->es_search($params);
			$arr		=	[];
			$empIds		=	[];
			$new_arr	=	[];
			
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $pobject):
						$row						=		(object)$pobject['_source'];
						array_push($empIds,$row->approved_by);
						array_push($arr,$row);
					endforeach;
				endif;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}

			/* get employee information */
			$empIds			=	array_unique($empIds);
			$objEmployees	=	[];
			if (!empty($empIds)):
				$ES_Employee	=	new ES_Employee();
				$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
			endif;
			foreach ($arr as $key  => $row):
				$robject							=		new stdClass();
				$robject->comments		 			=		(int)count($row->comments);
				$robject->likes						= 		(object)[	'count'		=>	(int)count($row->likes),
																		'is_liked'	=>	(boolean)in_array($object->employee_id,$row->likes)];
			
				$robject->approver					=		new stdClass();
				if(!empty($row->approved_by)):
					$robject->approver				=		$this->setEmpObject($objEmployees, $row->approved_by);
					$robject->approver->approved_on	=		$row->approved_on;
				endif;
				$new_arr[$row->cheer_employee_award_id]		=	$robject;
			endforeach;
			return  $new_arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function updateEmployeeAward($object){
		try{
			
			$params = [];
			$params['index'] 		= 	$this->index;
			$params['type']			=	$this->type;
			$params['body']['query']['bool']				=	[ 'must' => ['term' => ['cheer_employee_award_id'=>(int)$object->cheer_employee_award_id]]];
			$params['body']['script']['inline']				=	'	ctx._source.citation_id		=	params.citation_id;
																	ctx._source.approved  		= 	params.approved; 
																	ctx._source.points 			= 	params.points;
																	ctx._source.extra_points 	= 	params.extra_points; 
																	ctx._source.approved_by 	= 	params.approved_by;
																	ctx._source.approved_on 	= 	params.approved_on; ';
			$params['body']['script']['params']				=	[	'citation_id' => (int)$object->citation_id, 'approved' => (int)$object->approved , 'extra_points' => (int)$object->extra_points , 'points' => (int)$object->points , 'approved_by' => (int)$object->approved_by , 'approved_on' => (string)$object->approved_on];
			
			return $this->es_updateByQuery($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getRewardCount($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	['must' => [['term' => ['approved'=>(int)1]], ['term' => ['enabled'=>(int)1]]]];
				
			if(!empty($object->reward_type) && $object->reward_type == "received"):
				array_push($params['body']['query']['bool']['must'], ['term' => ['employee_id' => (int)$object->employee_id]]);
			else:
				array_push($params['body']['query']['bool']['must'], ['term' => ['approved_by' => (int)$object->employee_id]]);	
			endif;
			
			$result	=	$this->es_count($params);
			return (!empty($result['count']))?$result['count']:0;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function canAwardHallOfFame($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['body']['query']['bool']['must']	=	[['term' => ['award_key'	=>	(string)"_STAR_EMP_MONTH" ]],['term' => ['employee_id'	=>	(int)$object->employee_id ]],['term' => ['approved'	=> (int)1 ]]];
			$params['body']['query']['bool']['must'][3]['bool']['should']			=	[   [ 'bool' 	=>     [ 'must' =>   [[ 'range' => ['created_on'	=> ['gte' => (string)$object->ldate, "format"=> "yyyy-MM"]]] , [ 'range' => ['created_on'	=> ['lt' => (string)$object->date, "format"=> "yyyy-MM"]]]]]],
																							[ 'bool'	=>	   [ 'must' =>   [[ 'range' => ['created_on'	=> ['gte' => (string)$object->lldate, "format"=> "yyyy-MM"]]] , [ 'range' => ['created_on'	=> ['lt' => (string)$object->ldate, "format"=> "yyyy-MM"]]]]]]];
			

			$result	=	$this->es_count($params);
			return (!empty($result['count']))?$result['count']:0;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getEnabledCheerAwards(){
		try{
			$params = [];
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['scroll']  	= 	'30s';
			
			
			$params['body']['query']['bool']['must']	=	['term' => ['enabled' => 1]];
			
			$results		=	$this->es_search($params);
			$cheerIds		=	[];
			
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						$row						=		(object)$object['_source'];
						array_push($cheerIds,$row->cheer_employee_award_id);
					endforeach;
				endif;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			$cheerIds	=	array_unique($cheerIds);
			return $cheerIds;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

}
?>
