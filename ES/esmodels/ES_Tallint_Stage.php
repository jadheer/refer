<?php
class ES_Tallint_Stage extends ESSource
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
	

	public function getTallintStages(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	[['match'	=>	['enabled' => (int)1]]];
			$params['body']['sort']						=	['id'	=>	['order'	=>	'asc']];
			
			$objects	=	$this->es_search($params);
			$results	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$row		=	(object)$object['_source'];
						$robject 	=	new stdClass();
						$robject->id			=	(int)$row->id;
						$robject->title			=	(string)$row->title;
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