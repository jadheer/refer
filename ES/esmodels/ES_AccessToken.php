<?php 
class ES_AccessToken extends ESSource 
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

	public function addAccessToken($object){
		try
		{
			$params = [];
			$params['body']  		= 	[	'token_id'			=>	(int)$object->token_id,
											'employee_id'		=>	(int)$object->employee_id,
											'access_token'		=>	(string)$object->access_token,
											'third_party'		=>	(int)$object->third_party,
											'created_on'		=>	(string)$object->created_on];
				
			$params['index']  		= 	$this->index;
			$params['type']  		= 	$this->type;
			$params['id']    		= 	(int)$object->token_id;
			return $this->es_index($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}	
	
	public function getAccessTokenByIdentifier($accessToken){
		try
		{
			$params = [];
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	[['term'	=>	['access_token' => $accessToken]]];
			return $this->setAccessToken($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setAccessToken($objects){
		if ($objects['hits']['total']>0):
			foreach ($objects['hits']['hits'] as $object):
				if (!empty($object['_source'])):
					$robject 	=	new stdClass();
					$robject->token_id 		=	(int)$object['_source']['token_id'];
					$robject->employee_id 	=	(int)$object['_source']['employee_id'];
					$robject->access_token 	=	(string)$object['_source']['access_token'];
					$robject->third_party 	=	(string)$object['_source']['third_party'];
					$robject->created_on 	=	(string)$object['_source']['created_on'];
				endif;
			endforeach;
		endif;
		return (!empty($robject)?$robject:[]);
	}
	
	public function deleteThirdPartyAccessToken(){
		try
		{
			$params = [];
			$params['index'] 		= 	$this->index;
			$params['type']  		= 	$this->type;
	
			$params['body']['query']['bool']['must']			=	[['term'		=>	['third_party' => (int)1]]];
			$params['body']['query']['bool']['filter']['range']	=	['created_on'	=>	['lt' => (string)date('Y-m-d H:i:s', strtotime('-3 minutes'))]];
			return $this->es_deleteByQuery($params);
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