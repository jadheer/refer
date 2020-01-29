<?php 
class ES_Classified_category extends ESSource 
{
	var $index;
	var $type;
	public function __construct()
	{	
		parent::__construct();
		$this->index	=	"classified_categories";
		$this->type		=	"category";}

    public function __destruct()
	{	parent::__destruct();	}

	public function getCategories(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$params['body']['query']['bool']['must']	=	[['term' =>	['enabled' => (int)1]]];
			$params['body']['sort']						= 	[['category_id' => ['order' => 'asc']]];
			
			$objects	=	$this->es_search($params);
			$results	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$row		=	(object)$object['_source'];
						$robject 	=	new stdClass();
						$robject->category_id		=	(int)$row->category_id;
						$robject->post_title		=	(string)$this->common->entityDecode($row->post_title);
						$robject->explore_title		=	(string)$this->common->entityDecode($row->explore_title);
						$results[$row->category_id]		=	$robject;
					endif;
				endforeach;
			endif;
			return $results;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getCategoryBySearch($query){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$search_string		=	"*".trim($query)."*";
			$params['body']['query']['bool']['must']	=	[['term' =>	['enabled' => (int)1]],
															 ['wildcard' => ['explore_text' => $search_string]]
															];
			$objects	=	$this->es_search($params);
			$results	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$row		=	(object)$object['_source'];
						$results[]		=	$row->category_id;
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