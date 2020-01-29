<?php
class ES_Breakout_Update  extends ESSource
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
	

	/* for all breakout updates */
	public function getUpdate($object){
		try{
			$params 			= 	[];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
		
			$params['body']['query']['bool']['must'] 		= 	[['term' => ['enabled'=>1]], ['term' => ['breakout_id'=> (int)$object->breakout_id]] ];
			$params['body']['sort']							=	['update_id'=>['order'=> 'desc']];
			
			$results	=	$this->es_search($params);
			$arr		=	[];
			foreach ($results['hits']['hits'] as $result):
				$row		=	(object)$result['_source'];
				array_push($arr,$this->setObject($row,$object));
			endforeach;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setObject($row,$aobject){
		try{
			$robject	=	new stdClass();
			$robject->update_id    			= 	(int)$row->update_id;
			$robject->breakout_id    		= 	(int)$row->breakout_id;
			$robject->summary				= 	(string)$this->common->entityDecode($row->summary);
			$robject->created_on			= 	(string)$row->created_on;
			$robject->comment_count 		=	(int)count($row->comments);
			$robject->likes					=	(object)[	'count'		=>	(int)count($row->likes),
															'is_liked'	=>	(boolean)in_array($aobject->employee_id,$row->likes)];
			return $robject;
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