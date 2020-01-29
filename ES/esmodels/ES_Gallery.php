<?php 
class ES_Gallery extends ESSource {
	var $component;
	var $indexes;
	public function __construct()
	{	
		parent::__construct();	
		$this->indexes = ['causes', 'facilities', 'communities', 'contests', 'events', 'learnings', 'customer_visits'];
		$this->component = ['events' 		=> (object)['index' => 'events', 'primary' => 'event_id', 'docType' => 'event' , 'base_url'=>_AWS_URL._EVENTS_IMAGES_DIR],
							'learning' 		=> (object)['index' => 'learnings', 'primary' => 'learning_id', 'docType' => 'learning', 'base_url'=>_AWS_URL._LEARNINGS_IMAGES_DIR],
							'contests'		=> (object)['index' => 'contests', 'primary' => 'contest_id', 'docType' => 'contest','base_url'=>_AWS_URL._CONTESTS_IMAGES_DIR],
							'communities' 	=> (object)['index' => 'communities', 'primary' => 'community_id', 'docType' => 'community' , 'base_url'=>_AWS_URL._COMMUNITIES_IMAGES_DIR],
							'microgive'		=> (object)['index' => 'causes', 'primary' => 'cause_id', 'docType' => 'cause' , 'base_url'=>_AWS_URL._CAUSES_IMAGES_DIR],
							'facilities' 	=> (object)['index' => 'facilities', 'primary' => 'facility_id', 'docType' => 'facility' , 'base_url'=>_AWS_URL._FACILITIES_IMAGES_DIR],
							'clientvisits'	=> (object)['index' => 'customer_visits', 'primary' => 'client_visit_id', 'docType' => 'customer_visit','base_url'=>_AWS_URL._CLIENTVISITS_IMAGES_DIR],
							'stars'			=> (object)['index' => 'star_microlands', 'primary' => 'som_id', 'docType' => 'star_microland','base_url'=>_AWS_URL._SOM_IMAGES_DIR]
		];
	}

    public function __destruct()
	{	parent::__destruct();	}

	public function getEventsGallery($object){
		try{
				$params = [];
				$params['index']  		= 	$this->component[$object->type]->index;
				$params['type']  		= 	$this->component[$object->type]->docType;
				$object->primary_id    	= 	$this->component[$object->type]->primary;
				$params['from']  		= 	$object->start??0;
				$params['size']  		= 	$object->end??1000;
				
				$params['_source']		=	[$object->primary_id ,'gallery','title', 'description','start_date','end_date','pub_datetime','promo_image','geographies','locations','functions','levels','layers'];
				
				$params['body']['query']['bool']	=	[	'must' => [	['term' => ['enabled'=>1]],
																		['term' => ['published'=>1]],
																		['range'=> [ 'end_datetime' => [ 'lte' => (string)date('Y-m-d H:i:s') ,"format"=> "yyyy-MM-dd HH:mm:ss"]]],
																		['nested' => [ 'path' =>'gallery',
																				'query'=>['exists'=>['field'=>'gallery.picture_path']]
																		]]]];
				
				if (!empty($object->geography_id)):
					array_push($params['body']['query']['bool']['must'], ['terms'	=>	['geographies'	=>	array_map('intval', explode(',', $object->geography_id))]]);
				endif;
				
				if (!empty($object->location_id)):
					array_push($params['body']['query']['bool']['must'], ['terms'	=>	['locations'	=>	array_map('intval', explode(',', $object->location_id))]]);
				endif;
				
				if (!empty($object->function_id)):
					array_push($params['body']['query']['bool']['must'], ['terms'	=>	['functions'	=>	array_map('intval', explode(',', $object->function_id))]]);
				endif;
				
				if (!empty($object->level_id)):
					array_push($params['body']['query']['bool']['must'], ['terms'	=>	['levels'	=>	array_map('intval', explode(',', $object->level_id))]]);
				endif;
				
				if (!empty($object->layer_id)):
					array_push($params['body']['query']['bool']['must'], ['terms'	=>	['layers'	=>	array_map('intval', explode(',', $object->layer_id))]]);
				endif;
				
				if (!empty($object->date)):
					$date_condition		=	[['range'=>	[ 'start_date' => ['lte' => (string)$object->date, "format"=> "yyyy-MM-dd"]]],
									 		['range'=>	[ 'end_date' 	=> ['gte' => (string)$object->date, "format"=> "yyyy-MM-dd"]]]];
					array_push($params['body']['query']['bool']['must'], $date_condition);
				endif;
			
				$params['body']['sort']		=	[['start_datetime' => ['order'	=>	'desc']], [$object->primary_id => ['order' => 'asc']]];
				
				$results					=	$this->es_search($params);
				$arr 		=	[];
				if ($results['hits']['total']>0):
					$ES_Geography	=	new ES_Geography();
					$ES_Location	=	new ES_Location();
					$ES_Level		=	new ES_Level();
					$ES_Function	=	new ES_Function();
					$ES_Layer		=	new ES_Layer();
					
					$ObjGeos	=	$ES_Geography->getGeographies();
					$Objlocs	=	$ES_Location->getLocations();
					$ObjLevs	=	$ES_Level->getLevels();
					$ObjFuncs	=	$ES_Function->getFunctions();
					$ObjLays	=	$ES_Layer->getLayers();
					
					$ObjMaster	=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers' => $ObjLays ];
					
					$componentMapping	=	new ComponentMapping($ObjMaster);
					foreach ($results['hits']['hits'] as $obj):
						if (!empty($obj['_source'])):
							$row	=	(object)$obj['_source'];
							array_push($arr, $this->setObject($row, $object, $componentMapping));
						endif;
					endforeach;
				endif;
				return $arr;
			}
			catch(Exception $e)
			{	$this->es_error($e);	}
	}
	
	private function setObject($row, $object, $componentMapping=null){
		try{
			$robject 				=	new stdClass();
			$id						=	$object->primary_id;
			$robject->id			=	(int)$row->$id;
			$robject->title			=	(string)$this->common->entityDecode($row->title);
			$robject->summary		=	(string)$this->common->entityDecode($row->description);
			$robject->pub_datetime	=	(string)$row->pub_datetime;
			$robject->base_url		=	(string)$this->component[$object->type]->base_url;
			$robject->promo_image	=	(string)$row->promo_image;
			
			if(!empty($row->start_date)):
				$robject->start_date	=	(string)$row->start_date;
			endif;
			if(!empty($row->end_date)):
				$robject->end_date		=	(string)$row->end_date;
			endif;
			
			$pictures	=	[];
			if (!empty($row->gallery)):
				foreach ($row->gallery as $pic):
					$pObj	=	new stdClass();
					$pObj->image_path		=	(string)$pic['picture_path'];
					$pObj->caption			=	(string)$this->common->entityDecode($pic['picture_caption']);
					array_push($pictures, $pObj);
				endforeach;
			endif;
			$robject->gallery				=	$pictures;
			
			if(!empty($componentMapping)):
				$robject->geo_locations			=	(array)$componentMapping->getGeoLocations(implode(",",$row->geographies),implode(",",$row->locations));
				$robject->functions				=	(array)$componentMapping->getFunctions(implode(",",$row->functions));
				$robject->level_layers			=	(array)$componentMapping->getLevelLayers(implode(",",$row->levels),implode(",",$row->layers));
			endif;	
			
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getGalleriesBySearch($object){
		try{
			$params = [];
			$params['from']  	= 	$object->start;
			$params['size']  	= 	1000; //$object->end;
			$params['index']  	= 	$this->indexes;
				
			$params['body']['query']['bool']['must']	=	[];
			if (!empty($object->query)):
// 				$params['body']['query']['bool']['must']	=	[];
				$this->search_token		=	trim($object->query);
				$this->setFullTextToken();
				array_push($params['body']['query']['bool']['must'], ['query_string'=> ['fields' => ['causes.author', 'causes.title', 'causes.summary', 'causes.body'],'query' => $this->search_token]]);
			endif;
		}
			catch(Exception $e)
			{	$this->es_error($e);	}
	}
	
	public function getMicrogiveGallery($object){
		try{
			$params = [];
			$params['index']  		= 	$this->component[$object->type]->index;
			$params['type']  		= 	$this->component[$object->type]->docType;
			$object->primary_id    	= 	$this->component[$object->type]->primary;
			
			$params['_source']		=	[$object->primary_id ,'gallery','title', 'summary','start_date','end_date','pub_datetime','promo_image'];
			$params['from']  		= 	$object->start??0;
			$params['size']  		= 	$object->end??1000;
			
			$params['body']['query']['bool'] 	= 	['must' => [['term' => ['enabled'=>1]],
																['term' => ['published'=>1]],
																['range'=> [ 'pub_datetime' => [ 'lte' => (string)date('Y-m-d H:i:s') ,"format"=> "yyyy-MM-dd HH:mm:ss"]]],
																['nested' => [ 'path' =>'gallery',
																				'query'=>['exists'=>['field'=>'gallery.picture_path']]
																]]]];
																				
			
			if (!empty($object->date)):
				$date_condition		=	[['range'=>	[ 'start_date' => ['lte' => (string)$object->date, "format"=> "yyyy-MM-dd"]]],
										 ['range'=>	[ 'end_date' 	=> ['gte' => (string)$object->date, "format"=> "yyyy-MM-dd"]]]];
				array_push($params['body']['query']['bool']['must'], $date_condition);
			endif;
			
			
			$params['body']['sort']				= 	[['pub_datetime' => ['order' => 'desc']], ['cause_id' => ['order' => 'desc']]];
			
			$results				=	$this->es_search($params);
			$arr	=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						$row->description	=	$row->summary;
						array_push($arr, $this->setObject($row, $object));
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getClientvisitsGallery($object){
		try{
			$params = [];
			$params['index']  		= 	$this->component[$object->type]->index;
			$params['type']  		= 	$this->component[$object->type]->docType;
			$object->primary_id    	= 	$this->component[$object->type]->primary;
			
			$params['_source']		=	[$object->primary_id ,'gallery','title', 'description','date','pub_datetime','promo_image'];
			$params['from']  		= 	$object->start??0;
			$params['size']  		= 	$object->end??1000;
		
			$params['body']['query']['bool'] 	= 	['must' => [['term' => ['published'=>1]],
																['range'=> [ 'pub_datetime' => [ 'lte' => (string)date('Y-m-d H:i:s') ,"format"=> "yyyy-MM-dd HH:mm:ss"]]],
																['nested' => [ 'path' =>'gallery',
																				'query'=>['exists'=>['field'=>'gallery.picture_path']]
																]]]];
											
			if (!empty($object->date)):
				$date_condition		=	['term' =>[ 'date' => $object->date]];
				array_push($params['body']['query']['bool']['must'], $date_condition);
			endif;
			
			
			$params['body']['sort']				=	[['pub_datetime' =>	['order'	=>	'desc']]];
			$results				=	$this->es_search($params);
			$arr	=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						$row->start_date	=	$row->date;
						array_push($arr, $this->setObject($row, $object));
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getFacilitiesGallery($object){
		try{
			$params = [];
			$params['index']  		= 	$this->component[$object->type]->index;
			$params['type']  		= 	$this->component[$object->type]->docType;
			$object->primary_id    	= 	$this->component[$object->type]->primary;
			$params['_source']		=	[$object->primary_id ,'gallery','title','sorting_text', 'address','pub_datetime','promo_image','geography_id','location_id'];
			$params['from']  		= 	$object->start??0;
			$params['size']  		= 	$object->end??1000;
			
			$params['body']['query']['bool'] 	= 	['must' => [['term' => ['enabled'=>1]],
																['term' => ['published'=>1]],
																['range'=> ['pub_datetime' => [ 'lte' => (string)date('Y-m-d H:i:s') ,"format"=> "yyyy-MM-dd HH:mm:ss"]]],
																['nested' => [ 'path' =>'gallery',
																			   'query'=>['exists'=>['field'=>'gallery.picture_path']]]]]];
			
			if (!empty($object->geography_id)):
				array_push($params['body']['query']['bool']['must'], ['bool' => ['should' => ['terms'=>	['geography_id'	=>	array_map('intval', explode(',', $object->geography_id))]]]]);
			endif;
			
			if (!empty($object->location_id)):
				array_push($params['body']['query']['bool']['must'], ['bool' => ['should' => ['terms' =>	['location_id'	=>	array_map('intval', explode(',', $object->location_id))]]]]);
			endif;
			
			$params['body']['sort']		=	[['sorting_text' =>	['order'	=>	'asc']],['facility_id' =>	['order'	=>	'asc']]];

			$results				=	$this->es_search($params);
			$arr	=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];		
						$row->description	=	$row->address;
						$robject			=	$this->setObject($row, $object);
						
						/*get geography details*/
						$ES_Geography		=	new ES_Geography();
						$ObjGeos			=	$ES_Geography->getById($row->geography_id);
						$robject->geography	=	new stdClass();
						if (!empty($ObjGeos)):
							$robject->geography	= (object)['geography_id' => (int)$ObjGeos->geography_id, 'title' => (string)$ObjGeos->value];
						endif;
						
						/*get location details*/
						$ES_Location		=	new ES_Location();
						$Objlocs			=	$ES_Location->getById($row->location_id);
						$robject->location	=	new stdClass();
						if (!empty($Objlocs)):
							$robject->location	= (object)['location_id' => (int)$Objlocs->location_id, 'title' => (string)$Objlocs->value];
						endif;
						array_push($arr, $robject);
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}
	
	public function getCommunitiesGallery($object){
		try{
			$params = [];
			$params['index']  		= 	$this->component[$object->type]->index;
			$params['type']  		= 	$this->component[$object->type]->docType;
			$object->primary_id    	= 	$this->component[$object->type]->primary;
			$params['from']  		= 	$object->start??0;
			$params['size']  		= 	$object->end??1000;
			
			$es_community			=	new ES_Community();
			$category_arr			=	[];
			$objCategories			=	$es_community->getCategories();
			
			if(!empty($objCategories)):
				$category_arr		=	array_combine($objCategories->ids,array_column($objCategories->categories,"title"));
			endif;
			
			$params['_source']		=	[$object->primary_id ,'gallery','category_id','title','description','sorting_text','pub_datetime','promo_image'];
			
			$params['body']['query']['bool']['must']		=	[['term' =>	['enabled' => (int)1]],
																 ['term' =>	['published' => (int)1]],
																 ['range'=> ['pub_datetime' => [ 'lte' => (string)date('Y-m-d H:i:s') ,"format"=> "yyyy-MM-dd HH:mm:ss"]]],
																 ['nested' => [ 'path' =>'gallery',
																				'query'=>['exists'=>['field'=>'gallery.picture_path']]
																 ]]
																];
			
			$params['body']['query']['bool']['filter'] 		= 	['terms' 	=> 	['category_id'	=> $objCategories->ids ]];
			
			$params['body']['sort']							= 	[['sorting_text' => ['order' 	=> 	'asc']]];
			
			$results				=	$this->es_search($params);
			$arr	=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						$robject					=	$this->setObject($row, $object);
						$robject->category_title	=	$category_arr[$row->category_id];
						array_push($arr, $robject);
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	
	public function getStarsGallery($object){
		try{
			$params = [];
			$params['index']  		= 	$this->component[$object->type]->index;
			$params['type']  		= 	$this->component[$object->type]->docType;
			$object->primary_id    	= 	$this->component[$object->type]->primary;
			
			$params['_source']		=	[$object->primary_id ,'gallery','title', 'description','start_date','end_date','created_on','promo_image'];
			$params['from']  		= 	$object->start??0;
			$params['size']  		= 	$object->end??1000;
			
			$params['body']['query']['bool'] 	= 	['must' => [['term' => ['enabled'=>1]],
																['nested' => [ 'path' =>'gallery',
																			 'query'=>['exists'=>['field'=>'gallery.picture_path']]
																	]]
																]];
		
			$params['body']['sort']				=	[['start_date' =>	['order'	=>	'desc']]];
			$results					=	$this->es_search($params);
			$arr	=	[];
			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						$row->pub_datetime	=	$row->created_on;
						array_push($arr, $this->setObject($row, $object));
					endif;
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
}
?>