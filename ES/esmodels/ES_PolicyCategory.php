<?php 
class ES_PolicyCategory  extends ESSource {
	var $index;
	var $type;
	public function __construct(){	
		parent::__construct();
		$this->index	=	'policycategories';
		$this->type		=	'policycategory';
	}

    public function __destruct()
	{	parent::__destruct();	}

	public function getPolicyCategories(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['match_all']	=	new stdClass();
			
			$results	=	$this->es_search($params);
			$robject	=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $res):
					if (!empty($res['_source'])):
						$row	=	(object)$res['_source'];
						$obj	=	new stdClass();
					
						$obj->category_id	=	(int)$row->category_id;
						$obj->title			=	(string)$this->common->entityDecode($row->title);
						array_push($robject, $obj);
					endif;
				endforeach;
			endif;
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>