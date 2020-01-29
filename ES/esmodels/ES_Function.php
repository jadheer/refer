<?php 
class ES_Function extends ESSource 
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
    
	public function getById($id){
		try
		{
			$params = [];
			$params['index']  	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']  		= 	(int)$id;
	
	
			$object	=	$this->es_get($params);
			if (!empty($object['_source'])):
			return (object)$object['_source'];
			endif;
			return [];
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getFunctions(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	[['term'	=>	['enabled' => (int)1]]];
			$params['body']['sort']	=	[	['value'		=>	['order'	=>	'asc']]];
			
			$objects	=	$this->es_search($params);
			$results	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$row		=	(object)$object['_source'];
						$robject 	=	new stdClass();
						$robject->function_id	=	(int)$row->function_id;
						$robject->title			=	(string)$row->value;
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