<?php
class ES_Cheer_Employee_Nomination extends ESSource {

	public function __construct()
	{	parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}

	public function __destruct()
	{	parent::__destruct();	}


	public function addNomination($object){
		try
		{
			$params 	= 	[];
			$params['body']  		= 	[	'nomination_id'					=>	(int)$object->nomination_id,
											'award_id'						=>	(int)$object->award_id,
											'employee_id'					=>	(int)$object->employee_id,
											'nominated_by'					=>	(int)$object->nominated_by,
											'citation_id'					=>	(int)$object->citation_id,
											'nomination_type'				=>	(string)$object->nomination_type,
											'approval_type'					=>	(string)$object->approval_type,
											'approved'						=>	(int)$object->approved,
											'nomination_for'				=>	(string)$object->nomination_for,
											'created_on'					=>	(string)$object->created_on,
											'cheer_employee_award_id'		=>	(int)$object->cheer_employee_award_id,
											'points'						=>	(int)$object->points,
											'extra_points'					=>	(int)$object->extra_points
											];


			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->nomination_id;
			return  $this->es_index($params);
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}
	
	public function getPendingApprovalCount($object){
		try{
			$params = [];
			$objDeptMembers		=	[];
			$objReportees		=	[];
			$params['index'] 	= 	$this->index;
			$params['size'] 	= 	0;
			$can_approval		= 	0;		
			$robject			=	new stdClass();
			$cheer_ids			=	[];
			
			//get employees from the given departments
			if(!empty($object->departments)):
				$es_employee 		=		new ES_Employee();
				$objDeptMembers		=		$es_employee->getEmployeesByDeptIds($object->departments);
				
				$objDeptMembers		=		array_column($objDeptMembers,'employee_id');
			endif;
			
			//get direct reportees
			$es_employee 			=		new ES_Employee();
			$objReportees			=		$es_employee->getReportees($object);
			$objReportees			=		array_column($objReportees,'employee_id');
		
			$can_approval			=       (empty($object->departments) && empty($objReportees))?false:true;
			
			$es_reward				=		new ES_Cheer_Employee_Award();
			$cheer_ids				=		$es_reward->getEnabledCheerAwards();
			$cheer_ids				= 		array_values($cheer_ids);
		
			$params['body']['query']['bool']['should']			=	[ [ 'bool' 	=>		[ 'must' =>  [  ['terms' => [ 'cheer_employee_award_id' => $cheer_ids ]] , [ 'term' => ['approved'	=>	(int)0 ]] , [ 'terms' => ['nominated_by'	=>	$objDeptMembers ]],['terms' => ['approval_type'	=>	['NM_DEPT_HD','DEPT_HD']]]]]],
																		[ 'bool'	=>		[ 'must' =>	 [ ['terms' => [ 'cheer_employee_award_id' => $cheer_ids ]]  , [ 'term' => ['approved'	=>	(int)0 ]] ,[ 'terms' => ['nominated_by'	=>	$objReportees ]],['term' => ['approval_type'	=>	'RM']]]]]];
			
			$params['body']['aggs']['approvals']				=	['cardinality'=> ['field' =>'cheer_employee_award_id']];
			$result						=	$this->es_search($params);
			$robject->count				=	(int)!empty($result['aggregations']['approvals']['value'])?$result['aggregations']['approvals']['value']:0;
			$robject->can_approve		=	(boolean)$can_approval;
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getNominationForApprovals($object){
		try{
			$params = [];
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['scroll']  	= 	'30s';

			$objDeptMembers		=	[];
			$objReportees		=	[];
			
			//get employees from the given departments
			if(!empty($object->departments)):
				$es_employee 		=		new ES_Employee();
				$objDeptMembers		=		$es_employee->getEmployeesByDeptIds($object->departments);
			
				$objDeptMembers		=		array_column($objDeptMembers,'employee_id');
			endif;
			
			//get direct reportees
			$es_employee 			=		new ES_Employee();
			$objReportees			=		$es_employee->getReportees($object);
			$objReportees			=		array_column($objReportees,'employee_id');

		
			$params['body']['query']['bool']['should']	=	[[ 'bool' =>  	[ 'must' =>  [[ 'terms' => ['nominated_by'	=>	$objDeptMembers ]],['terms' => ['approval_type'	=>	['NM_DEPT_HD','DEPT_HD']]]]]],
															 [ 'bool'=>	   [ 'must' =>	 [[ 'terms' => ['nominated_by'	=>	$objReportees ]],['term' => ['approval_type'	=>	'RM']]]]]];
			
			
			
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
	
	/* */
	public function getNominations($arr){
		try{
			$params = [];
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['scroll']  	= 	'30s';
			
			$params['body']['query']['bool']['must']	=	['terms' => ['cheer_employee_award_id'	=>	$arr ]];
			$params['body']['sort']						=	['created_on' => ['order'	=>	'desc']];
			
			$results	=	$this->es_search($params);
			$rObjects	=	[];
			$empIds		=	[];
			$citationIds	=	[];
			
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						$row						=		(object)$object['_source'];
						$robject					=		new stdClass();
						$robject->nominated_by		= 		(int)$row->nominated_by;
						$robject->nominated_on		= 		(string)$row->created_on;
						$robject->points					= 		(int)$row->points;
						$robject->extra_points				= 		(int)$row->extra_points;
						$robject->citation					= 		(int)$row->citation_id;
						$robject->cheer_employee_award_id	= 		(int)$row->cheer_employee_award_id;
						array_push($empIds,$row->nominated_by);
						array_push($citationIds,$row->citation_id);
						array_push($rObjects,$robject);
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
			
			//get citation information
			$citationIds	=	array_unique($citationIds);
			$objCitations	=	[];
			if (!empty($citationIds)):
				$ES_award			=		new ES_Cheer_Award();
				$objCitations		=		$ES_award->getCitationsByIds($citationIds);
			endif;
			$arr	=	[];
			foreach ($rObjects as $robject):
				$robject->nominated_by				= 		$this->setEmpObject($objEmployees, $robject->nominated_by);
				$robject->citation					= 		$objCitations[$robject->citation]??'';
				$arr[$robject->cheer_employee_award_id][]	=		$robject;
			endforeach;
			return $arr;
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

	// get employeeIds of all nominations for particular award from current logged in employee
	public function getNominatedEmployeesByAwardId($object){
		try{
			$params = [];
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['scroll']  	= 	'30s';
			$params['_source']  = 	'employee_id';
			
			$params['body']['query']['bool']['must']	=	[['term' => ['award_id'	=>	$object->award_id ]],['term' => ['nominated_by'	=>	$object->nominated_by ]]];
			
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
			$arr		=	[];
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						$row						=		(object)$object['_source'];
						array_push($arr,$row->employee_id);
					endforeach;
				endif;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	//get all award nominations from current logged in employee and its status
	public function getEmployeeNominations($object,$master_obj){
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']['must']	=	[['term' => ['nominated_by'	=>	$object->employee_id ]]];
			$params['body']['sort']						=	[['created_on' => ['order'	=>	'desc']], ['nomination_id' => ['order' => 'desc']]];
			
			$results	=	$this->es_search($params);
			$arr		=	[];
			if ($results['hits']['total']>0):
				$empIds		=	[];
				$nomination_arr	=	[];
				$cheerIds		=	[];
				$allcheerIds	=	[];
				$citationIds	=	[];
				
				$es_icon				=		new ES_Icon();
				$objIcons				=		$es_icon->getIcons();
				
				/*get the employee ids & award ids */
				foreach ($results['hits']['hits'] as $award_obj):
					if (!empty($award_obj['_source'])):
						$row	=	(object)$award_obj['_source'];
						array_push($empIds, $row->employee_id);
						array_push($citationIds,$row->citation_id);
						if($row->approved==1):
							array_push($cheerIds, $row->cheer_employee_award_id);
						endif;
						array_push($allcheerIds, $row->cheer_employee_award_id);
					endif;
				endforeach;
			
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
				
				// get award informations
				$ES_cheer				=		new ES_Cheer_Employee_Award();
				$objCheerAwards			=		$ES_cheer->getCheerEmployeeByIds($cheerIds,$object);

				$es_nomination			=		new ES_Cheer_Employee_Nomination();
				$nomination_obj			=		$es_nomination->getNominations($allcheerIds);
				
				foreach ($results['hits']['hits'] as $award_obj):
					if (!empty($award_obj['_source'])):
						$row								=		(object)$award_obj['_source'];
						//show awards of enabled only
						if(!empty($objAwards[$row->award_id])):
							$robject							=		new stdClass();
							$robject->cheer_employee_award_id	= 		(int)$row->cheer_employee_award_id;
							$robject->nomination_id				= 		(int)$row->nomination_id;						
							$robject->created_on				=		(string)$row->created_on;
							$robject->award_details				= 		$objAwards[$row->award_id];
							$robject->timeframe					=		new stdClass();
							if($robject->award_details->award_frequency == "Monthly"):
								$robject->timeframe					=		(object)['start_date'=> date('Y-m-d', strtotime($row->created_on." -1 month"))];
							elseif($robject->award_details->award_frequency == "Quarterly"):
								$robject->timeframe					=		$this->common->getPrevisiousQuarterDates($row->created_on);
							endif;
							$robject->award_details->show_certificate			= 		(boolean) false;	
							$robject->citation					=		(string)$citation_obj[$row->citation_id]??"";
							$robject->employee					=		$this->setEmpObject($objEmployees, $row->employee_id);
							$robject->points					=		(int)$row->points;
							$robject->extra_points				=		(int)$row->extra_points;
							$robject->request_type				=		(string)$row->approved == 1 ? "Approved":($row->approved == 2 ?"Rejected":"Pending");
							//show comments & likes for approved Nominations	
							if($row->approved==1 && isset($objCheerAwards[$row->cheer_employee_award_id])):
								$robject->approver					=		$objCheerAwards[$row->cheer_employee_award_id]->approver;
								
								$robject->comment_count 			=		(int)$objCheerAwards[$row->cheer_employee_award_id]->comments;
								$robject->likes						= 		(object)$objCheerAwards[$row->cheer_employee_award_id]->likes;

							endif;
							$robject->nominations					=		$nomination_obj[$row->cheer_employee_award_id]??[];
							array_push($arr,$robject);
						endif;
					endif;
				endforeach;
			endif;
			return  $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function checkNomination($object){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	[['term' => ['award_id'	=>	$object->award_id ]],['term' => ['nominated_by'	=>	$object->nominated_by ]],['term' => ['employee_id'	=>	$object->employee_id ]]];
			
		
			if(!empty($object->frequency)):
				$tMonth		=	 	date('n');
				$tyear		=		date('Y');
				
				if($object->frequency == "Monthly"):
					$NJS	=	'doc.created_on.date.monthOfYear=='.$tMonth.' && doc.created_on.date.year=='.$tyear;
					$condition	=	['script' => ['script' => ['source' => "$NJS", 'lang' => 'painless']]];
					$params['body']['query']['bool']['filter'] = $condition;
				elseif($object->frequency == "Quarterly"):
					$dates			=		$this->common->getQuarterDates();
					$condition      =		[	['range'=>	[ 'created_on' => ['gte' => (string)$dates->start_date, "format"=> "yyyy-MM-dd HH:mm:ss"]]],
												['range'=>	[ 'created_on' =>   ['lte' => (string)$dates->end_date, "format"=> "yyyy-MM-dd HH:mm:ss"]]]];
					array_push($params['body']['query']['bool']['must'],$condition);
				endif;
				
			endif;
			$results	=	$this->es_search($params);
			return (isset($results['hits']['hits']) && ($results['hits']['total'])>0)?true:false;
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	

	public function updateNominations($object){
		try{
			
			$params = [];
			$params['index'] 		= 	$this->index;
			$params['type']			=	$this->type;
			$params['body']['query']['bool']				=	[ 'must' => ['term' => ['cheer_employee_award_id'=>(int)$object->cheer_employee_award_id]]];
			$params['body']['script']['inline']				=	'ctx._source.approved = params.approved; ';
			$params['body']['script']['params']				=	[ 'approved' => $object->approved ];
			
			return $this->es_updateByQuery($params);
			
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getTopNominators($object){
		try{
			$params = [];
			$params['size']  	= 	0;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']			=	['term' => ['approved'	=>	(int)1 ]];
			$params['body']['aggs']['nominations']				=	['terms'=> ['field' =>'nominated_by']];

			$results	=	$this->es_search($params);	
			$arr		=	[];$empIds = [];
			if (!empty($results['aggregations']['nominations']['buckets'])):
				$rank = 0;$count=0;
				foreach ($results['aggregations']['nominations']['buckets'] as $obj):
					if (!empty($obj['key'])):
						$row							=		(object)$obj;
						$robject						=		new stdClass();
						$robject->type					=		"nominator";
						$robject->nomination_count		=		(int)$row->doc_count;
						$robject->topper_id				=		(int)$row->key;
						$rank							=		($count>0 && $count == $robject->nomination_count )?$rank:$rank+1;
						$count							=		$row->doc_count;
						$robject->rank					=		(int)$rank;
						$robject->employee				=		new stdClass();
						array_push($arr, $robject);
						array_push($empIds, $robject->topper_id);
					endif;
				endforeach;
			
				/* get employee information */
				if (!empty($empIds)):
					$empIds				=		array_unique($empIds);
					$objEmployees		=		[];
					$ES_Employee		=		new ES_Employee();
					$objEmployees		=		array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
					$ES_Nominator		=		new ES_Cheer_Top_Nominator();
					$objNominators		=		array_column($ES_Nominator->getNominatorsByIds($empIds),NULL,"nominator_id");
				endif;

				foreach ($arr  as $key => $robject):
					$robject->employee				= 		$this->setEmpObject($objEmployees, $robject->topper_id);
					$robject->comment_count 		=		(int)count($objNominators[$robject->topper_id]['comments']);
					$robject->likes					=		(object)[	'count'		=>	(int)count($objNominators[$robject->topper_id]['likes']),
																		'is_liked'	=>	(boolean)in_array($object->employee_id,$objNominators[$robject->topper_id]['likes'])?true:false];
				endforeach;
			endif;
			return $arr;
			
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
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

}
?>
