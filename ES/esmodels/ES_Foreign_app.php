<?php 
class ES_Foreign_app extends ESSource 
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

	
	public function getForeignApps(){
		try
		{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	[['term'	=>	['enabled' => (int)1]]];
			
			$objects	=	$this->es_search($params);
			$results	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$object	=	(object)$object['_source'];
						$results[$object->app_identifier]	=	[];
						if (!empty($object->whitelists)):
							$results[$object->app_identifier]	=	array_column($object->whitelists, 'identifier');
						endif;
					endif;
				endforeach;
			endif;
			return $results;
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