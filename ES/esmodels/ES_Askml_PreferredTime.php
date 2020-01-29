<?php 
class ES_Askml_PreferredTime  extends ESSource 
{
	var $index;
	var $type;
	public function __construct()
	{	
		parent::__construct();
		$this->index	=	"askml_timerange";
		$this->type		=	"askml_time";
	}

    public function __destruct()
	{	parent::__destruct();	}

	public function getTimeRanges(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']['must']	=	[['term' =>	['enabled' => (int)1]] , ['term' => ['ml_ticket_type_id' => (int)2]]];
			$params['body']['sort']						= 	['perferred_id' => ['order' => 'asc']];
			
			$results	=	$this->es_search($params);
			$rObjects	=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $object):
					$row								=		(object)$object['_source'];
					$robject							=		new stdClass();
					$robject->time_id					=		(int)$row->ml_id;
					$robject->time_title				=		(string)$row->ml_time_range;
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