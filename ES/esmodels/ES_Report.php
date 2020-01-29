<?php 
class ES_Report extends ESSource {
	var $component;
	public function __construct()
	{	
		parent::__construct();	
		$this->component = ['C' => (object)['index' => 'classifieds', 'primary' => 'news_id', 'docType' => 'classified'],
							'W' => (object)['index' => 'wall_posts', 'primary' => 'post_id', 'docType' => 'wall_post']];
	}

    public function __destruct()
	{	parent::__destruct();	}

	public function addReport($object){
		try{
			$params = [];
			$params['index']  	= 	$this->component[$object->type]->index;
			$params['type']  	= 	$this->component[$object->type]->docType;
			$params['id']    	= 	(int)$object->refer_id;
			
			$params['body']['script']['inline']	=	'ctx._source.reports.add(params.report)';
			$params['body']['script']['params']	=	['report' => (int)$object->employee_id];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	public function refresh($Index){
		try{
			$params = [];
			$params['index']  	= 	$this->component[$Index]->index;
			return $this->es_indices_refresh($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>