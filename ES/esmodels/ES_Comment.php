<?php
class ES_Comment extends ESSource {
	var $component;
	public function __construct()
	{
		parent::__construct();
		$this->component = ['NW' => (object)['index' => 'news', 'primary' => 'news_id', 'docType' => 'news'],
							'MT' => (object)['index' => 'microtips', 'primary' => 'microtip_id', 'docType' => 'microtip'],
							'CM' => (object)['index' => 'community_posts', 'primary' => 'post_id', 'docType' => 'community_post'],
							'A' => (object)['index' => 'articles', 'primary' => 'article_id', 'docType' => 'article'],
							'W' => (object)['index' => 'wall_posts', 'primary' => 'post_id', 'docType' => 'wall_post'],
							'CW' => (object)['index' => 'customer_wins', 'primary' => 'win_id', 'docType' => 'customer_win'],
							'CQ' => (object)['index' => 'chatquestions', 'primary' => 'question_id', 'docType' => 'chatquestion'],
							'SE' => (object)['index' => 'som_employees', 'primary' => 'star_employee_id', 'docType' => 'som_employee'],
							'BU' => (object)['index' => 'breakout_updates', 'primary' => 'update_id', 'docType' => 'breakout_update'],
							'MI' => (object)['index' => 'microgive_initiatives', 'primary' => 'initiative_id', 'docType' => 'microgive_initiative'],
							'MVU' => (object)['index' => 'microgive_updates', 'primary' => 'update_id', 'docType' => 'microgive_update'],
							'MP' => (object)['index' => 'mlthirty_posts', 'primary' => 'mlthirty_challenge_post_id', 'docType' => 'mlthirty_post'],
							'CA' => (object)['index' => 'cheer_employee_awards', 'primary' => 'cheer_employee_award_id', 'docType' => 'cheer_employee_award'],
							'CTN' => (object)['index' => 'cheer_top_nominators', 'primary' => 'nominator_id', 'docType' => 'cheer_top_nominator'],
							'CTR' => (object)['index' => 'cheer_top_recipients', 'primary' => 'recipient_id', 'docType' => 'cheer_top_recipient']
		];
	}

    public function __destruct()
	{	parent::__destruct();	}

	public function addComment($object){
		try{
			$params = [];
			$params = [];
			$params['index']  	= 	$this->component[$object->type]->index;
			$params['type']  	= 	$this->component[$object->type]->docType;
			$params['id']    	= 	(int)$object->refer_id;

			$fobj					=	new stdClass();
			$fobj->geographies		=	(string)(!empty($object->geography_id))?$object->geography_id:'';
			$fobj->functions		=	(string)(!empty($object->function_id))?$object->function_id:'';
			$fobj->locations		=	(string)(!empty($object->location_id))?$object->location_id:'';
			$fobj->levels			=	(string)(!empty($object->level_id))?$object->level_id:'';
			$fobj->layers			=	(string)(!empty($object->layer_id))?$object->layer_id:'';

			$doc 	= 	(object)[	'comment_id'		=>	(int)$object->comment_id,
									'employee_id'		=>	(int)$object->employee_id,
									'comment'			=>	(string)$this->common->entityEncode($object->comment),
									'comment_on'		=>	(string)$object->comment_on,
									'enabled'			=>	(int)$object->enabled,
									'filters'			=>  $fobj,
									'tagged_employees'	=>	!empty($object->es_tags)?$object->es_tags:[] ];

			$params['body']['script']['inline']	=	'ctx._source.comments.add(params.comments)';
			$params['body']['script']['params']	=	['comments' => $doc];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getComments($object){
		try{
			$primary	=	(string)$this->component[$object->type]->primary;
			$params 	= 	[];
			$params['index']  	= 	$this->component[$object->type]->index;
			$params['_source']	=	false;
			$condition			=	'enabled';
			$condition			=	($this->component[$object->type]->index =='wall_posts')?'published':($this->component[$object->type]->index == 'cheer_employee_awards'?"approved":$condition);

			$params['body']['query']['bool']['must']= 	[	[['term' 	=> ["$primary" => (int)$object->refer_id]],['term' => [ "$condition" => (int)1]]],
															['nested'	=>	['path' => 'comments',
																			'query'	=> ['bool' => ['must' => [['term' => ['comments.enabled' => (int)1]]]]],
															'inner_hits'	=>	['from'	=>	(int)$object->start, 'size'	=>	(int)$object->end]]]];
			$params['body']['sort']	=	[['comments.comment_on' => ['order' => 'desc']]];

			$results	=	$this->es_search($params);

			$robjects	=	[];
			$empIds		=	[];
			$tag_arr	=	[];
			if (isset($results['hits']['hits'][0]['inner_hits']['comments']['hits']['hits'])):
				if ($results['hits']['hits'][0]['inner_hits']['comments']['hits']['total']>0):
					$docs	=	$results['hits']['hits'][0]['inner_hits']['comments']['hits']['hits'];
					foreach ($docs as $obj):
						$obj	=	(object)$obj['_source'];
						$object		=	new stdClass();
						$object->comment_id 	=	(int)$obj->comment_id;
						$object->comment 		=	(string)html_entity_decode($this->common->entityDecode($obj->comment),ENT_QUOTES);
						$object->comment_on 	=	(string)$obj->comment_on;
						$object->employee		=	(object)['employee_id' 	=> 	(int)$obj->employee_id];
						$fobj					=	new stdClass();
						$fobj->geography_id		=	(string)(!empty($obj->filters['geographies'])?$obj->filters['geographies']:'');
						$fobj->location_id		=	(string)(!empty($obj->filters['locations'])?$obj->filters['locations']:'');
						$fobj->function_id		=	(string)(!empty($obj->filters['functions'])?$obj->filters['functions']:'');
						$fobj->level_id			=	(string)(!empty($obj->filters['levels'])?$obj->filters['levels']:'');
						$fobj->layer_id			=	(string)(!empty($obj->filters['layers'])?$obj->filters['layers']:'');
						$object->filters		=	(object)$fobj;
						$object->tags_count		=	(int)!empty($obj->tagged_employees)?count($obj->tagged_employees):0;
						array_push($empIds, (string)$obj->employee_id);
						array_push($robjects, $object);
					endforeach;
				endif;
			endif;

			$comments	=	[];
			$empIds		=	array_unique($empIds);
			if (!empty($empIds)):
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Level		=	new ES_Level();
				$ES_Function	=	new ES_Function();
				$ES_Layer		=	new ES_Layer();

				$ObjGeos		=	$ES_Geography->getGeographies();
				$Objlocs		=	$ES_Location->getLocations();
				$ObjLevs		=	$ES_Level->getLevels();
				$ObjFuncs		=	$ES_Function->getFunctions();
				$objLayers		=	$ES_Layer->getLayers();

				$ObjMaster	=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers'  =>  $objLayers];

				$ES_Employee	=	new ES_Employee();
				$objEmployees	=	$ES_Employee->getEmployeeByIds($empIds);

				if (!empty($objEmployees)):
					foreach ($robjects as $robject):
						$Key	=	array_search($robject->employee->employee_id, array_column($objEmployees, 'employee_id'));
						if ($Key!==false):
							if ($objEmployees[$Key]['enabled'] == 1):
								$robject->employee		=	(object)[	'employee_id' 		=> 	(int)$objEmployees[$Key]['employee_id'],
																		'first_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['first_name']),
																		'middle_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['middle_name']),
																		'last_name'			=>	(string)$this->common->entityDecode($objEmployees[$Key]['last_name']),
																		'display_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['display_name']),
																		'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																											'image_path'	=>	(string)$objEmployees[$Key]['profile_picture']]];
								array_push($comments,$robject);
							endif;
						endif;
					endforeach;
				endif;
			endif;
			return $comments;
		}
		catch(Exception $e){
		$this->es_error($e);	}
	}


	public function updateTags($object) {
		try{
			$params['index']  	= 	$this->component[$object->type]->index;
			$params['type']  	= 	$this->component[$object->type]->docType;
			$primary			=	(string)$this->component[$object->type]->primary;
			$params['body']['query']['bool']		=	[ 'must' => ['term' => [ $primary =>(int)$object->refer_id ]]];
			$params['body']['script']['inline']		=	'for (int i = 0; i < ctx._source.comments.size(); i++){if(ctx._source.comments[i].comment_id == params.comment_id){ctx._source.comments[i].tagged_employees = params.tagged_employees;ctx._source.comments[i].filters = params.filters; }}';
			$params['body']['script']['params']		=	['comment_id' => (int)$object->comment_id, 'tagged_employees' => $object->tagged_employees,'filters' =>  $object->filters ];

			return $this->es_updateByQuery($params);

		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}


	public function removeTaggedEmployee($object) {
		try{
			$params['index']  	= 	$this->component[$object->type]->index;
			$params['type']  	= 	$this->component[$object->type]->docType;
			$primary			=	(string)$this->component[$object->type]->primary;
			$params['body']['query']['bool']		=	[ 'must' => ['term' => [ $primary =>(int)$object->refer_id ]]];
			$params['body']['script']['inline']		=	'for (int i = 0; i < ctx._source.comments.size(); i++){if(ctx._source.comments[i].comment_id == params.comment_id){ctx._source.comments[i].tagged_employees.removeAll(Collections.singleton(params.tags)) }}';
			$params['body']['script']['params']		=	['comment_id' => (int)$object->comment_id, 'tags' => $object->tag];

			return $this->es_updateByQuery($params);

		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function refresh($Index){
		try{
			$params = [];
			$params['index']  	= 	$this->component[$Index]->index;
			return $this->es_indices_refresh($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getTaggedEmployees($object) {
		try{
			$params['index']  	= 	$this->component[$object->type]->index;
			$params['type']  	= 	$this->component[$object->type]->docType;
			$primary			=	(string)$this->component[$object->type]->primary;

			$params['body']['query']['bool']['must']= 	[	[['term' 	=> ["$primary" => (int)$object->refer_id]]],
																			[	'nested'	=>	['path' => 'comments',
																				'query'	=> ['bool' => ['must' => [['term' => ['comments.comment_id' => (int)$object->comment_id ]]]]],
																				'inner_hits'	=>	['from'	=>	0, 'size'	=>	1]]]];


			$results	=	$this->es_search($params);
			$empIds		=	[];
			$arr		=	[];
			if (isset($results['hits']['hits'][0]['inner_hits']['comments']['hits']['hits'])):
				if ($results['hits']['hits'][0]['inner_hits']['comments']['hits']['total']>0):
					$empIds	=	$results['hits']['hits'][0]['inner_hits']['comments']['hits']['hits'][0]['_source']['tagged_employees'];

					$objEmployees	=	[];
					if (!empty($empIds)):
						$ES_Employee	=	new ES_Employee();
						$objEmployees	=	array_column($ES_Employee->getEmployeeByIds($empIds),NULL,"employee_id");
					endif;
					$ObjMaster	=	$this->setMasterObject();

					foreach ($empIds as $key => $tag):
						if(isset($objEmployees[$tag]) && $objEmployees[$tag]['enabled']==1):
							$emp					=	new stdClass();
							$emp->employee_id 		= 	(int)$objEmployees[$tag]['employee_id'];
							$first_name				=	$objEmployees[$tag]['first_name']?$this->common->entityDecode($objEmployees[$tag]['first_name'])." ":"";
							$middle_name			=	$objEmployees[$tag]['middle_name']?$this->common->entityDecode($objEmployees[$tag]['middle_name'])." ":"";
							$last_name				=	$objEmployees[$tag]['last_name']?$this->common->entityDecode($objEmployees[$tag]['last_name']):"";
							$emp->first_name		=	$objEmployees[$tag]['first_name']?$this->common->entityDecode($objEmployees[$tag]['first_name']):"";
							$emp->middle_name		=	$objEmployees[$tag]['middle_name']?$this->common->entityDecode($objEmployees[$tag]['middle_name']):"";
							$emp->last_name			=	$objEmployees[$tag]['last_name']?$this->common->entityDecode($objEmployees[$tag]['last_name']):"";
							$emp->employee_name		=	(string)$first_name.$middle_name.$last_name;
							$emp->employee_code		=	(string)$objEmployees[$tag]['employee_code'];
							$emp->profile_picture	=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																	'image_path'	=>	(string)$objEmployees[$tag]['profile_picture']];

							$emp->geography	=	new stdClass();
							$Key	=	array_search($objEmployees[$tag]['geography_id'], array_column($ObjMaster->geographies, 'geography_id'));
							if ($Key!==false):
							$emp->geography	= (object)['geography_id' => (int)$ObjMaster->geographies[$Key]->geography_id,
														'title' => (string)$ObjMaster->geographies[$Key]->title];
							endif;

							$emp->location	=	new stdClass();
							$Key	=	array_search($objEmployees[$tag]['location_id'], array_column($ObjMaster->locations, 'location_id'));
							if ($Key!==false):
							$emp->location	= (object)[	'location_id' => (int)$ObjMaster->locations[$Key]->location_id,
														'title' => (string)$ObjMaster->locations[$Key]->title];
							endif;

							$emp->function	=	new stdClass();
							$Key	=	array_search($objEmployees[$tag]['function_id'], array_column($ObjMaster->functions, 'function_id'));
							if ($Key!==false):
							$emp->function	= (object)[	'function_id' => (int)$ObjMaster->functions[$Key]->function_id,
														'title' => (string)$ObjMaster->functions[$Key]->title];
							endif;

							$emp->level		=	new stdClass();
							$Key	=	array_search($objEmployees[$tag]['level_id'], array_column($ObjMaster->levels, 'level_id'));
							if ($Key!==false):
							$emp->level		= (object)[	'level_id' => (int)$ObjMaster->levels[$Key]->level_id,
														'title' => (string)$ObjMaster->levels[$Key]->title];
							endif;


							$emp->layer		=	new stdClass();
							$Key			=	array_search($objEmployees[$tag]['layer_id'], array_column($ObjMaster->layers, 'layer_id'));
							if ($Key!==false):
							$emp->layer	= 	(object)[	'layer_id' => (int)$ObjMaster->layers[$Key]->layer_id,
														'title' => (string)$ObjMaster->layers[$Key]->title];
							endif;

							array_push($arr, $emp);
						endif;
					endforeach;
				endif;
			endif;
			$sort_arr 		= 	array_column($arr,NULL,'employee_name');
			ksort($sort_arr);
			return array_values($sort_arr);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	private function setMasterObject(){
		try{

			$ES_Geography	=	new ES_Geography();
			$ES_Location	=	new ES_Location();
			$ES_Level		=	new ES_Level();
			$ES_Function	=	new ES_Function();
			$ES_Layer		=	new ES_Layer();

			$ObjGeos		=	$ES_Geography->getGeographies();
			$Objlocs		=	$ES_Location->getLocations();
			$ObjLevs		=	$ES_Level->getLevels();
			$ObjFuncs		=	$ES_Function->getFunctions();
			$objLayers		=	$ES_Layer->getLayers();

			$ObjMaster		=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers'  =>  $objLayers];

			return $ObjMaster;
		}catch(Exception $e)
		{	$this->es_error($e);	}
	}

}
?>
