<?php 
class ES_Greeting extends ESSource 
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

	public function getGreetings($obj){
		try {
			
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']['must'] = [['term' => ['published' => (int)1]],
														['bool'	=> ['should' 	=> [
																['term' => ['type' => (string)'AD']],
																['term' => ['type' => (string)'AN']],
																['term' => ['type' => (string)'B']],
																['bool' => ['must' => [	['term' => ['type' => (string)'O']],
																						['range'=> ['start_date' => ['gte' => (string)date('Y-m-d'),'lte' => (string)date('Y-m-d'),"format"=> "yyyy-MM-dd"]]]]]],
																['bool' => ['must' => [	['term' => ['type' => (string)'O']],
																						['range'=> [ 'start_date' => ['lte' => (string)date('Y-m-d'), "format"=> "yyyy-MM-dd"]]],
																						['range'=> [ 'end_date' => ['gte' => (string)date('Y-m-d'), "format"=> "yyyy-MM-dd"]]]]]]]]]];
			$objects	=	$this->es_search($params);
			$results	=	[];
			if ($objects['hits']['total']>0):
				$cards	=	[];
				$mgs	=	[];
			
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$object		=	(object)$object['_source'];
						$push 		= 	false;
						if((empty($object->geographies) || (!empty($object->geographies) && in_array($obj->employee->geography->geography_id,$object->geographies))) &&
							(empty($object->locations) || (!empty($object->locations) && in_array($obj->employee->location->location_id,$object->locations))) &&
							(empty($object->functions) || (!empty($object->functions) &&  in_array($obj->employee->function->function_id,$object->functions))) &&
							(empty($object->levels) || (!empty($object->levels) && in_array($obj->employee->level->level_id,$object->levels))) && 
							(empty($object->layers) || (!empty($object->layers) && in_array($obj->employee->layer->layer_id,$object->layers)))):
							$push = true;
						endif;	
						if($push):
							$robject 	=	new stdClass();
							switch ($object->type):
								case 'AD':	$key	=	'_advancements';
								break;
								case 'AN':	$key	=	'_anniversary';
								break;
								case 'B' :	$key	=	'_birthday';
								break;
								default	 :	$key	=	$this->common->createKey($this->common->entityDecode(substr($object->title, 0, 20)), 'alphanumeric').'_'.$object->greeting_id;
							endswitch;
							
							if (!empty($object->gallery)):
								$randKey	=	array_rand($object->gallery, 1);
								$objCards	=	(string)$object->gallery[$randKey]['picture_path'];
							endif;
							
							if (!empty($objCards)):
								$cards[$key]	=	$objCards;
								$mgs[$key]		=	(object)['salutation' => (string)ucfirst(strtolower($object->salutation)), 'message' => (string)$this->common->entityDecode($object->director_message)];
							endif;
						endif;
					endif;
				endforeach;
				$results	=	['cards'	=>	(object)$cards, 'messages'	=>	(object)$mgs];
			endif;
			return !empty($results)?(object)$results:[];
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>