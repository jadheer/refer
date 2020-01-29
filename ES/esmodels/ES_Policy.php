<?php 
class ES_Policy  extends ESSource {
	var $index;
	var $type;
	public function __construct(){	
		parent::__construct();
		$this->index	=	'policies';
		$this->type		=	'policy';
	}

    public function __destruct()
	{	parent::__destruct();	}

	public function getPolicyById($policyId){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$policyId;
	
			$result		=	$this->es_get($params);
			if (!empty($result['_source'])):
				$ES_PolicyCategory	=	new ES_PolicyCategory();
				$ObjCats	=	$ES_PolicyCategory->getPolicyCategories();
				$row		=	(object)$result['_source'];
				$robject	=	$this->setObject($row, $ObjCats);
			endif;
			return !empty($robject)?$robject:new stdClass();
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	
	public function getPolicies($object){
		try{
			$params = [];
			$params['from']  	= 	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>1]],
																['term' => ['published'=>1]]]];
	
			if (!empty($object->category_id)):
				array_push($params['body']['query']['bool']['must'], ['term' => ['category_id'=> (int)$object->category_id]]);
			endif;
			
			if (!empty($object->query)):
				$query	=	['multi_match' => ['query' => (string)trim($object->query), 'fields' => ['title^3', 'summary^2', 'description']]];
				array_push($params['body']['query']['bool']['must'], $query);
			else:
				$params['body']['sort']	= [['sorting_text' => ['order' => 'asc']], ['policy_id' => ['order' => 'asc']]];
			endif;
			return $this->setOutput($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results){
		try{
			$objects	=	[];
			if ($results['hits']['total']>0):
				$ES_PolicyCategory	=	new ES_PolicyCategory();
				$ObjCats	=	$ES_PolicyCategory->getPolicyCategories();
				foreach ($results['hits']['hits'] as $res):
					if (!empty($res['_source'])):
						$row	=	(object)$res['_source'];
						array_push($objects, $this->setObject($row, $ObjCats));
					endif;
				endforeach;
			endif;
			return $objects;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setObject($row, $objCats){
		try{
			$robject	=	new stdClass();
			$robject->policy_id 	=	(int)$row->policy_id;
			$robject->title 		=	(string)$this->common->entityDecode($row->title);
			$robject->summary		=	(string)$this->common->entityDecode($row->summary);
			$robject->description 	=	(string)$this->common->entityDecode($row->description);
			
			$categoryTitle	=	'';
			$key	=	array_search($row->category_id, array_column($objCats, 'category_id'));
			if ($key!==false):
				$categoryTitle		=	$objCats[$key]->title;
			endif;
			
			$robject->category		=	(object)[	'category_id'	=>	(int)$row->category_id,
													'title'			=>	(string)$categoryTitle];
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
    
    public function refresh(){
		try{
			$params = [];
			$params['index']  		= 	$this->index;
			return $this->es_indices_refresh($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
}
?>