<?php
class ES_Joinee_quicklink extends ESSource
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
	
	public function getQuicklinks(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]]]];
			$params['body']['sort']	=	[	['created_on'	=>	['order'	=>	'asc']],
											['link_id'		=>	['order'	=>	'asc']]];
			return $this->setOutput($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results){
		$arr 		=	[];
		if ($results['hits']['total']>0):
			foreach ($results['hits']['hits'] as $view_obj):
				if (!empty($view_obj['_source'])):
					$row					=	(object)$view_obj['_source'];
					$robject				=	new stdClass();
					$robject->link_id 		=	(int)$row->link_id;
					$robject->title 		=	(string)$this->common->entityDecode($row->title);
					$robject->web_link 		=	(string)$row->web_link;
					$robject->description 	=	(string)$this->common->entityDecode($row->description);
					array_push($arr, $robject);
				endif;
			endforeach;
		endif;
		return $arr;
	}
	
}
?>