<?php 
class ES_Ijp_apply extends ESSource 
{
	var $index;
	var $type;
	var $stages;
	var $status;
	var $indentstatus_arr;
	
	public function __construct()
	{	
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
		$this->indentstatus_arr		=	 array("IN"=>"In-Process","CL"=>"Closed","CAN"=>"Cancelled","P"=>"Planned","OH"=>"On-Hold");
		
		try{
			$es_tallintstage		=		new ES_Tallint_Stage();
			$stages_obj				=		$es_tallintstage->getTallintStages();
			$this->stages			=		array_column($stages_obj,"title","id");
		
			$es_tallintstatus		=		new ES_Tallint_Status();
			$status_obj				=		$es_tallintstatus->getTallintStatus();
			$this->status			=		array_column($status_obj,"status","id");
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
		
	}

    public function __destruct()
	{	parent::__destruct();	}

	
	public function getEmployeeIjps($object){
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	[	'must' => [	['term' => ['enabled'=>1]],
																	['term' => ['employee_id'=>(int)$object->employee_id]]]];
			
			$params['body']['sort']				=	['created_on' => ['order'	=>	'desc']];
			
			return $this->setOutput($this->es_search($params),$object);
			
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getEmployeeIjpApply($employee_id){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['size'] 	= 	1000;
			$params['scroll']	=	'30s';
			$params['_source']	=	'ijp_id';
			
			$params['body']['query']['bool']	=	[  'must' => [['term' => ['employee_id'=>(int)$employee_id]]]];
			$results	=	$this->es_search($params);
			$rObject	=	[];
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						array_push($rObject, $object['_source']);
					endforeach;
				endif;
			$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return array_column($rObject,"ijp_id");
		}
		catch(Exception $e)
		{	$this->es_error($e);   }
	}
	

	private function setOutput($results, $object){
		try {
			$arr		=	$robjects	=	[];
			$arr_ids	=	[];
			foreach ($results['hits']['hits'] as $ijp_obj):
				if (!empty($ijp_obj['_source'])):
					$row							=	(object)$ijp_obj['_source'];
					$ijp_id    						= 	(int)$row->ijp_id;
					array_push($arr_ids,$ijp_id);
					array_push($robjects,$row);
				endif;
			endforeach;
			
			$es_ijp				=	new ES_Ijp();
			$ijp_arr			=	(array)$es_ijp->getIjpsByIds($arr_ids);	
			
			foreach ($robjects as $row):
				$index							=	$row->ijp_id;
				$robject						=	new stdClass();
				$robject->title     			= 	(string)$ijp_arr[$index]->title?(string)$this->common->entityDecode($ijp_arr[$index]->title):'';
				$robject->indent_number			= 	(string)$ijp_arr[$index]->indent_number?$ijp_arr[$index]->indent_number:'';
				$robject->indent_status			= 	(string)$ijp_arr[$index]->indent_status?$ijp_arr[$index]->indent_status:'';
				$robject->indent_status			= 	(string)isset($this->indentstatus_arr[$robject->indent_status])?$this->indentstatus_arr[$robject->indent_status]:"";
				$robject->applied_on  			= 	(string)$row->created_on;
				$robject->ijp_id    			= 	(int)$row->ijp_id;
				$robject->employee_id			= 	(int)$row->employee_id;
				$robject->profile_url			= 	(string)_MICROLANDER_URL."employees/".$object->employee_code."/microlander-profile";
				$robject->stage					= 	(string)isset($this->stages[$row->stage])?$this->stages[$row->stage]:"";
				$robject->status  				= 	(string)isset($this->status[$row->status])?$this->status[$row->status]:"";
				array_push($arr,$robject);
			endforeach;
			return $arr;
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
	
	
	public function update($object){
		try
		{
			$params 		= 	[];
			$params['body']	= 	[	'doc' 		=>	[	'ijp_employee_map_id'		=> (int)$object->ijp_employee_map_id,
														'ijp_id'					=>	(int)$object->ijp_id,
														'employee_id'				=>	(int)$object->employee_id,
														'candidate_number'			=>	(string)$object->candidate_number,
														'stage'						=>	(int)$object->stage,
														'status'					=> 	(int)$object->status,
														'created_on'				=>	(string)$object->created_on,
														'updated_on' 				=> 	(string)$object->updated_on,
														'enabled'					=>	(int)$object->enabled
															],
									'doc_as_upsert'	=>	true];
			
			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	(int)$object->ijp_employee_map_id;
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function insert($object){
		try
		{
			$params 		= 	[];
			$params['body']	= 		[	'ijp_employee_map_id'		=>  (int)$object->ijp_employee_map_id,
										'ijp_id'					=>	(int)$object->ijp_id,
										'employee_id'				=>	(int)$object->employee_id,
										'candidate_number'			=>	(string)"",
										'stage'						=>	(int)$object->stage,
										'status'					=> 	(int)$object->status,
										'created_on'				=>	(string)$object->created_on,
										'updated_on' 				=> 	(string)$object->updated_on,
										'enabled'					=>	(int)1];
			
			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->ijp_employee_map_id;
			return $this->es_index($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

}
?>