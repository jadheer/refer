<?php 
class ES_Level extends ESSource 
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
	
	public function getLevels(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	[['term'	=>	['enabled' => (int)1]]];
			$params['body']['sort']						=	[ ['level_id'	=>	['order'	=>	'asc']]];
			
			$objects	=	$this->es_search($params);
			$results	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$row		=	(object)$object['_source'];
						$robject 	=	new stdClass();
						$robject->level_id	=	(int)$row->level_id;
						$robject->title		=	(string)$row->name ;
						array_push($results, $robject);
					endif;
				endforeach;
			endif;
			return $results;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getLevelId($title){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	['match'	=>	['name' => $title]];
			
			$object	=	$this->es_search($params);
			
			if (!empty($object['hits']['hits'][0]['_source'])):
				$row				=	(object)$object['hits']['hits'][0]['_source'];
				$robject 			=	new stdClass();
				$robject->level_id	=	(int)$row->level_id;
				$robject->name		=	(string)$row->name ;
				return $robject;
			endif;
			
			return new stdClass();
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
}
?>