<?php

class ES_Cheer_Top_Nominator extends ESSource {

	public function __construct()
	{	parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}

	public function __destruct()
	{	parent::__destruct();	}

	public function getNominatorsByIds($ids){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['size'] 	= 	1000;
			$params['scroll']	=	'30s';
			
			$params['body']['query']['ids']				=  ['values' => (array)array_values($ids)];
			$results	=	$this->es_search($params);
			$rObject	=	[];
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						array_push($rObject, $object['_source']);
					endforeach;
				endif;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return $rObject;
		}
		catch(Exception $e)
		{ $this->es_error($e);   }
	}
	
}
?>
