<?php 
class ES_Askml_Question  extends ESSource {
	var $index;
	var $type;
	public function __construct(){	
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
	
    public function __destruct()
	{	parent::__destruct();	}

	
	public function getQuestions($object,$objMaster){
		try{
			$params = [];
			$params['from']			=		$object->start??0;
			$params['size']  		= 		$object->end??1000;
			$params['index'] 		= 		$this->index;
			$params['type']			=		$this->type;
			$service_ids			=		array_column($objMaster->services,"service_id");
			$category_ids			=		array_column($objMaster->categories,"category_id");
			$sub_category_ids		=		array_column($objMaster->subcategories,"sub_category_id");
			$classification_ids		=		array_column($objMaster->classifies,"classification_id");
			
			array_unshift($service_ids,0);
			array_unshift($category_ids,0);
			array_unshift($sub_category_ids,0);
			array_unshift($classification_ids,0);
			//questions with atleast one enabled question
			$params['body']['query']['bool']['must'] 	= 	[	['term' 	=>  ['enabled'				=>		(int)1]],
																['term' 	=> 	['published' 			=> 		(int)1]],
																['nested'	=>	[   'path' 		   		=> 		'answers',
																					'query'		    	=>  	['bool' => ['must' => [['term' => ['answers.enabled' => (int)1]]]]],
																					'inner_hits'		=>		['from'	=>	(int)0, 'size'	=>	(int)1]]]];
																
			$params['body']['query']['bool']['filter'] 	= 	[	['terms' 	=> 	['service_id'			=> 		$service_ids]] ,
																['terms' 	=> 	['category_id'			=> 		$category_ids]],
																['terms' 	=> 	['sub_category_id'		=> 		$sub_category_ids]],
																['terms' 	=> 	['classification_id'	=> 		$classification_ids]] ];

			
			
			
			$query	=	['query_string' => ['fields'		=>	['question', 'tags'],
											'query'			=> (string)trim($object->query).'*',
											'phrase_slop'	=>	1,
											'type'			=>	'best_fields']];
			
			array_push($params['body']['query']['bool']['must'], $query);			
			$results	=		$this->es_search($params);
			$arr		=		[];
			
			if ($results['hits']['total']>0):	
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						$obj	= $this->setObject($row, $objMaster);
						array_push($arr,$obj);
					endif;
				endforeach;
			endif;

			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getQuestionById($object){
		try{
			$params = [];
			$params['index'] 	= 		$this->index;
			$params['type']		=		$this->type;
			$params['body']['query']['bool']				=	[ 'must' 	=> 	[['term' => ['question_id'=>(int)$object->question_id]]]];
			$params['body']['query']['bool']['should']		=	[['nested'	=>	[   'path' 		    => 'answers',
																					'query'		    =>  ['bool' => ['must' => [['term' => ['answers.enabled' => (int)1]]]]],
																					'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	(int)100 , 'sort' =>['answers.sort_order' => ['order'	=>	'asc' ]]]]]];
			
			$result			=	$this->es_search($params);
	
			if ($result['hits']['total']>0):
				
				$row							=		(object)$result['hits']['hits'][0]['_source'];
				$inner_doc						=		$result['hits']['hits'][0]['inner_hits']['answers']??[];
				$robject						=		new stdClass();
				$objMaster						=		new stdClass();
				$robject->question_id			=		(int)$row->question_id;
				$robject->question				=		(string)$this->common->entityDecode($row->question);
				
				$es_icon						=		new ES_Icon();
				$objIcons						=		$es_icon->getIcons();
				$objMaster->service_id			=		(int)$row->service_id;
				$objMaster->category_id			=		(int)$row->category_id;
				$objMaster->sub_category_id		=		(int)$row->sub_category_id;
				$objMaster->classification_id	=		(int)$row->classification_id;
				$objMaster->icons				=		$objIcons;
				
				$robject->service				=		$robject->category	 =	$robject->subcategory  =   $robject->classification		=	new stdClass();
				
				if(!empty($row->service_id)):
					$es_service					=		new ES_Askml_Service();
					$robject->service			=		$es_service->getServiceById($objMaster);
				endif;
					
				if(!empty($row->category_id)):
					$es_category					=		new ES_Askml_Category();
					$robject->category				=		$es_category->getCategoryById($objMaster);
				endif;
					
				if(!empty($row->sub_category_id)):
					$es_sub							=		new ES_Askml_SubCategory();
					$robject->subcategory			=		$es_sub->getSubCategoryById($objMaster);
				endif;
				
				if(!empty($row->classification_id)):
					$es_classify					=		new ES_Askml_Classification();
					$robject->classification		=		$es_classify->getClassificationId($objMaster);
				endif;
				
				//answers
				$answer_arr	=	[];
				if(!empty($inner_doc['hits']['total'])):
					foreach ($inner_doc['hits']['hits'] as $answer):
						if (!empty($answer['_source'])):
							$answer						= 		(object)$answer['_source'];
							$answers					=		new stdClass();
							$answers->answer_id			=		(int)$answer->answer_id;
							$answers->description		=		(string)$this->common->entityDecode($answer->description);
							$pdfs	=	[];
							if(!empty($answer->pdf)):
								foreach ($answer->pdf as $key => $link):
									$lobj					=		new stdClass();
									$lobj->label			=		(string)$this->common->entityDecode($link['label']);
									$lobj->url				=		(string)$link['url'];	
									array_push($pdfs,$lobj);
								endforeach;
							endif;
							$answers->pdf					=		(object)[ 	'base_url' 		=>  	(string)_AWS_URL._ASKML_PDF_DIR,
																				'links' 		=>  	$pdfs];
							array_push($answer_arr,$answers);
						endif;
					endforeach;
				endif;
				$robject->answers			=		$answer_arr;
				
			endif;
			return !empty($robject)?$robject:false;
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}

	private function setObject($row,$objMaster){
		try{
			$robject 					=		new stdClass();
			$robject->question_id		=		(int)$row->question_id;
			$robject->question			=		(string)$this->common->entityDecode($row->question);
		
			$robject->service			=		new stdClass();
			$Key	=	array_search($row->service_id, array_column($objMaster->services, 'service_id'));
			if ($Key!==false):
				$robject->service			= (object)[	'service_id'	 => 	(int)$objMaster->services[$Key]->service_id,
														'ml_service_id'	 => 	(int)$objMaster->services[$Key]->ml_service_id,
														'title'          => 	(string)$objMaster->services[$Key]->title,
														'icon_name'		 => 	(string)$objMaster->services[$Key]->icon_name
				];

			endif;
			
			$robject->category			=		new stdClass();
			$Key	=	array_search($row->category_id, array_column($objMaster->categories, 'category_id'));
			if ($Key!==false):
				$robject->category			= (object)[	    'category_id'	 => 	(int)$objMaster->categories[$Key]->category_id,
															'ml_category_id'	 => 	(int)$objMaster->categories[$Key]->ml_category_id,
															'title'          => 	(string)$objMaster->categories[$Key]->title,
															'icon_name'		 =>  	(string)$objMaster->categories[$Key]->icon_name
				];
			endif;
			
			
			$robject->subcategory		=		new stdClass();
			$Key	=	array_search($row->sub_category_id, array_column($objMaster->subcategories, 'sub_category_id'));
			if ($Key!==false):
				$robject->subcategory			= (object)[	    'sub_category_id'	 	=> 	(int)$objMaster->subcategories[$Key]->sub_category_id,
																'ml_sub_category_id'	=> 	(int)$objMaster->subcategories[$Key]->ml_sub_category_id,
																'title'          	 	=> 	(string)$objMaster->subcategories[$Key]->title
				];
			endif;
			
			
			$robject->classification	=		new stdClass();
			$Key	=	array_search($row->classification_id, array_column($objMaster->classifies, 'classification_id'));
			if ($Key!==false):
				$robject->classification			= (object)[	'classification_id'	 		=> 		(int)$objMaster->classifies[$Key]->classification_id,
																'ml_classification_id'	 	=> 		(int)$objMaster->classifies[$Key]->ml_classification_id,
																'title'          			=> 		(string)$objMaster->classifies[$Key]->title
				];
			endif;

			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}	
	
}
?>