<?php
class ES_Social  extends ESSource
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
	
	
	public function getSocials(){
		try
		{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool'] = ['must' => [	'match' => ['enabled'=>1]] ] ;
			$params['body']['sort']	= [['social_order' => ['order' => 'asc']]];
			
			$results	=	$this->es_search($params);
			$arr		=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $social):
					if (!empty($social['_source'])):
						$row					=	(object)$social['_source'];
						$robject				=	new stdClass();
						$robject->identifier	=	(string)strtolower($row->social_identifier);
						$robject->url			=	(string)$row->social_url;
						array_push($arr,$robject);
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

}
?>