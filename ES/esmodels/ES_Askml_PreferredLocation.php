<?php 
class ES_Askml_PreferredLocation  extends ESSource 
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

	
	public function getLocations(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
		
			$params['body']['query']['bool']['must']	=	[['term' =>	['enabled' => (int)1]]];
			$params['body']['sort']						= 	['ml_requestor_location' => ['order' => 'asc']];
			
			$results	=	$this->es_search($params);
			$rObjects	=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $object):
					$row								=		(object)$object['_source'];
					$robject							=		new stdClass();
					$robject->location_id				=		(int)$row->ml_requestor_location_id;
					$robject->location_title			=		(string)$this->common->entityDecode($row->ml_requestor_location);
					$robject->country_code				=		(int)$row->ml_country_code_id;
					$robject->country_short_name		=		(string)$row->ml_country_short_name;
					array_push($rObjects,$robject);
				endforeach;
			endif;
			return $rObjects;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
}
?>