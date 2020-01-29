<?php 
class ES_Askml_Classification  extends ESSource 
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
    

	public function getClassifies($sub_category_id = 0){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['scroll']  	= 	'30s';
			
			$params['body']['query']['bool']['must']	=	[['term' =>	['enabled' => (int)1]]];
			if (!empty($sub_category_id)):
				array_push($params['body']['query']['bool']['must'], ['term' => ['sub_category_id' => $sub_category_id]]);
			endif;
			$params['body']['sort']						= 	['display_text' => ['order' => 'asc']];
			
			$results	=	$this->es_search($params);
			$rObjects	=	[];
			
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						$row								=		(object)$object['_source'];
						$robject							=		new stdClass();
						$robject->classification_id			=		(int)$row->classification_id;
						$robject->sub_category_id			=		(int)$row->sub_category_id;
						$robject->title						=		(string)$this->common->entityDecode($row->display_text);
						$robject->ml_classification_id		=		(int)$row->ml_classification_id;
						$robject->ml_title					=		(string)$this->common->entityDecode($row->ml_classification);
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
	
	
	public function getClassificationId($object){
		try{
			$params = [];
			$params['index'] 	= 		$this->index;
			$params['type']		=		$this->type;
			$params['id']		=		(int)$object->classification_id;
			$result				=		$this->es_get($params);
			
			if (!empty($result['_source'])):
				$row								=		(object)$result['_source'];
				$robject 							=		new stdClass();
				$robject->classification_id			=		(int)$row->classification_id;
				$robject->ml_classification_id		=		(int)$row->ml_classification_id;
				$robject->title						=		(string)$this->common->entityDecode($row->display_text);
			endif;
			return !empty($robject)?$robject:new stdClass();
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	
}
?>