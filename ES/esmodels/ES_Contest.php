<?php
class ES_Contest extends ESSource
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

	public function getContestsByType($object) {
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	[	'must' => [	['term' => ['enabled'=>1]],
														['term' => ['published'=>1]]]];
			if ($object->type=="past"):
			$condition	=	['range'=>	[ 'end_datetime' => [ 'lte' => (string)date('Y-m-d H:i:s') ,"format"=> "yyyy-MM-dd HH:mm:ss"]]];
				array_push($params['body']['query']['bool']['must'], $condition);
			else:
			$condition	=	['range'=>	[ 'end_datetime' => [ 'gt' => (string)date('Y-m-d H:i:s') ,"format"=> "yyyy-MM-dd HH:mm:ss"]]];
				array_push($params['body']['query']['bool']['must'], $condition);
			endif;

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


			if ($object->type=="past"):
				$params['body']['sort']	=	[['start_datetime' => ['order'	=>	'desc']], ['contest_id' => ['order' => 'asc']]];
			else:
				$params['body']['sort']	=	[['start_datetime' => ['order'	=>	'asc']], ['contest_id' => ['order' => 'asc']]];
			endif;
			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getContestByDate($object) {
		try{
			$params = [];
			$params['from']		=	(int)$object->start??0;
			$params['size']  	= 	(int)$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	[	'must' => [	['term' => ['enabled'=>1]],
																	['term' => ['published'=>1]],
																	['range'=> [ 'start_date' => ['lte' => (string)$object->date, "format"=> "yyyy-MM-dd"]]],
																	['range'=> [ 'end_date' => ['gte' => (string)$object->date, "format"=> "yyyy-MM-dd"]]]]];

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
			$params['body']['sort']	=	[['start_datetime' => ['order'	=>	'asc']], ['contest_id' => ['order' => 'asc']]];

			return $this->setOutput($this->es_search($params),$object);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getContestById($object) {
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->contest_id;

			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Level		=	new ES_Level();
				$ES_Function	=	new ES_Function();

				$ObjGeos	=	$ES_Geography->getGeographies();
				$Objlocs	=	$ES_Location->getLocations();
				$ObjLevs	=	$ES_Level->getLevels();
				$ObjFuncs	=	$ES_Function->getFunctions();

				$ES_Layer		=	new ES_Layer();
				$ObjLays		=	$ES_Layer->getLayers();
				$ObjMaster		=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers' => $ObjLays ];

				$componentMapping	=	new ComponentMapping($ObjMaster);
				$robject	=	$this->setObject((object)$result['_source'], $object, $componentMapping);
			endif;
			return !empty($robject)?$robject:new stdClass();
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getUniqueContestDatesByType($type) {
		try
		{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$condition			=	($type	==	"upcoming")?"gte":"lte";
			$params['size']  	= 	1000;
			$params['scroll']	=	'30s';
			$params['_source']	=	['start_date'];
			$dates				=	[];

			$params['body']['query']['bool']	=	[	'must' => [	['term' 	=> 	['enabled'=>(int)1]],
																	['term' 	=> 	['published'	=>	(int)1]],
																	['range' 	=> 	['end_date'	=> [ $condition => date("Y-m-d",strtotime("now")) ,"format"=> "yyyy-MM-dd"]]]]];
			$results	=	$this->es_search($params);
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				foreach ($results['hits']['hits'] as $result):
					$row	=	(object)$result['_source'];
					$date	=	date('Y-m-d',strtotime($row->start_date));
					array_push($dates,$date);
				endforeach;
				$results 	= 	$this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			function date_sort($a, $b) {
				return strtotime($a) - strtotime($b);
			}
			$dates	=	array_values(array_unique($dates));
			usort($dates, "date_sort");
			return  $dates;
		}
		catch(Exception $e)
		{
			$this->es_error($e);	}
	}

	public function apply($object){
		try {
			$params = [];
			$params = [];
			$params['index']  	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->contest_id;

			$params['body']['script']['inline']	=	'ctx._source.participants_details.add(params.participants_details)';
			$params['body']['script']['params']	=	['participants_details' => (int)$object->employee_id];
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	private function setOutput($results,$object){
		try{
			$arr 		=	[];
			if ($results['hits']['total']>0):
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Level		=	new ES_Level();
				$ES_Function	=	new ES_Function();

				$ObjGeos	=	$ES_Geography->getGeographies();
				$Objlocs	=	$ES_Location->getLocations();
				$ObjLevs	=	$ES_Level->getLevels();
				$ObjFuncs	=	$ES_Function->getFunctions();

				$ES_Layer		=	new ES_Layer();
				$ObjLays		=	$ES_Layer->getLayers();
				$ObjMaster		=	(object)['geographies'	=>	$ObjGeos, 'locations' => $Objlocs, 'levels' => $ObjLevs, 'functions' => $ObjFuncs , 'layers' => $ObjLays ];

				$componentMapping	=	new ComponentMapping($ObjMaster);
				foreach ($results['hits']['hits'] as $ijp_obj):
					if (!empty($ijp_obj['_source'])):
						$row	=	(object)$ijp_obj['_source'];
					endif;
					array_push($arr, $this->setObject($row, $object, $componentMapping));
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	private function setObject($row, $object, $componentMapping){
		try{
			$robject 				=	new stdClass();
			$robject->contest_id	=	(int)$row->contest_id;
			$robject->author		=	(string)$this->common->entityDecode($row->author);
			$robject->title			=	(string)$this->common->entityDecode($row->title);
			$robject->description	=	(string)$this->common->entityDecode($row->description);
			$robject->summary		=	(string)html_entity_decode($this->common->truncate_str(trim(strip_tags($this->common->entityDecode($row->description))),300),ENT_QUOTES);
			$robject->start_date	=	(string)$row->start_date;
			$robject->end_date		=	(string)$row->end_date;
			$robject->start_time	=	(string)$row->start_time;
			$robject->end_time		=	(string)$row->end_time;
			$robject->promo_image 	=	(object)[	'base_url'		=>	(string)_AWS_URL._CONTESTS_IMAGES_DIR,
													'image_path'	=>	(string)$row->promo_image];

			$pictures	=	[];
			if (!empty($row->gallery)):
				foreach ($row->gallery as $pic):
					$pObj	=	new stdClass();
					$pObj->picture_path		=	(string)$pic['picture_path'];
					$pObj->picture_caption	=	(string)$pic['picture_caption'];
					array_push($pictures, $pObj);
				endforeach;
			endif;
			$robject->gallary	=	(object)[	'base_url'	=>	(string)_AWS_URL._CONTESTS_IMAGES_DIR,
												'pictures'	=>	$pictures];

			$allowedParticipation		=	(boolean)(!empty($row->participants)?true:false);
			if ($allowedParticipation):
				$allowedParticipation 	=	(strtotime($robject->end_date.' '.$robject->end_time)>time())?true:false;
				if (strtotime($row->participation_last_date) < strtotime(date('Y-m-d'))):
					$allowedParticipation	=	(boolean)false;
				endif;
				if ($allowedParticipation):
					$allowedParticipation	=	($row->max_participants==count($row->participants_details))?false:true;
				endif;
			endif;

			$robject->participants		=	(object)[	'registration_required'	=>	(boolean)(!empty($row->participants)?true:false),
														'allowed_register' 	=> 	$allowedParticipation,
														'last_date'			=>	(string)$row->participation_last_date,
														'max_limit'			=>	(int)$row->max_participants,
														'total_register'	=>	(int)count($row->participants_details),
														'is_registered'		=>	(boolean)in_array($object->employee_id,$row->participants_details),
														'past_contest'		=>	(boolean)(strtotime($robject->end_date.' '.$robject->end_time)<time())?true:false];

			$robject->contest_location	=	(string)$this->common->entityDecode($row->contest_location);
			$robject->geo_locations		=	(array)$componentMapping->getGeoLocations(implode(",",$row->geographies),implode(",",$row->locations));
			$robject->functions			=	(array)$componentMapping->getFunctions(implode(",",$row->functions));
			$robject->level_layers		=	(array)$componentMapping->getLevelLayers(implode(",",$row->levels),implode(",",$row->layers));
			if (strtotime($robject->start_date)<time()):
				$robject->winners	=	$this->getWinners($row->winners);
			endif;
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	private function getWinners($object){
		try{
			$wObject	=	[];
			if (!empty($object)):
				$ids	=	array_column($object, 'employee_id');
				$ES_Employee	=	new ES_Employee();
				$objEmployees	=	$ES_Employee->getEmployeeByIds($ids);

				if (!empty($objEmployees)):
					foreach ($object as $obj):
						$Key	=	array_search($obj['employee_id'], array_column($objEmployees, 'employee_id'));
						if ($Key!==false):
							if ($objEmployees[$Key]['enabled'] == 1):
								$obj	=	(object)[	'employee_id' 		=> 	(int)$objEmployees[$Key]['employee_id'],
														'first_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['first_name']),
														'middle_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['middle_name']),
														'last_name'			=>	(string)$this->common->entityDecode($objEmployees[$Key]['last_name']),
														'display_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['display_name']),
														'profile_picture'	=>	(object)[	'base_url'	=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																							'image_path'=>	(string)$objEmployees[$Key]['profile_picture']],
														'position'			=>	(int)$obj['winner_position']];
								array_push($wObject,$obj);
							endif;
						endif;
					endforeach;
				endif;
			endif;

			if(!empty($wObject)):
				usort($wObject, function ($item1, $item2) {
					return $item1->position <=> $item2->position;
				});
			endif;
			return $wObject;
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
