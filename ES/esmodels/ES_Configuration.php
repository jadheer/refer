<?php 
class ES_Configuration extends ESSource 
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

	public function getConfigurations(){
		try
		{
			$params = [];
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
 			$params['body']['query']['bool']['filter'] = [['terms' => ['setting_group' => ['ALL','SER','AND','IOS','ANDIOS','WEBSER']]]];
			$params['body']['query']['bool']['must']['match_all']	=	new stdClass();
			
			return $this->setConfiguration($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setConfiguration($objects){
		$robjects	=	[];
		if ($objects['hits']['total']>0){
			foreach ($objects['hits']['hits'] as $object){
				if (!empty($object['_source'])){
					array_push($robjects, (object)array('setting_key'	=>	$object['_source']['setting_key'],
														'setting_value'	=>	$object['_source']['setting_value']));
				}
			}
		}
		return $robjects;
	}
}
?>