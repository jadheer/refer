<?php 
class ES_Icon  extends ESSource 
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

	
	public function getIcons(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['scroll']  	= 	'30s';
			
			$params['body']['query']['bool']['must']	=	[['term' =>	['enabled' => (int)1]]];
			$params['body']['sort']						= 	['icon_id' => ['order' => 'asc']];
			
			$results	=	$this->es_search($params);
			$rObjects	=	[];
			
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						$row						=		(object)$object['_source'];
						$robject					=		new stdClass();
						$robject->icon_id			=		(int)$row->icon_id;
						$robject->title				=		(string)$row->display_name;
						array_push($rObjects,$robject);
					endforeach;
				endif;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return $rObjects;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
    
}
?>