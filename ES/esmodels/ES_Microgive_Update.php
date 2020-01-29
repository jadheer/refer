<?php
class ES_Microgive_Update  extends ESSource
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


	/* for all  updates */
	public function getUpdate($object){
		try{
			$params 			= 	[];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;

			$params['body']['query']['bool']['must'] 		= 	[
																	['term' => ['enabled'=>1]],
																	['term' => ['initiative_detail_id'=> (int)$object->initiative_detail_id]]
																];

			$params['body']['query']['bool']['should'] 		= 	[
																	['nested'  =>  [    'path'          => 'comments',
										    											'query'         =>  ['bool' => ['must' => ['term' => ['comments.enabled' => (int)1]]]],
										    											'inner_hits'    =>  ['from' =>  (int)0, 'size'  =>  (int)10000]]]
																];


			$params['body']['sort']							=	['update_id'=>['order'=> 'desc']];

			$results			=	$this->es_search($params);



			$arr				=	[];
			if (!empty($results['hits']['total'])):

				foreach ($results['hits']['hits'] as $result):
					$row			=	(object)$result['_source'];
    				$inner_doc		=	$result['inner_hits']??[];
					$robject						=	new stdClass();
					$robject->update_id    			= 	(int)$row->update_id;
					$robject->initiative_id			= 	(int)$row->initiative_id;
					$robject->initiative_detail_id	= 	(int)$row->initiative_detail_id;
					$robject->summary				= 	(string)$this->common->entityDecode($row->summary);
					$robject->created_on			= 	(string)$row->created_on;
					$robject->comment_count 		=	(int)$inner_doc['comments']['hits']['total'];
					array_push($arr,$robject);
				endforeach;

    		endif;

			return $arr;
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
