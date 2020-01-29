<?php
class ES_Geography extends ESSource
{
	var $index;
	var $type;
	public function __construct()
	{
		parent::__construct();
		$this->index	=	'geographies';
		$this->type		=	'geography';
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

	public function getGeographies(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	=	[['term'	=>	['enabled' => (int)1]]];
			$params['body']['sort']	=	[	['geo_order'=>	['order'	=>	'asc']],
											['value'	=>	['order'	=>	'asc']]];

			$objects	=	$this->es_search($params);
			$results	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$object					=	(object)$object['_source'];
						$robject 				=	new stdClass();
						$robject->geography_id	=	(int)$object->geography_id;
						$robject->title 		=	(string)$object->value;
						$robject->abbreviation 	=	(string)$object->abbreviation;
						$robject->isd_code 		=	(string)$object->isd_code;
						$robject->currency_id	=	(int)$object->currency_id;

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
