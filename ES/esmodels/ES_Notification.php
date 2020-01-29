<?php 
class ES_Notification extends ESSource{
	var $index;
	var $type;
	public function __construct(){	
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}

    public function __destruct()
	{	parent::__destruct();	}

	public function getNotificationUnreadCount($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
	
			$params['body']['query']['bool']['must'] 		=  [['term' => ['enabled' => (int)1]],
																['range'=> ['updated_on' => ['gt' => (string)$object->accessed_on, "format"=> "yyyy-MM-dd HH:mm:ss"]]],
																['bool'	=>	['should'	=>	[['term' => ['employees' => (int)$object->employee_id]], ['term' => ['to_all' => (boolean)true]]]]]];
			$params['body']['query']['bool']['must_not'] 	= 	[['term' => ['exclude' => (int)$object->employee_id]]];
// 			$params['body']['query']['bool']['filter']['bool']['should'] 	= 	[['term' => ['employees' => (int)$object->employee_id]], ['term' => ['to_all' => (boolean)true]]];
			
			$result	=	$this->es_count($params);
			return (!empty($result['count']))?$result['count']:0;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getNotifications($object){
		try{
			$params = [];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
	
			$params['body']['query']['bool']['must'] 		=  [['term' => ['enabled' => (int)1]],
																['bool'	=>	['should'	=>	[['term' => ['employees' => (int)$object->employee->employee_id]], ['term' => ['to_all' => (boolean)true]]]]]];
			$params['body']['query']['bool']['must_not'] 	= 	[['term' => ['exclude' => (int)$object->employee->employee_id]]];
			$params['body']['sort']	=	[['updated_on' => ['order' => 'desc']]];
			
			return $this->setOutput($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getLatestAskmlNotification($object){
		try{
			$params 			= 	[];
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']['must'] 			=  [    ['term' => ['enabled' => (int)1]],
																		['terms' => ['identifier' => $object->identifiers]],
																		['term' => ['reference_id' => (int)$object->employee_id]],
																		];
			
			$params['body']['sort']								=	[['created_on' => ['order' => 'desc']]];
			
			$result		=	$this->es_search($params);
			$object		=	new stdClass();
			if ($result['hits']['total']>0):
				$obj	=	$result['hits']['hits'][0];
				if (!empty($obj['_source'])):
					$row		=	(object)$obj['_source'];
					$payload	=	json_decode(base64_decode($row->payload));
					
					$object->notification_id	=		(int)$row->notification_id;
					$object->timestamp			=		(string)$row->updated_on;
					$object->identifier			=		(string)$row->identifier;
					$object->askml_popup		=		(int)$row->askml_popup;
					
					if (!empty($payload->title)):
						$object->title		=		(string)str_replace( chr( 194 ) . chr( 160 ), '', trim(html_entity_decode($payload->title, ENT_QUOTES)));
						$object->body		=		(string)str_replace( chr( 194 ) . chr( 160 ), '', trim(html_entity_decode($payload->body, ENT_QUOTES)));
						$object->data		=		(object)$payload->data;
					endif;
				endif;
			endif;
			return $object;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}


	private function setOutput($results){
		try{
			$arr 		=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						$payload	=	json_decode(base64_decode($row->payload));
						if ($payload===false):
							continue;
						else:
							$object		=	new stdClass();
							$object->notification_id	=	(int)$row->notification_id;
							$object->timestamp			=	(string)$row->updated_on;
							
							if (!empty($payload->title)):
								$object->title		=	(string)str_replace( chr( 194 ) . chr( 160 ), '', trim(html_entity_decode($payload->title, ENT_QUOTES)));
								$object->body		=	(string)str_replace( chr( 194 ) . chr( 160 ), '', trim(html_entity_decode($payload->body, ENT_QUOTES)));
								$object->data		=	(object)$payload->data;
								array_push($arr, $object);
							endif;
						endif;
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function insert($object){
		try{
			$params = [];
			$params['body']  		= [	'notification_id'	=>	(int)$object->notification_id,
										'created_by'		=>	(int)$object->created_by,
										'updated_by'		=>	(int)$object->updated_by,
										'title'				=>	(string)$this->common->entityEncode($object->title),
										'body'				=>	(string)$this->common->entityEncode($object->body),
										'imagepath'			=>	(string)$object->imagepath,
										'identifier'		=>	(string)$object->identifier,
										'reference_id'		=>	(int)$object->reference_id,
										'is_custom'			=>	(int)$object->is_custom,
										'created_on'		=>	(string)$object->created_on,
										'updated_on'		=>	(string)$object->updated_on,
										'payload'			=>	(string)base64_encode($object->payload),
										'enabled'			=>	(int)$object->enabled,
										'askml_popup'		=>	(int)!empty($object->askml_popup)?$object->askml_popup:0,
										'to_all'			=>	(boolean)$object->to_all,
										'employees'			=>	(boolean)!empty($object->employees)?$object->employees:[],
										'exclude'			=>	(boolean)!empty($object->exclude)?$object->exclude:[]];
				
			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->notification_id;
			
			return $this->es_index($params);
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
	
	public function deleteEmployee($object){
		try{
			$params 		 =  [];
			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = (int)$object->notification_id;
			
			$params['body']['script']['inline']	=	"ctx._source.employees.removeAll(Collections.singleton(params.employees))";
			$params['body']['script']['params']	=	['employees' => (int)$object->employee_id];
			
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