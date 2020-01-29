<?php 
class ES_Preference extends ESSource 
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

	public function getPreferences(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['match_all']	=	new stdClass();
				
			$objects	=	$this->es_search($params);
			$results	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$object	=	(object)$object['_source'];
						$robject 	=	new stdClass();
						$robject->preference_id		=	(int)$object->preference_id;
						$robject->feature 			=	(string)$object->feature;
						$robject->feature_key 		=	(string)$object->feature_key;
						$robject->default_setting	=	(int)$object->default_setting;
						$robject->enabled 			=	(int)$object->default_setting;
						array_push($results, $robject);
					endif;
				endforeach;
			endif;
			return $results;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>