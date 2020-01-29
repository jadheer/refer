<?php 
class ES_Device extends ESSource 
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
    
	public function getDeviceByAccessToken($accessToken){
		try
		{
			$params = [];
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	[['match'	=>	['access_token' => $accessToken]]];
			return $this->setDevice($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getDeviceByIdentifier($Identifier){
		try
		{
			$params = [];
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	[['match'	=>	['device_identifier' => $Identifier]]];
			return $this->setDevice($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setDevice($objects){
		if ($objects['hits']['total']>0):
			foreach ($objects['hits']['hits'] as $object):
				if (!empty($object['_source'])):
					$robject 	=	new stdClass();
					$robject->device_id 			=	(int)$object['_source']['device_id'];
					$robject->employee_id 			=	(int)$object['_source']['employee_id'];
					$robject->device_type 			=	(string)$object['_source']['device_type'];
					$robject->device_os 			=	(string)$object['_source']['device_os'];
					$robject->device_os_version 	=	(string)$object['_source']['device_os_version'];
					$robject->device_identifier 	=	(string)$object['_source']['device_identifier'];
					$robject->app_version 			=	(string)$object['_source']['app_version'];
					$robject->notification_token 	=	(string)$object['_source']['notification_token'];
					$robject->access_token 			=	(string)$object['_source']['access_token'];
				endif;
			endforeach;
		endif;
		return (!empty($robject)?$robject:[]);
	}
	
	public function insert($object){
		try
		{
			$params = [];
			$params['body']  		= 	[	'device_id'			=>	(int)$object->device_id,
											'employee_id'		=>	(int)$object->employee_id,
											'device_type'		=>	(string)$object->device_type,
											'device_os'			=>	(string)$object->device_os,
											'device_os_version'	=>	(string)$object->device_os_version,
											'device_identifier'	=>	(string)$object->device_identifier,
											'app_version'		=>	(string)$object->app_version,
											'notification_token'=>	(string)$object->notification_token,
											'access_token'		=>	(string)$object->access_token,
											'created_on'		=>	(string)$object->created_on,
											'updated_on'		=>	(string)$object->updated_on,
											'enabled'			=>	(int)$object->enabled];
			
			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->device_id;
			return $this->es_index($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function clearDeviceToken($accessToken){
		try
		{
			$params = [];
			$params['index'] 		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['conflicts'] 	= 	'proceed';
				
			$params['body']['query']['bool']['must']	=	[['term'	=>	['access_token' => (string)$accessToken]]];
			$params['body']['script']['inline']			=	'ctx._source.access_token = ""';
			
			return $this->es_updateByQuery($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function update($object){
		try
		{
			$params = [];
			$params['body']	= [	'doc' 			=>	[	'device_id'			=>	(int)$object->device_id,
														'employee_id'		=>	(int)$object->employee_id,
														'device_type'		=>	(string)$object->device_type,
														'device_os'			=>	(string)$object->device_os,
														'device_os_version'	=>	(string)$object->device_os_version,
														'device_identifier'	=>	(string)$object->device_identifier,
														'app_version'		=>	(string)$object->app_version,
														'notification_token'=>	(string)$object->notification_token,
														'access_token'		=>	(string)$object->access_token,
														'created_on'		=>	(string)$object->created_on,
														'updated_on'		=>	(string)$object->updated_on,
														'enabled'			=>	(int)$object->enabled],
								'doc_as_upsert'	=>	true];
			
			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	(int)$object->device_id;
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function updateEmpDeviceMapping($object){
		try
		{
			$params = [];
			$params['body']	= [	'doc' => [	"employee_id" 			=>	(int)$object->employee_id,
											"notification_token" 	=>	(string)$object->notification_token,
											"access_token" 			=>	(string)$object->access_token,
											"app_version" 			=>	(string)$object->app_version]];
		
			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	(int)$object->device_id;
			return $this->es_update($params);
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
	
	public function delete($id){
		try
		{
			$params = [];
			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$id;
			return $this->es_delete($params);
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