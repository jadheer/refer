<?php
class ES_Cheer_Employee_Point extends ESSource {

	public function __construct()
	{	parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}

	public function __destruct()
	{	parent::__destruct();	}
		
	public function addEmployeePoint($object){
		try
		{
			$params 	= 	[];
			$params['body']  		= 	[	'cheer_point_id'				=>	(int)$object->cheer_point_id,
											'employee_id'					=>	(int)$object->employee_id,
											'points'						=>	(int)$object->points,
											'extra_points'					=>	(int)$object->extra_points,
											'current_points'				=>	(int)$object->current_points,
											'points_from'					=>	(string)$object->points_from,
											'refer_id'						=>	(int)$object->refer_id,
											'type'							=>	(string)$object->type,
											'created_on'					=>	(string)$object->created_on
										];


			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->cheer_point_id;
			return  $this->es_index($params);
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}
	
	public function getCurrentBalance($object){
		try{
			$params = [];
			$params['from']  		= 		0;
			$params['size']  		= 		1;
			$params['index'] 		= 		$this->index;
			$params['type'] 		= 		$this->type;
			$params['_source'] 		= 		'current_points';
			
			$params['body']['query']['bool']['must']	=	[['term' => ['employee_id'	=>	$object->employee_id ]]];
			$params['body']['sort']						=	[['created_on' => ['order'	=>	'desc']]];
			$result	=	$this->es_search($params);

			if ($result['hits']['total']>0):
				$robject	=	(object)$result['hits']['hits'][0]['_source'];
			endif;
			return !empty($robject)?$robject->current_points:0;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getTopRecipients($object){
		try{
			$params = [];
			$params['size']  	= 	0;
			$params['index'] 	= 	$this->index;
			$ids				=	[0]; // when refer_id is 0 for admins
			
			//get all enabled rewards to calculate total points
			$es_reward			=		new ES_Cheer_Employee_Award();
			$ids				=		$ids + $es_reward->getEnabledCheerAwards();
			$ids				= 		array_values($ids);
			
			$params['body']['query']['bool']['must']			=	[ ['terms' => ['points_from'	=> ['AW', 'EX']]] , ['term' => ['type' => 'C' ]], ['terms' => [ 'refer_id' => $ids ]]];
			$params['body']['aggs']['points']					=	['terms'	=> 	[	'field'	 	=>	'employee_id'  , "order" => [ "total_points"=> "desc"  ]] , 
																						'aggs' 		=> 	[	'total_points' => [ 'sum'=> [	'script' =>  [  "lang"=> "painless", 
																															 								"inline"=> "doc['points'].value+doc['extra_points'].value"]]]]];
			
			$results	=	$this->es_search($params);
			$arr		=	[];$empIds = [];
			if (!empty($results['aggregations']['points']['buckets'])):
				$rank = 0;$count=0;
				foreach ($results['aggregations']['points']['buckets'] as $obj):
					if (!empty($obj['key'])):
						$row							=		(object)$obj;
						$robject						=		new stdClass();
						$robject->type					=		"recipient";
						$robject->total_points			=		(int)$row->total_points['value'];
						$robject->topper_id				=		(int)$row->key;
						$rank							=		($count>0 && $count == $robject->total_points )?$rank:$rank+1;
						$count							=		$robject->total_points;
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
					$ES_Recipients		=		new ES_Cheer_Top_Recipient();
					$objRecipients		=		array_column($ES_Recipients->getRecipientsByIds($empIds),NULL,"recipient_id");
				endif;
				foreach ($arr  as $key => $robject):
					$robject->employee				= 		$this->setEmpObject($objEmployees, $robject->topper_id);
					$robject->comment_count 		=		(int)count($objRecipients[$robject->topper_id]['comments']);
					$robject->likes					=		(object)[	'count'		=>	(int)count($objRecipients[$robject->topper_id]['likes']),
																		'is_liked'	=>	(boolean)in_array($object->employee_id,$objRecipients[$robject->topper_id]['likes'])?true:false];
				endforeach;
			endif;
			return $arr;		
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	
	
	private function setEmpObject($objEmployees,$id){
		try{
			
			$emp						=	new stdClass();
			if(isset($objEmployees[$id])):
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
			
			endif;
			return $emp;
			
		}catch(Exception $e){
			$this->es_error($e);
		}
	}
	
	
	public function getLastMonthPoints($object){
		try{
			$params = [];
			$params['index'] 		= 		$this->index;
			$params['type'] 		= 		$this->type;
			$params['size'] 		= 		0;
			
			$params['body']['query']['bool']['must']		=		[['term' => ['employee_id'	=>	$object->employee_id ]],
																	 ['range'=>	[ 'created_on' => [ 'lte' => (string)$object->end_date ,"format"=> "yyyy-MM-dd HH:mm:ss"]]] ,
																	 ['range'=>	[ 'created_on' => [ 'gt' => (string)$object->start_date ,"format"=> "yyyy-MM-dd HH:mm:ss"]]]];
			
			
			if ($object->point_type == "redeemed"):
				array_push($params['body']['query']['bool']['must'], ['term' => ['points_from' => 'RD']]);
			elseif ($object->point_type == "credited"):
				array_push($params['body']['query']['bool']['must'], ['term' => ['type' => 'C']]);
			else:
				array_push($params['body']['query']['bool']['must'], ['term' => ['type' => 'D']]);
			endif;
			
 			$params['body']['aggs']['total_points']					= 	[ 'sum'=> [	'script' =>  [  "lang"=> "painless",
 																									"inline"=> "doc['points'].value+doc['extra_points'].value"]]];
			$result		=	$this->es_search($params);
			return $result['aggregations']['total_points']['value']??0 ;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getEmployeeLedger($pobject) {
		try{
			$params = [];
			$params['from']		=	(int)$pobject->start??0;
			$params['size']  	= 	(int)$pobject->end??1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=		[ 'must' => ['term' => ['employee_id'=> (int) $pobject->employee_id]]];
			$params['body']['sort']				=		[['created_on' => ['order'	=>	'desc']]];
			
			$results 	=	$this->es_search($params);
			$arr		=	[];
			$referIds	=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $point_obj):
					if (!empty($point_obj['_source'])):
						$row						=		(object)$point_obj['_source'];
						$object						=		new stdClass();
						$object->points				=		(int)$row->points + $row->extra_points;
						$object->title				=		(string)($row->type == "C")?"Credited":"Debited";
						$object->title				=		(string)($row->points_from	== "RD")?"Redeemed":$object->title;
						$object->refer_id			=		(int)$row->refer_id;
						$object->datetime			=		(string)$row->created_on;
						array_push($arr,$object);
						if($row->points_from == "RD"):
							array_push($referIds,$row->refer_id);
						endif;
					endif;
				endforeach;		

				//from db
				$cheer		=	new CheerAward();
				$refer_obj	=	$cheer->getreferDetails($referIds , $pobject->employee_id);
				foreach ($arr as $point_obj):
					$point_obj->details		=	new stdClass();
					if($point_obj->title == "Redeemed"):
						$point_obj->details				=	$refer_obj[$point_obj->refer_id]??new stdClass();
					endif;
				endforeach;	
			endif;
			return  $arr;
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
