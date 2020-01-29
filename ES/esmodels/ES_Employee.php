<?php
class ES_Employee extends ESSource
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

	public function updateEmployee($object){
		try{
			$params = [];
			$params['body']	= [	'doc' 			=>	[	'display_name'					=>	(string)$this->common->entityEncode($object->display_name),
														'date_of_birth'					=>	(string)empty($object->date_of_birth->date)?'':$object->date_of_birth->date,
														'is_birth_day_visible'			=>	(int)$object->date_of_birth->visibility,
														'gender'						=>	(string)$object->gender,
														'blood_group'					=>	(string)$object->blood_group->group,
														'is_blood_group_visible'		=>	(int)$object->blood_group->visibility,
														'description			'		=>	(string)$this->common->entityEncode($object->description),
														'linkedin_url'					=>	(string)$object->social->linkedin_url,
														'twitter_url'					=>	(string)$object->social->twitter_url,
														'website_url'					=>	(string)$object->social->website_url,
														'open_id'						=>	(string)$object->social->open_id,
														'mobile_isd'					=>	(string)$object->mobile_number->isd_code,
														'mobile_number'					=>	(string)$object->mobile_number->number,
														'is_mobile_verified'			=>	(int)$object->mobile_number->is_mobile_verified,
														'is_mobile_number_visible'		=>	(int)$object->mobile_number->is_mobile_number_visible,
														'workphone_isd'					=>	(string)$object->work_phone->isd_code,
														'work_phone'					=>	(string)$object->work_phone->number,
														'work_phone_extn'				=>	(string)$object->work_phone->extension,
														'address'						=>	(string)$object->address,
														'emergency_contact_name'		=>	(string)$this->common->entityEncode($object->emergency_contact->name),
														'emergency_contact_phone'		=>	(string)$object->emergency_contact->phone->number,
														'emergency_contact_phone_isd'	=>	(string)$object->emergency_contact->phone->isd_code,
														'emergency_contact_email'		=>	(string)$object->emergency_contact->email,
														'preferences'					=>	[],
														'updated_on'					=>	(string)$object->updated_on]];

			$preferences	=	[];
			if (!empty($object->preferences)):
				foreach ($object->preferences as $pref):
					if ($pref->on==false):
						array_push($preferences, (int)$pref->preference_id);
					endif;
				endforeach;
			endif;
			$params['body']['doc']['preferences']	=	$preferences;

			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	(int)$object->employee_id;

			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getEmpByEmail($email){
		try
		{
			$params = [];
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	= 	[['term' => ['email' => (string)$email]],['term' => ['enabled' => (int)1]]];

			$objects	=	$this->es_search($params);
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$robject 	=	new stdClass();
						$robject->employee_id 			=	(int)$object['_source']['employee_id'];
						$robject->employee_code 		=	(int)$object['_source']['employee_code'];
						$robject->employee_type 		=	(string)$object['_source']['employee_type'];
						$robject->first_name 			=	(string)$this->common->entityDecode($object['_source']['first_name']);
						$robject->middle_name 			=	(string)$this->common->entityDecode($object['_source']['middle_name']);
						$robject->last_name 			=	(string)$this->common->entityDecode($object['_source']['last_name']);
						$robject->display_name 			=	(string)$this->common->entityDecode($object['_source']['display_name']);
						$robject->email 				=	(string)$object['_source']['email'];
						$robject->ad_id					=	(string)$object['_source']['ad_id'];
						$robject->is_email_verified 	=	(string)$object['_source']['is_email_verified'];
						$robject->mobile_number 		=	(string)$object['_source']['mobile_number'];
						$robject->mobile_isd 			=	(string)$object['_source']['mobile_isd'];
						$robject->is_mobile_verified 	=	(string)$object['_source']['is_mobile_verified'];
						$robject->is_mobile_number_visible	=	(string)$object['_source']['is_mobile_number_visible'];
					endif;
				endforeach;
			endif;
			return (!empty($robject)?$robject:[]);
		}
		catch(Exception $e)
		{  $this->es_error($e);   }
	}

	public function getEmployeeByEmail($email){
		try
		{
			$params = [];
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	= 	[['term' => ['email' => (string)$email]],
															['term' => ['enabled' => (int)1]]];
			$objects	=	$this->es_search($params);

			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$object		=	(object)$object['_source'];
						$robject	=	new stdClass();
						$robject->employee_id		=	(int)$object->employee_id;
						$robject->employee_code		=	(string)$object->employee_code;
						$robject->employee_type		=	(string)$object->employee_type;
						$robject->first_name		=	(string)$this->common->entityDecode($object->first_name);
						$robject->middle_name		=	(string)$this->common->entityDecode($object->middle_name);
						$robject->last_name			=	(string)$this->common->entityDecode($object->last_name);
						$robject->display_name		=	(string)$this->common->entityDecode($object->display_name);
						$robject->date_of_birth		=	(object)[	'date' 		=>	(string)$object->date_of_birth,
																	'visibility'=> ($object->is_birth_day_visible)?true:false];

						$robject->gender			=	(string)$object->gender;
						$robject->blood_group		=	(object)[	'group' 	=> (string)$object->blood_group,
																	'visibility'=> ($object->is_blood_group_visible)?true:false];
						$robject->description		=	(string)$this->common->entityDecode($object->description);
						$robject->profile_picture	=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																	'image_path'	=>	(string)$object->profile_picture];
						$robject->social			=	(object)[	'linkedin_url' 	=> (string)$object->linkedin_url,
																	'twitter_url' 	=> (string)$object->twitter_url,
																	'website_url' 	=> (string)$object->website_url,
																	'open_id' 		=> (string)$object->open_id];
						$robject->job_code			=	(string)$object->job_code;
						$robject->position_title	=	(string)$this->common->entityDecode($object->position_title);
						$robject->date_of_joining	=	(string)$object->date_of_joining;
						$d2							=  	new DateTime(date("Y-m-d"));
						$diff 						=  	$d2->diff( new DateTime($robject->date_of_joining));
						$robject->service_years		=	(int)$diff->y;
						$robject->supervisor_id		=	(string)$object->supervisor_id;
						$robject->supervisor_email	=	(string)$object->supervisor_email;
						$robject->supervisor_name	=	(string)$this->common->entityDecode($object->supervisor_name);
						$robject->address			=	(string)$this->common->entityDecode($object->address);
						$robject->email				=	(string)$object->email;
						$robject->ad_id				=	(string)$object->ad_id;
						$robject->is_email_verified	=	(boolean)($object->is_email_verified==1)?true:false;
						$robject->work_phone		=	(object)[	'isd_code'		=>	(string)(!empty($object->workphone_isd)?str_replace('+', '', $object->workphone_isd):''),
																	'number' 		=>	(string)ltrim($object->work_phone,'0'),
																	'extension'		=>	(string)$object->work_phone_extn];
						$robject->mobile_number		=	(object)[	'isd_code'		=>	(string)(!empty($object->mobile_isd)?str_replace('+', '', $object->mobile_isd):''),
																	'number'			=>	(string)ltrim($object->mobile_number,'0'),
																	'is_mobile_verified'=>	(boolean)($object->is_mobile_verified==1)?true:false,
																	'is_mobile_number_visible'	=>	(boolean)($object->is_mobile_number_visible==1)?true:false];
						$robject->emergency_contact	=	(object)[	'name'			=>	(string)$this->common->entityDecode($object->emergency_contact_name),
																	'phone'			=>	(object)[	'isd_code'	=>	(string)(!empty($object->emergency_contact_phone_isd)?str_replace('+', '', $object->emergency_contact_phone_isd):''),
																									'number' 	=>	(string)ltrim($object->emergency_contact_phone,'0')],
																	'email'			=>	(string)$object->emergency_contact_email];

						$robject->extra				=	(object)[	'secure_pin' 	=> 	(string)$object->secure_pin,
																	'salt_string' 	=> 	(string)$object->salt_string,
																	'attempts' 		=> 	(int)$object->attempts,
																	'locked_till' 	=> 	(string)$object->locked_till,
																	'accessed_on'	=>	(string)$object->accessed_on];

						$robject->wishes			=	(object)[	'birthday'		=>	(date('m-d', strtotime($object->date_of_birth))==date('m-d')?true:false),
																	'anniversary'	=>	(date('m-d', strtotime($object->date_of_joining))==date('m-d')?true:false),
																	'advancement'	=>	(!empty($object->advancements)?true:false),
																	'new_joinees'	=>	((strtotime($object->date_of_joining)>strtotime('-'._NEW_JOINEE_DAYS.' days'))?true:false)];


						// Get Departments
						$robject->department_code	=	(string)'';
						$robject->department_name	=	(string)'';
						if (!empty($object->department_id)):
							$ES_Department	=	new ES_Department();
							$objDepartment	=	$ES_Department->getById($object->department_id);
							if (!empty($objDepartment)):
								$robject->department_code	=	(string)$objDepartment->department_code;
								$robject->department_name	=	(string)$objDepartment->department_name;
							endif;
						endif;

						$robject->geography	=	(object)['geography_id'	=>(int)0, 'title'=>(string)'', 'abbreviation' => (string)'', 'isd_code' =>	(int)0];
						if (!empty($object->geography_id)):
							$ES_Geography	=	new ES_Geography();
							$objGeography	=	$ES_Geography->getById($object->geography_id);
							if (!empty($objGeography)):
								$robject->geography	=	(object)[	'geography_id'	=>	(int)$objGeography->geography_id,
																	'title'			=>	(string)$objGeography->value,
																	'abbreviation'	=>	(string)$objGeography->abbreviation,
																	'isd_code'		=>	(int)$objGeography->isd_code,
																	'currency_id'	=>	(int)$objGeography->currency_id];
							endif;
						endif;

						$robject->location	=	(object)['location_id'	=>	(int)0, 'title'	=> (string)''];
						if (!empty($object->geography_id)):
							$ES_Location	=	new ES_Location();
							$objLocation	=	$ES_Location->getById($object->location_id);
							if (!empty($objLocation)):
								$robject->location	=	(object)[	'location_id'	=>	(int)$objLocation->location_id,
																	'title'			=>	(string)$objLocation->value];
							endif;
						endif;

						$robject->function	=	(object)['function_id'	=>	(int)0, 'title'	=> (string)''];
						if (!empty($object->function_id)):
							$ES_Function	=	new ES_Function();
							$objFunction	=	$ES_Function->getById($object->function_id);
							if (!empty($objFunction)):
								$robject->function	=	(object)[	'function_id'	=>	(int)$objFunction->function_id,
																	'title'			=>	(string)$objFunction->value];
							endif;
						endif;

						$robject->level	=	(object)['level_id'	=>	(int)0, 'title'	=> (string)''];
						if (!empty($object->level_id)):
							$ES_Level	=	new ES_Level();
							$objLevel	=	$ES_Level->getById($object->level_id);
							if (!empty($objLevel)):
								$robject->level		=	(object)[	'level_id'		=>	(int)$objLevel->level_id,
																	'title'			=>	(string)$objLevel->name];
							endif;
						endif;

						$robject->layer	=	(object)['layer_id'	=>	(int)0, 'title'	=> (string)''];
						if (!empty($object->layer_id)):
								$ES_Layer	=	new ES_Layer();
								$objLayer	=	$ES_Layer->getById($object->layer_id);
								if (!empty($objLayer)):
									$robject->layer		=	(object)[	'layer_id'		=>	(int)$objLayer->layer_id,
																		'title'			=>	(string)$objLayer->name];
							endif;
						endif;

						$robject->establishment		=	(object)['establishment_id'	=>	(int)0, 'title'	=> (string)'' ];
						if (!empty($object->establishment_id)):
								$ES_Establishment	=	new ES_Establishment();
								$objEst				=	$ES_Establishment->getById($object->establishment_id);
								if (!empty($objEst)):
									$robject->establishment		=	(object)[	'establishment_id'		=>	(int)$objEst->establishment_id,
																				'title'					=>	(string)$objEst->name];
								endif;
						endif;


						$robject->is_walk_through	=	(int)$object->is_walk_through;
						$robject->is_pledged	    =	(int)$object->is_pledged;
						$ES_Preference	=	new ES_Preference();
						$objPrefs		=	$ES_Preference->getPreferences();
						$robject->preferences	=	[];
						if (!empty($objPrefs)):
							$nPrefs	=	[];
							foreach ($objPrefs as $pref):
								$objPref		=	new stdClass();
								$objPref->preference_id 	=	(int)$pref->preference_id;
								$objPref->title 			=	(string)$pref->feature;
								$objPref->on				=	(boolean)true;

								if (!empty($object->preferences)):
									$objPref->on	=	(boolean)(in_array($objPref->preference_id,$object->preferences))?false:true;
								endif;
								array_push($nPrefs, $objPref);
							endforeach;
							$robject->preferences	=	$nPrefs;
						endif;

						// profile from stars of microland
						$es_som						=	new ES_Som_Employee();
						$som_link_arr				=	$es_som->setSomLinks([$robject->employee_id]);
						$robject->som_links			=	isset($som_link_arr[$robject->employee_id])?$som_link_arr[$robject->employee_id]:[];

					endif;
				endforeach;
			endif;
			return (!empty($robject))?$robject:[];
		}
		catch(Exception $e)
		{	$this->es_error($e);   }
	}

	public function getEmployees($object){
		try{
			$params = [];
			$params['from']  	= 	$object->start??0;
			$params['size']  	= 	$object->end??100;
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;


			if (!empty($object->random)):
				$params['body']['query']['function_score']['query']['bool']['must']	= [['term' => ['enabled' => (int)1]]];
				$params['body']['query']['function_score']['random_score'] = ['seed' => mt_rand()];
			else:
				$params['body']['query']['bool']['must']	= 	[['term' => ['enabled' => (int)1]]];

				if (!empty($object->range)):
					$ranges	=	array_map('intval', explode(',', $object->range));
					if (!empty($ranges)):
						array_push($params['body']['query']['bool']['must'], ['bool' =>	['should'=>	[]]]);
						foreach ($ranges as $range):
							if ($range==1):
								$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d'), 'gte' => (string)date('Y-m-d', strtotime('-6 month')),"format"=> "yyyy-MM-dd"]]];
								array_push($params['body']['query']['bool']['must'][1]['bool']['should'], $condition);
							endif;
							if ($range==2):
								$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-6 month')), 'gte' => (string)date('Y-m-d', strtotime('-1 year')),"format"=> "yyyy-MM-dd"]]];
								array_push($params['body']['query']['bool']['must'][1]['bool']['should'], $condition);
							endif;
							if ($range==3):
								$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-1 year')), 'gte' => (string)date('Y-m-d', strtotime('-2 year')),"format"=> "yyyy-MM-dd"]]];
								array_push($params['body']['query']['bool']['must'][1]['bool']['should'], $condition);
							endif;
							if ($range==4):
								$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-2 year')), 'gte' => (string)date('Y-m-d', strtotime('-5 year')),"format"=> "yyyy-MM-dd"]]];
								array_push($params['body']['query']['bool']['must'][1]['bool']['should'], $condition);
							endif;
							if ($range==5):
								$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-5 year')), 'gte' => (string)date('Y-m-d', strtotime('-10 year')),"format"=> "yyyy-MM-dd"]]];
								array_push($params['body']['query']['bool']['must'][1]['bool']['should'], $condition);
							endif;
							if ($range==6):
								$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-10 year')), 'gte' => (string)date('Y-m-d', strtotime('-15 year')),"format"=> "yyyy-MM-dd"]]];
								array_push($params['body']['query']['bool']['must'][1]['bool']['should'], $condition);
							endif;
							if ($range==7):
								$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-15 year')), 'gte' => (string)date('Y-m-d', strtotime('-20 year')),"format"=> "yyyy-MM-dd"]]];
								array_push($params['body']['query']['bool']['must'][1]['bool']['should'], $condition);
							endif;
							if ($range==8):
								$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-20 year')),"format"=> "yyyy-MM-dd"]]];
								array_push($params['body']['query']['bool']['must'][1]['bool']['should'], $condition);
							endif;
						endforeach;
					endif;
				endif;

				if (!empty($object->query)):
					$query	=	['query_string' => ['fields'		=>	['full_name^3', 'first_name^2', 'middle_name', 'last_name'],
													'query'			=> (string)trim($object->query).'*',
													'phrase_slop'	=>	1,
													'type'			=>	'best_fields']];
					array_push($params['body']['query']['bool']['must'], $query);
				endif;

				if (empty($object->random)):
					if (!empty($object->geography_id)):
						array_push($params['body']['query']['bool']['must'], ['terms' => ['geography_id' => array_map('intval', explode(',', $object->geography_id))]]);
					endif;
					if (!empty($object->location_id)):
						array_push($params['body']['query']['bool']['must'], ['terms' => ['location_id' => array_map('intval', explode(',', $object->location_id))]]);
					endif;
					if (!empty($object->function_id)):
						array_push($params['body']['query']['bool']['must'], ['terms' => ['function_id' => array_map('intval', explode(',', $object->function_id))]]);
					endif;
					if (!empty($object->level_id)):
						array_push($params['body']['query']['bool']['must'], ['terms' => ['level_id' => array_map('intval', explode(',', $object->level_id))]]);
					endif;
					if (!empty($object->layer_id)):
						array_push($params['body']['query']['bool']['must'], ['terms' => ['layer_id' => array_map('intval', explode(',', $object->layer_id))]]);
					endif;
				endif;
				if (empty($object->query)):
					$params['body']['sort']	=	[['first_name' => ['order'	=>	'asc']], ['middle_name' => ['order' => 'asc']], ['last_name' => ['order' => 'asc']]];
				endif;
			endif;
			return $this->setOutput($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);   }
	}

	public function getAllMatchedEmployees($object){
		try{
			$params = [];
			$params['from']  	= 	$object->start??0;
			$params['size']  	= 	$object->end??100;
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['scroll']  	= 	'30s';
			$params['body']['query']['bool']['must']		= 	[['term' => ['enabled' => (int)1]]];
			$params['body']['query']['bool']['must_not']	=	['term' 	=> ['employee_id'	=>	(int)$object->employee_id]];
			if (!empty($object->geography_id)):
				array_push($params['body']['query']['bool']['must'], ['terms' => ['geography_id' => array_map('intval', explode(',', $object->geography_id))]]);
			endif;
			if (!empty($object->location_id)):
				array_push($params['body']['query']['bool']['must'], ['terms' => ['location_id' => array_map('intval', explode(',', $object->location_id))]]);
			endif;
			if (!empty($object->function_id)):
				array_push($params['body']['query']['bool']['must'], ['terms' => ['function_id' => array_map('intval', explode(',', $object->function_id))]]);
			endif;
			if (!empty($object->level_id)):
				array_push($params['body']['query']['bool']['must'], ['terms' => ['level_id' => array_map('intval', explode(',', $object->level_id))]]);
			endif;
			if (!empty($object->layer_id)):
				array_push($params['body']['query']['bool']['must'], ['terms' => ['layer_id' => array_map('intval', explode(',', $object->layer_id))]]);
			endif;
			$params['body']['sort']	=	[['first_name' => ['order'	=>	'asc']], ['middle_name' => ['order' => 'asc']], ['last_name' => ['order' => 'asc']]];

			$results	=	$this->es_search($params);
			$rObject	=	[];

			$ES_Department	=	new ES_Department();
			$ES_Geography	=	new ES_Geography();
			$ES_Location	=	new ES_Location();
			$ES_Function	=	new ES_Function();
			$ES_Level		=	new ES_Level();
			$ES_Layer		=	new ES_Layer();

			$objDepartments	=	$ES_Department->getDepartments();
			$objGeographies	=	$ES_Geography->getGeographies();
			$objLocations	=	$ES_Location->getLocations();
			$objFunctions	=	$ES_Function->getFunctions();
			$objLevels		=	$ES_Level->getLevels();
			$objLayers		= 	$ES_Layer->getLayers();

			$objMaster		=	(object)[	'departments'	=>	$objDepartments,
											'geographies'	=>	$objGeographies,
											'locations'		=>	$objLocations,
											'functions'		=>	$objFunctions,
											'levels'		=>	$objLevels ,
											'layers'		=>  $objLayers ];

			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						$row	=	(object)$object['_source'];
						array_push($rObject, $this->setObject($row, $objMaster));
					endforeach;
				endif;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return $rObject;
		}
		catch(Exception $e)
		{	$this->es_error($e);   }
	}

	public function getCelebrations($object){
		try{
			$params = [];
			$params['from']  	= 	$object->start??0;
			$params['size']  	= 	$object->end??100;
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;

			$DOJ	=	'doc.date_of_joining.date.monthOfYear=='.date("n").' && doc.date_of_joining.date.dayOfMonth=='.date('j');
			$DOB	=	'doc.date_of_birth.date.monthOfYear=='.date("n").' && doc.date_of_birth.date.dayOfMonth=='.date('j').' && doc.is_birth_day_visible.value==1';
			$ADV	=	'doc.advancements.value==true';

			$params['body']['query']['function_score']['query']['bool']['must'] 	= 	[['term' => ['enabled' => (int)1]],
																						['bool' => ['should' => [['script' => ['script' => ['source' => "$DOJ", 'lang' => 'painless']]],
																												['script' => ['script' => ['source' => "$DOB", 'lang' => 'painless']]],
																												['script' => ['script' => ['source' => "$ADV", 'lang' => 'painless']]]]]]];
			$params['body']['query']['function_score']['random_score'] = ['seed' => mt_rand()];
			return $this->setOutput($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);   }
	}

	public function getEmployeeByIds($ids){
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

	public function getEmployeeByIdsAndFilters($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['size'] 	= 	1000;
			$params['scroll']	=	'30s';

			$params['body']['query']['bool']['filter']	= 	[['terms' => ['employee_id' => array_values($object->ids)]]];
			if (!empty($object->geography_id)):
				array_push($params['body']['query']['bool']['filter'], ['terms' => ['geography_id' => array_map('intval', explode(',', $object->geography_id))]]);
			endif;
			if (!empty($object->location_id)):
				array_push($params['body']['query']['bool']['filter'], ['terms' => ['location_id' => array_map('intval', explode(',', $object->location_id))]]);
			endif;
			if (!empty($object->function_id)):
				array_push($params['body']['query']['bool']['filter'], ['terms' => ['function_id' => array_map('intval', explode(',', $object->function_id))]]);
			endif;
			if (!empty($object->level_id)):
				array_push($params['body']['query']['bool']['filter'], ['terms' => ['level_id' => array_map('intval', explode(',', $object->level_id))]]);
			endif;
			if (!empty($object->layer_id)):
				array_push($params['body']['query']['bool']['filter'], ['terms' => ['layer_id' => array_map('intval', explode(',', $object->layer_id))]]);
			endif;
			if (!empty($object->query)):
				$query	=	['query_string' => ['fields'		=>	['full_name^3', 'first_name^2', 'middle_name', 'last_name'],
												'query'			=> (string)trim($object->query).'*',
												'phrase_slop'	=>	1,
												'type'			=>	'best_fields']];
				$params['body']['query']['bool']['must'] = $query;
			endif;

			$params['body']['sort']	=	[['first_name' => ['order'	=>	'asc']], ['middle_name' => ['order' => 'asc']], ['last_name' => ['order' => 'asc']]];

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

	private function setOutput($results){
		try{
			$arr 	=	[]; $empIds = [];
			if ($results['hits']['total']>0):
				$ES_Department	=	new ES_Department();
				$ES_Geography	=	new ES_Geography();
				$ES_Location	=	new ES_Location();
				$ES_Function	=	new ES_Function();
				$ES_Level		=	new ES_Level();
				$ES_Layer		=	new ES_Layer();

				$objDepartments	=	$ES_Department->getDepartments();
				$objGeographies	=	$ES_Geography->getGeographies();
				$objLocations	=	$ES_Location->getLocations();
				$objFunctions	=	$ES_Function->getFunctions();
				$objLevels		=	$ES_Level->getLevels();
				$objLayers		=	$ES_Layer->getLayers();

				$objMaster		=	(object)[	'departments'	=>	$objDepartments,
												'geographies'	=>	$objGeographies,
												'locations'		=>	$objLocations,
												'functions'		=>	$objFunctions,
												'levels'		=>	$objLevels ,
												'layers'        =>  $objLayers];


				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row	=	(object)$obj['_source'];
						array_push($empIds,(int)$row->employee_id);
						array_push($arr, $this->setObject($row, $objMaster));
					endif;
				endforeach;

				// profile from stars of microland
				$es_som			=	new ES_Som_Employee();
				$som_link_arr	=	$es_som->setSomLinks($empIds);
				foreach ($arr as $key => $object):
					$object->som_links			=	isset($som_link_arr[$object->employee_id])?$som_link_arr[$object->employee_id]:[];
				endforeach;
			endif;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);   }
	}

	private function setObject($row, $objMaster){
		try{
			$object	=	new stdClass();

			$object->employee_id		=	(int)$row->employee_id;
			$object->employee_code		=	(string)$row->employee_code;
			$object->employee_type		=	(string)$row->employee_type;
			$object->first_name			=	(string)$this->common->entityDecode($row->first_name);
			$object->middle_name		=	(string)$this->common->entityDecode($row->middle_name);
			$object->last_name			=	(string)$this->common->entityDecode($row->last_name);
			$object->display_name		=	(string)$this->common->entityDecode($row->display_name);
			$object->date_of_birth		=	(object)[	'date' 		=>	(string)$row->date_of_birth,
														'visibility'=> ($row->is_birth_day_visible)?true:false];
			$object->gender				=	(string)$row->gender;
			$object->blood_group		=	(object)[	'group' 	=> (string)$row->blood_group,
														'visibility'=> ($row->is_blood_group_visible)?true:false];
			$object->description		=	(string)$this->common->entityDecode($row->description);
			$object->profile_picture	=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
														'image_path'	=>	(string)$row->profile_picture];
			$object->social				=	(object)[	'linkedin_url' 	=> (string)$row->linkedin_url,
														'twitter_url' 	=> (string)$row->twitter_url,
														'website_url' 	=> (string)$row->website_url,
														'open_id' 		=> (string)$row->open_id];
			$object->job_code			=	(string)$row->job_code;
			$object->position_title		=	(string)$this->common->entityDecode($row->position_title);
			$object->date_of_joining	=	(string)$row->date_of_joining;

			$d2							=  	new DateTime(date("Y-m-d"));
			$diff 						=  	$d2->diff( new DateTime($object->date_of_joining));
			$object->service_years		=	(int)$diff->y;

			$object->supervisor_id		=	(string)$row->supervisor_id;
			$object->supervisor_email	=	(string)$row->supervisor_email;
			$object->supervisor_name	=	(string)$this->common->entityDecode($row->supervisor_name);
			$object->address			=	(string)$row->address;

			$object->department_code	=	(string)'';
			$object->department_name	=	(string)'';

			if (!empty($row->department_id)):
				$Key	=	array_search($row->department_id, array_column($objMaster->departments, 'department_id'));
				if ($Key!==false):
					$object->department_code	=	(string)$objMaster->departments[$Key]->department_code;
					$object->department_name	=	(string)$objMaster->departments[$Key]->department_name;
				endif;
			endif;

			$object->geography	=	new stdClass();
			$Key	=	array_search($row->geography_id, array_column($objMaster->geographies, 'geography_id'));
			if ($Key!==false):
				$object->geography	= (object)[	'geography_id' => (int)$objMaster->geographies[$Key]->geography_id,
												'title' => (string)$objMaster->geographies[$Key]->title];
			endif;

			$object->location	=	new stdClass();
			$Key	=	array_search($row->location_id, array_column($objMaster->locations, 'location_id'));
			if ($Key!==false):
				$object->location	= (object)[	'location_id' => (int)$objMaster->locations[$Key]->location_id,
												'title' => (string)$objMaster->locations[$Key]->title];
			endif;

			$object->function	=	new stdClass();
			$Key	=	array_search($row->function_id, array_column($objMaster->functions, 'function_id'));
			if ($Key!==false):
				$object->function	= (object)[	'function_id' => (int)$objMaster->functions[$Key]->function_id,
												'title' => (string)$objMaster->functions[$Key]->title];
			endif;

			$object->level	=	new stdClass();
			$Key	=	array_search($row->level_id, array_column($objMaster->levels, 'level_id'));
			if ($Key!==false):
				$object->level	= (object)[	'level_id' => (int)$objMaster->levels[$Key]->level_id,
											'title' => (string)$objMaster->levels[$Key]->title];
			endif;


			$object->layer	=	new stdClass();
			$Key	=	array_search($row->layer_id, array_column($objMaster->layers, 'layer_id'));
			if ($Key!==false):
				$object->layer	= 	(object)[	'layer_id' => (int)$objMaster->layers[$Key]->layer_id,
												'title' => (string)$objMaster->layers[$Key]->title];
			endif;



			$object->email				=	(string)$row->email;
			$object->ad_id				=	(string)$row->ad_id;
			$object->work_phone			=	(object)[	'isd_code'		=>	(string)(!empty($row->workphone_isd)?str_replace('+', '', $row->workphone_isd):''),
														'number' 		=>	(string)ltrim($row->work_phone,'0'),
														'extension'		=>	(string)$row->work_phone_extn];
			$object->mobile_number		=	(object)[	'isd_code'		=>	(string)(!empty($row->mobile_isd)?str_replace('+', '', $row->mobile_isd):''),
														'number'		=>	(string)ltrim($row->mobile_number,'0'),
														'is_mobile_number_visible'	=>	(boolean)($row->is_mobile_number_visible==1)?true:false
			];
			$object->emergency_contact	=	(object)[	'name'			=>	(string)$this->common->entityDecode($row->emergency_contact_name),
														'phone'			=>	['isd_code'	=>	(string)(!empty($row->emergency_contact_phone_isd)?str_replace('+', '', $row->emergency_contact_phone_isd):''),
																			'number' 	=>	(string)ltrim($row->emergency_contact_phone,'0')],
																			'email'		=>	(string)$row->emergency_contact_email];

			$object->wishes				=	(object)[	'birthday'		=>	(date('m-d', strtotime($row->date_of_birth))==date('m-d')?true:false),
														'anniversary'	=>	(date('m-d', strtotime($row->date_of_joining))==date('m-d')?true:false),
														'advancement'	=>	(!empty($row->advancements)?true:false),
														'new_joinees'	=>	((strtotime($row->date_of_joining)>strtotime('-'._NEW_JOINEE_DAYS.' days'))?true:false)];



			return $object;
		}
		catch(Exception $e)
		{	$this->es_error($e);   }
	}

	public function getEmpById($empId){
		try
		{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$empId;

			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				$row		=	(object)$result['_source'];
				$object		=	new stdClass();
				$object->employee_id		=	(int)$row->employee_id;
				$object->employee_code		=	(string)$row->employee_code;
				$object->employee_type		=	(string)$row->employee_type;
				$object->first_name			=	(string)$this->common->entityDecode($row->first_name);
				$object->middle_name		=	(string)$this->common->entityDecode($row->middle_name);
				$object->last_name			=	(string)$this->common->entityDecode($row->last_name);
				$object->display_name		=	(string)$this->common->entityDecode($row->display_name);
				$object->date_of_birth		=	(object)[	'date' 		=>	(string)$row->date_of_birth,
															'visibility'=> 	($row->is_birth_day_visible)?true:false];

				$object->gender				=	(string)$row->gender;
				$object->blood_group		=	(object)[	'group' 	=> (string)$row->blood_group,
															'visibility'=> ($row->is_blood_group_visible)?true:false];
				$object->description		=	(string)$this->common->entityDecode($row->description);
				$object->profile_picture	=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
															'image_path'	=>	(string)$row->profile_picture];
				$object->social				=	(object)[	'linkedin_url' 	=> (string)$row->linkedin_url,
															'twitter_url' 	=> (string)$row->twitter_url,
															'website_url' 	=> (string)$row->website_url,
															'open_id' 		=> (string)$row->open_id];
				$object->job_code			=	(string)$row->job_code;
				$object->position_title		=	(string)$this->common->entityDecode($row->position_title);
				$object->date_of_joining	=	(string)$row->date_of_joining;

				$d2							=  	new DateTime(date("Y-m-d"));
				$diff 						=  	$d2->diff( new DateTime($object->date_of_joining));
				$object->service_years		=	(int)$diff->y;

				$object->supervisor_id		=	(string)$row->supervisor_id;
				$object->supervisor_email	=	(string)$row->supervisor_email;
				$object->supervisor_name	=	(string)$this->common->entityDecode($row->supervisor_name);
				$object->address			=	(string)$row->address;

				$object->department_code	=	(string)'';
				$object->department_name	=	(string)'';
				if (!empty($row->department_id)):
					$ES_Department	=	new ES_Department();
					$objDepartment	=	$ES_Department->getById($row->department_id);
					if (!empty($objDepartment)):
						$object->department_code	=	(string)$objDepartment->department_code;
						$object->department_name	=	(string)$objDepartment->department_name;
					endif;
				endif;

				$object->geography	=	(object)['geography_id'	=>(int)0, 'title'=>(string)'', 'abbreviation' => (string)'', 'isd_code' =>	(int)0];
				if (!empty($row->geography_id)):
					$ES_Geography	=	new ES_Geography();
					$objGeography	=	$ES_Geography->getById($row->geography_id);
					if (!empty($objGeography)):
						$object->geography	=	(object)[	'geography_id'	=>	(int)$objGeography->geography_id,
															'title'			=>	(string)$objGeography->value,
															'abbreviation'	=>	(string)$objGeography->abbreviation,
															'isd_code'		=>	(int)$objGeography->isd_code,
															'currency_id'	=>	(int)$objGeography->currency_id];
					endif;
				endif;

				$object->location	=	(object)['location_id'	=>	(int)0, 'title'	=> (string)''];
				if (!empty($row->geography_id)):
					$ES_Location	=	new ES_Location();
					$objLocation	=	$ES_Location->getById($row->location_id);
					if (!empty($objLocation)):
						$object->location	=	(object)[	'location_id'	=>	(int)$objLocation->location_id,
															'title'			=>	(string)$objLocation->value];
					endif;
				endif;

				$object->function	=	(object)['function_id'	=>	(int)0, 'title'	=> (string)''];
				if (!empty($row->function_id)):
					$ES_Function	=	new ES_Function();
					$objFunction	=	$ES_Function->getById($row->function_id);
					if (!empty($objFunction)):
						$object->function	=	(object)[	'function_id'	=>	(int)$objFunction->function_id,
															'title'			=>	(string)$objFunction->value];
					endif;
				endif;

				$object->level	=	(object)['level_id'	=>	(int)0, 'title'	=> (string)''];
				if (!empty($row->level_id)):
					$ES_Level	=	new ES_Level();
					$objLevel	=	$ES_Level->getById($row->level_id);
					if (!empty($objLevel)):
						$object->level		=	(object)[	'level_id'		=>	(int)$objLevel->level_id,
															'title'			=>	(string)$objLevel->name];
					endif;
				endif;

				$object->layer	=	(object)['layer_id'	=>	(int)0, 'title'	=> (string)''];
				if (!empty($row->layer_id)):
					$ES_Layer	=	new ES_Layer();
					$objLayer	=	$ES_Layer->getById($row->layer_id);
					if (!empty($objLevel)):
						$object->layer	= 	(object)[	'layer_id'  	=> 	(int)$objLayer->layer_id,
														'title'			=>	(string)$objLayer->name];
					endif;
				endif;

				$object->establishment		=	(object)['establishment_id'	=>	(int)0, 'title'	=> (string)'' ];
				if (!empty($row->establishment_id)):
					$ES_Establishment	=	new ES_Establishment();
					$objEst				=	$ES_Establishment->getById($row->establishment_id);
					if (!empty($objEst)):
						$object->establishment		=	(object)[	'establishment_id'		=>	(int)$objEst->establishment_id,
																	'title'					=>	(string)$objEst->name];
					endif;
				endif;


				$object->email				=	(string)$row->email;
				$object->ad_id				=	(string)$row->ad_id;
				$object->is_email_verified	=	(boolean)($row->is_email_verified==1)?true:false;
				$object->work_phone			=	(object)[	'isd_code'		=>	(string)(!empty($row->workphone_isd)?str_replace('+', '', $row->workphone_isd):''),
															'number' 		=>	(string)ltrim($row->work_phone,'0'),
															'extension'		=>	(string)$row->work_phone_extn];
				$object->mobile_number		=	(object)[	'isd_code'		=>	(string)(!empty($row->mobile_isd)?str_replace('+', '', $row->mobile_isd):''),
															'number'		=>	(string)ltrim($row->mobile_number,'0'),
															'is_mobile_verified'=>	(boolean)($row->is_mobile_verified==1)?true:false,
															'is_mobile_number_visible'	=>	(boolean)($row->is_mobile_number_visible==1)?true:false
				];
				$object->emergency_contact	=	(object)[	'name'	=>	(string)$this->common->entityDecode($row->emergency_contact_name),
															'phone'	=>	(object)[	'isd_code'	=>	(string)(!empty($row->emergency_contact_phone_isd)?str_replace('+', '', $row->emergency_contact_phone_isd):''),
																					'number' 	=>	(string)ltrim($row->emergency_contact_phone,'0')],
															'email'	=>	(string)$row->emergency_contact_email];
				$object->wishes				=	(object)[	'birthday'		=>	(date('m-d', strtotime($row->date_of_birth))==date('m-d')?true:false),
															'anniversary'	=>	(date('m-d', strtotime($row->date_of_joining))==date('m-d')?true:false),
															'advancement'	=>	(!empty($row->advancements)?true:false),
															'new_joinees'	=>	((strtotime($row->date_of_joining)>strtotime('-'._NEW_JOINEE_DAYS.' days'))?true:false)];
				// profile from stars of microland
				$es_som						=	new ES_Som_Employee();
				$som_link_arr				=	$es_som->setSomLinks([$object->employee_id]);
				$object->som_links			=	isset($som_link_arr[$object->employee_id])?$som_link_arr[$object->employee_id]:[];
			endif;
			return !empty($object)?$object:new stdClass();
		}
		catch(Exception $e)
		{	$this->es_error($e);   }
	}

	public function verifySecurePin($object){
		try{
			$params = [];
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	= 	[	['term' => ['secure_pin' => (string)$object->secure_pin]],
																['term' => ['employee_id' => (int)$object->employee_id]],
																['term' => ['enabled' => (int)1]]];
			$objects	=	$this->es_search($params);
			return ($objects['hits']['total']>0)?true:false;
		}
		catch(Exception $e)
		{	$this->es_error($e);   }
	}

	public function updateFailedAttempts($object){
		try{
			$params = [];
			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	(int)$object->employee_id;


			$doc	=	new stdClass();
			$doc->attempts		=	(int)$object->attempts;
			$doc->locked_till	=	(string)($object->locked_till==NULL)?'':$object->locked_till;

			if (!empty($object->last_login_time)):
				$doc->last_login_time	=	(string)$object->last_login_time;
			endif;
			$params['body']	= [	'doc' => (array)$doc];

			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function updateMobileNumber($object){
		try{
			$doc	=	new stdClass();
			$doc->mobile_isd			=	(string)$object->mobile_number->isd_code;
			$doc->mobile_number			=	(string)$object->mobile_number->number;
			$doc->mobile_otp			=	(int)$object->mobile_otp;
			$doc->is_mobile_verified	=	(int)$object->mobile_number->is_mobile_verified;


			$params = [];
			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	(int)$object->employee_id;
			$params['body']	= [	'doc' => (array)$doc];

			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function verifyMobileOTP($object){
		try
		{
			$params = [];
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	= 	[	['term' => ['mobile_otp' => (int)$object->mobile_otp]],
																['term' => ['employee_id' => (int)$object->employee_id]],
																['term' => ['enabled' => (int)1]]];
			$objects	=	$this->es_search($params);
			return ($objects['hits']['total']>0)?true:false;
		}
		catch(Exception $e)
		{	$this->es_error($e);  }
	}

	public function updateMobileVerifyStatus($object){
		try{
			$doc	=	new stdClass();
			$doc->mobile_otp			=	(int)$object->mobile_otp;
			$doc->is_mobile_verified	=	(int)$object->mobile_number->is_mobile_verified;

			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->employee_id;
			$params['body']		= 	[	'doc' => (array)$doc];

			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function updateEmailOTP($object){
		try{
			$doc	=	new stdClass();
			$doc->email_otp			=	(int)$object->email_otp;
			$doc->is_email_verified	=	(int)$object->is_email_verified;

			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->employee_id;
			$params['body']		= 	[	'doc' => (array)$doc];

			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function verifyEmailOTP($object){
		try{
			$params = [];
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	= 	[	['term' => ['email_otp' => (int)$object->email_otp]],
																['term' => ['employee_id' => (int)$object->employee_id]],
																['term' => ['enabled' => (int)1]]];
			$objects	=	$this->es_search($params);
			return ($objects['hits']['total']>0)?true:false;
		}
		catch(Exception $e)
		{ 	$this->es_error($e);   }
	}

	public function updateEmpSecurePin($object){
		try{
			$doc	=	new stdClass();
			$doc->secure_pin		=	(string)md5($object->salt_string.$object->secure_pin);
			$doc->salt_string		=	(string)$object->salt_string;
			$doc->secure_pin_otp	=	(int)$object->secure_pin_otp;
			$doc->updated_on		=	(string)$object->updated_on;

			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->employee_id;
			$params['body']		= 	[	'doc' => (array)$doc];

			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function updatePinOTP($object){
		try{
			$doc	=	new stdClass();
			$doc->secure_pin_otp	=	(int)$object->secure_pin_otp;

			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->employee_id;
			$params['body']		= 	[	'doc' => (array)$doc];

			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function verifyPinOTP($object){
		try{
			$params = [];
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['must']	= 	[	['term' => ['secure_pin_otp' => (int)$object->secure_pin_otp]],
																['term' => ['employee_id' => (int)$object->employee_id]],
																['term' => ['enabled' => (int)1]]];
			$objects	=	$this->es_search($params);
			return ($objects['hits']['total']>0)?true:false;
		}
		catch(Exception $e)
		{ 	$this->es_error($e);   }
	}

	public function updateProfilePicture($object){
		try{
			$doc	=	new stdClass();
			$doc->profile_picture	=	(string)$object->profile_picture;
			$doc->updated_on		=	(string)$object->updated_on;

			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['id']    	= 	(int)$object->employee_id;
			$params['body']		= 	[	'doc' => (array)$doc];

			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function replaceNotificationReadLog($object) {
		try{
			$params = [];
			$params['body']	= [	'doc' => ["accessed_on" => (string)$object->accessed_on]];

			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	(int)$object->employee->employee_id;
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function updateField($objectId, $field, $value){
		try{
			$params = [];
			$params['body']	= [	'doc' => [	"$field" =>	$value ]];

			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	$objectId;
			return $this->es_update($params);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function updateStatusField($objectId,$published,$enabled){
		try{
			$params = [];
			$params['body']	= [	'doc' => [	'published'			=>	(int)$published ,
											'enabled'			=>	(int)$enabled ] ];

			$params['index'] = 	$this->index;
			$params['type']  = 	$this->type;
			$params['id']    = 	$objectId;
			return $this->es_update($params);
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

	// Celebrations
	public function getEmployeeByOccasion($object) {
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$params['from']  	= 	0;
			$params['size']  	= 	1000;

			$params['body']['query']['function_score']['query']['bool']['must'] 	= 	[['term' => ['enabled' => (int)1]]];

			$tday	=	date('j');
			$tMonth	=	date('n');
			$tyear	=	date('Y');

			if ($object->type=='birthday' || $object->type=='birthdays'):
				if (!empty($object->date)):
					$tday	=	date('j', strtotime($object->date));
					$tMonth	=	date('n', strtotime($object->date));
				endif;
				$DOB	=	'doc.date_of_birth.date.monthOfYear=='.$tMonth.' && doc.date_of_birth.date.dayOfMonth=='.$tday.' && doc.is_birth_day_visible.value==1';
				$condition	=	['script' => ['script' => ['source' => "$DOB", 'lang' => 'painless']]];
				array_push($params['body']['query']['function_score']['query']['bool']['must'], $condition);
			endif;

			if ($object->type=='anniversary' || $object->type=='anniversaries'):
				if (!empty($object->date)):
					$tday	=	date('j', strtotime($object->date));
					$tMonth	=	date('n', strtotime($object->date));
					$tyear	=	date('Y', strtotime($object->date));
				endif;
				$DOJ	=	'doc.date_of_joining.date.monthOfYear=='.$tMonth.' && doc.date_of_joining.date.dayOfMonth=='.$tday.' && doc.date_of_joining.date.year < '.$tyear;
				$condition	=	['script' => ['script' => ['source' => "$DOJ", 'lang' => 'painless']]];
				array_push($params['body']['query']['function_score']['query']['bool']['must'], $condition);
			endif;

			if ($object->type=='advancements'):
				$ADV	=	'doc.advancements.value==true';
				if (!empty($object->date)):
					$tday	=	date('j', strtotime($object->date));
					$tMonth	=	date('n', strtotime($object->date));
					$tyear	=	date('Y', strtotime($object->date));
					$ADV	=	'doc.advancements.value==true && doc.date_of_advancement.date.monthOfYear=='.$tMonth.' && doc.date_of_advancement.date.dayOfMonth=='.$tday.' && doc.date_of_advancement.date.year=='.$tyear;
				endif;
				$condition	=	['script' => ['script' => ['source' => "$ADV", 'lang' => 'painless']]];
				array_push($params['body']['query']['function_score']['query']['bool']['must'], $condition);
			endif;

			if ($object->type=='joinees'):
				$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d'), 'gte' => (string)date('Y-m-d', strtotime('-'._NEW_JOINEE_DAYS.' day')),"format"=> "yyyy-MM-dd"]]];
				if (!empty($object->date)):
					$tday	=	date('j', strtotime($object->date));
					$tMonth	=	date('n', strtotime($object->date));
					$tyear	=	date('Y', strtotime($object->date));

					$NJS	=	'doc.date_of_joining.date.monthOfYear=='.$tMonth.' && doc.date_of_joining.date.dayOfMonth=='.$tday.' && doc.date_of_joining.date.year=='.$tyear;
					$condition	=	['script' => ['script' => ['source' => "$NJS", 'lang' => 'painless']]];
				endif;
				array_push($params['body']['query']['function_score']['query']['bool']['must'], $condition);
			endif;

			if (!empty($object->range) && $object->type!='joinees'):
				$ranges	=	array_map('intval', explode(',', $object->range));
				if (!empty($ranges)):
					$rangeConditions	=	['bool' => ['should' => []]];
					foreach ($ranges as $range):
						if ($range==1):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d'), 'gte' => (string)date('Y-m-d', strtotime('-6 month')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==2):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-6 month')), 'gte' => (string)date('Y-m-d', strtotime('-1 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==3):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-1 year')), 'gte' => (string)date('Y-m-d', strtotime('-2 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==4):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-2 year')), 'gte' => (string)date('Y-m-d', strtotime('-5 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==5):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-5 year')), 'gte' => (string)date('Y-m-d', strtotime('-10 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==6):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-10 year')), 'gte' => (string)date('Y-m-d', strtotime('-15 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==7):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-15 year')), 'gte' => (string)date('Y-m-d', strtotime('-20 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==8):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-20 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
					endforeach;
					array_push($params['body']['query']['function_score']['query']['bool']['must'], $rangeConditions);
				endif;
			endif;

			if (!empty($object->geography_id)):
				array_push($params['body']['query']['function_score']['query']['bool']['must'], ['terms' => ['geography_id' => array_map('intval', explode(',', $object->geography_id))]]);
			endif;
			if (!empty($object->location_id)):
				array_push($params['body']['query']['function_score']['query']['bool']['must'], ['terms' => ['location_id' => array_map('intval', explode(',', $object->location_id))]]);
			endif;
			if (!empty($object->function_id)):
				array_push($params['body']['query']['function_score']['query']['bool']['must'], ['terms' => ['function_id' => array_map('intval', explode(',', $object->function_id))]]);
			endif;
			if (!empty($object->level_id)):
				array_push($params['body']['query']['function_score']['query']['bool']['must'], ['terms' => ['level_id' => array_map('intval', explode(',', $object->level_id))]]);
			endif;
			if (!empty($object->layer_id)):
				array_push($params['body']['query']['function_score']['query']['bool']['must'], ['terms' => ['layer_id' => array_map('intval', explode(',', $object->layer_id))]]);
			endif;

			if ($object->type=='joinees'):
				$params['body']['sort']	=	[['date_of_joining' => ['order'	=>	'desc']], ['first_name' => ['order' => 'asc']]];
			elseif($object->type=='advancements'):
				$params['body']['sort']	=	[['date_of_advancement' => ['order'	=>	'desc']], ['first_name' => ['order' => 'asc']]];
			else:
				$params['body']['sort']	=	[['first_name' => ['order' => 'asc']]];
			endif;
			return $this->setOutput($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getEmployeeDates($object) {
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type'] 	= 	$this->type;
			$params['size']  	= 	1000;
			$params['scroll']	=	'30s';
			$object->type		=	$object->type??"";
			$params['_source']	=	['employee_id', 'date_of_birth', 'is_birth_day_visible', 'date_of_advancement', 'advancements', 'date_of_joining'];

			$params['body']['query']['function_score']['query']['bool']['must'] 	= 	[['term' => ['enabled' => (int)1]]];

			if (!empty($object->range) && $object->type!='joinees'):
				$ranges	=	array_map('intval', explode(',', $object->range));
				if (!empty($ranges)):
					$rangeConditions	=	['bool' => ['should' => []]];
					foreach ($ranges as $range):
						if ($range==1):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d'), 'gte' => (string)date('Y-m-d', strtotime('-6 month')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==2):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-6 month')), 'gte' => (string)date('Y-m-d', strtotime('-1 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==3):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-1 year')), 'gte' => (string)date('Y-m-d', strtotime('-2 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==4):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-2 year')), 'gte' => (string)date('Y-m-d', strtotime('-5 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==5):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-5 year')), 'gte' => (string)date('Y-m-d', strtotime('-10 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==6):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-10 year')), 'gte' => (string)date('Y-m-d', strtotime('-15 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==7):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-15 year')), 'gte' => (string)date('Y-m-d', strtotime('-20 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
						if ($range==8):
							$condition	=	['range'=>	[ 'date_of_joining' => [ 'lte' => (string)date('Y-m-d', strtotime('-20 year')),"format"=> "yyyy-MM-dd"]]];
							array_push($rangeConditions['bool']['should'], $condition);
						endif;
					endforeach;
					array_push($params['body']['query']['function_score']['query']['bool']['must'], $rangeConditions);
				endif;
			endif;

			if (!empty($object->geography_id)):
				array_push($params['body']['query']['function_score']['query']['bool']['must'], ['terms' => ['geography_id' => array_map('intval', explode(',', $object->geography_id))]]);
			endif;
			if (!empty($object->location_id)):
				array_push($params['body']['query']['function_score']['query']['bool']['must'], ['terms' => ['location_id' => array_map('intval', explode(',', $object->location_id))]]);
			endif;
			if (!empty($object->function_id)):
				array_push($params['body']['query']['function_score']['query']['bool']['must'], ['terms' => ['function_id' => array_map('intval', explode(',', $object->function_id))]]);
			endif;
			if (!empty($object->level_id)):
				array_push($params['body']['query']['function_score']['query']['bool']['must'], ['terms' => ['level_id' => array_map('intval', explode(',', $object->level_id))]]);
			endif;
			if (!empty($object->layer_id)):
				array_push($params['body']['query']['function_score']['query']['bool']['must'], ['terms' => ['layer_id' => array_map('intval', explode(',', $object->layer_id))]]);
			endif;

			$objDates	=	(object)['birthday' => [], 'anniversary' => [], 'joinees' => [], 'advancements' => []];
			$results	=	$this->es_search($params);

			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				foreach ($results['hits']['hits'] as $result):
					$row	=	(object)$result['_source'];
					if ($row->is_birth_day_visible==1 && $row->date_of_birth!=''):
						$date	=	'2016-'.date('m-d', strtotime($row->date_of_birth));
						array_push($objDates->birthday, ['date' => date('m-d', strtotime($row->date_of_birth)), 'sorter' => strtotime($date)]);
					endif;
					if ($row->date_of_joining!=''):
						$date	=	'2016-'.date('m-d', strtotime($row->date_of_joining));
						array_push($objDates->anniversary, ['date' => date('m-d', strtotime($row->date_of_joining)), 'sorter' => strtotime($date)]);
						if(strtotime($row->date_of_joining) >= strtotime('-'._NEW_JOINEE_DAYS.' days')):
							array_push($objDates->joinees, ['date' => date('m-d', strtotime($row->date_of_joining)), 'sorter' => strtotime($date)]);
						endif;
					endif;
					if ($row->advancements && $row->date_of_advancement!=''):
						$date	=	'2016-'.date('m-d', strtotime($row->date_of_joining));
						array_push($objDates->advancements, ['date' => date('m-d', strtotime($row->date_of_joining)), 'sorter' => strtotime($date)]);
					endif;
				endforeach;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}

			function cmp($a, $b){
				return strcmp($a['sorter'], $b['sorter']);
			}
			usort($objDates->birthday, "cmp");
			usort($objDates->anniversary, "cmp");
			usort($objDates->joinees, "cmp");
			usort($objDates->advancements, "cmp");

			$objDates->birthday		=	array_values(array_unique(array_column($objDates->birthday, 'date')));
			$objDates->anniversary	=	array_values(array_unique(array_column($objDates->anniversary, 'date')));
			$objDates->joinees		=	array_values(array_unique(array_column($objDates->joinees, 'date')));
			$objDates->advancements	=	array_values(array_unique(array_column($objDates->advancements, 'date')));

			return $objDates;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	// Microlander
	public function getEmployeeProfiles($object){
		try{
			$params = [];
			$params['from']  	= 	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;

			$params['body']['query']['bool']['must']['match_all']	=	new stdClass();
			$params['body']['sort']	=	[['employee_id' => ['order'	=>	'asc']]];

			$results	=	$this->es_search($params);

			$rObject	=	(object)['count' => (int)0, 'ijps' => []];
			if ($results['hits']['total']>0):
				$rObject->count		=	(int)$results['hits']['total'];
				$rObject->employees	=	[];

				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $obj):
						if (!empty($obj['_source'])):
							$object	=	new stdClass();
							$row	=	(object)$obj['_source'];

							$object->employee_code		=	(string)$row->employee_code;
							$object->email				=	(string)$row->email;
							$object->display_name		=	(string)$this->common->entityDecode($row->display_name);
							$object->date_of_birth		=	(string)$row->date_of_birth;
							$object->description		=	(string)$this->common->entityDecode($row->description);
							$object->profile_picture	=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
																		'image_path'	=>	(string)$row->profile_picture];
							$object->social				=	(object)[	'linkedin_url' 	=> (string)$row->linkedin_url,
																		'twitter_url' 	=> (string)$row->twitter_url,
																		'website_url' 	=> (string)$row->website_url,
																		'open_id' 		=> (string)$row->open_id];
							$object->work_phone			=	(object)[	'isd_code'		=>	(string)(!empty($row->workphone_isd)?str_replace('+', '', $row->workphone_isd):''),
																		'number' 		=>	(string)ltrim($row->work_phone,'0'),
																		'extension'		=>	(string)$row->work_phone_extn];
							$object->mobile_number		=	(object)[	'isd_code'		=>	(string)(!empty($row->mobile_isd)?str_replace('+', '', $row->mobile_isd):''),
																		'number'		=>	(string)ltrim($row->mobile_number,'0'),
																		'is_mobile_number_visible'	=>	(boolean)($row->is_mobile_number_visible==1)?true:false
							];
							$object->emergency_contact	=	(object)[	'name'			=>	(string)$this->common->entityDecode($row->emergency_contact_name),
																		'phone'			=>	[	'isd_code'	=>	(string)(!empty($row->emergency_contact_phone_isd)?str_replace('+', '', $row->emergency_contact_phone_isd):''),
																								'number' 	=>	(string)ltrim($row->emergency_contact_phone,'0')],
																		'email'		=>	(string)$row->emergency_contact_email];

							array_push($rObject->employees, $object);
						endif;
					endforeach;
				endif;
			endif;
			return $rObject;

			return $this->setOutput($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);   }
	}

	//smart center
	public function getSmartCenterEmpFeeds(){
		try{
			$params = [];
			$params['from']  	= 	$object->start??0;
			$params['size']  	= 	$object->end??100;
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;
			$params['scroll']  	= 	'30s';
			$params['body']['query']['bool']['must']			= 	[['term' => ['enabled' 			=> (int)1]],['regexp' => [ 'mobile_number' => ".+"] ]];
			$params['body']['sort']	=	[['first_name' => ['order'	=>	'asc']], ['middle_name' => ['order' => 'asc']], ['last_name' => ['order' => 'asc']]];
			$results	=	$this->es_search($params);
			$rObjects	=	[];

			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						$row						=	(object)$object['_source'];
						$robject					=	new stdClass();
						$robject->employee_code		=	(string)$row->employee_code;
						$robject->first_name		=	(string)$this->common->entityDecode($row->first_name);
						$robject->middle_name		=	(string)$this->common->entityDecode($row->middle_name);
						$robject->last_name			=	(string)$this->common->entityDecode($row->last_name);
						$robject->email				=	(string)$row->email;
						$robject->mobile_isd		=	(string)$row->mobile_isd;
						$robject->mobile_number		=	(string)$row->mobile_number;
						array_push($rObjects,$robject);
					endforeach;
				endif;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return $rObjects;
		}
		catch(Exception $e)
		{	$this->es_error($e);   }
	}

	//Smartcenter
	public function getEmployeesByEmail($email_arr){
		try
		{
			$params = [];
			$params['from']  	= 	$object->start??0;
			$params['size']  	= 	$object->end??10000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']['should']		= 	['terms' => ['email' => array_values($email_arr)]];

			$objects	=	$this->es_search($params);
			$rObjects	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$object				=		(object)$object['_source'];
						$robject			=		$this->setEmployeeObject($object);
						array_push($rObjects,$robject);
					endif;
				endforeach;
			endif;
			return (!empty($rObjects)?$rObjects:[]);
		}
		catch(Exception $e)
		{  $this->es_error($e);   }
	}

	private function setEmployeeObject($object){
		try{
			$robject					=		new stdClass();
			$robject->employee_id		=		(int)$object->employee_id;
			$robject->employee_code		=		(string)$object->employee_code;
			$robject->email				=		(string)$object->email;
			$robject->first_name		=		(string)$this->common->entityDecode($object->first_name);
			$robject->middle_name		=		(string)$this->common->entityDecode($object->middle_name);
			$robject->last_name			=		(string)$this->common->entityDecode($object->last_name);
			$robject->display_name		=		(string)$this->common->entityDecode($object->display_name);
			$robject->position_title	=		(string)$this->common->entityDecode($object->position_title);
			$robject->profile_picture	=		(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
															'image_path'	=>	(string)$object->profile_picture];
			$robject->geography			=		(object)['geography_id'	=>(int)0, 'title'=>(string)'', 'abbreviation' => (string)'', 'isd_code' =>	(int)0];
			if (!empty($object->geography_id)):
				$ES_Geography			=		new ES_Geography();
				$objGeography			=		$ES_Geography->getById($object->geography_id);
				if (!empty($objGeography)):
					$robject->geography	=	(object)[	'geography_id'	=>	(int)$objGeography->geography_id,
														'title'			=>	(string)$objGeography->value,
														'abbreviation'	=>	(string)$objGeography->abbreviation,
														'isd_code'		=>	(int)$objGeography->isd_code,
														'currency_id'	=>	(int)$objGeography->currency_id];
				endif;
			endif;

			$robject->location	=	(object)['location_id'	=>	(int)0, 'title'	=> (string)''];
			if (!empty($object->location_id)):
				$ES_Location	=	new ES_Location();
				$objLocation	=	$ES_Location->getById($object->location_id);
				if (!empty($objLocation)):
					$robject->location	=	(object)[	'location_id'	=>	(int)$objLocation->location_id,
														'title'			=>	(string)$objLocation->value];
				endif;
			endif;

			$robject->function	=	(object)['function_id'	=>	(int)0, 'title'	=> (string)''];
			if (!empty($object->function_id)):
				$ES_Function	=	new ES_Function();
				$objFunction	=	$ES_Function->getById($object->function_id);
				if (!empty($objFunction)):
					$robject->function	=	(object)[	'function_id'	=>	(int)$objFunction->function_id,
														'title'			=>	(string)$objFunction->value];
				endif;
			endif;

			$robject->level	=	(object)['level_id'	=>	(int)0, 'title'	=> (string)''];
			if (!empty($object->level_id)):
				$ES_Level	=	new ES_Level();
				$objLevel	=	$ES_Level->getById($object->level_id);
				if (!empty($objLevel)):
					$robject->level		=	(object)[	'level_id'		=>	(int)$objLevel->level_id,
														'title'			=>	(string)$objLevel->name];
				endif;
			endif;

			$robject->layer	=	(object)['layer_id'	=>	(int)0, 'title'	=> (string)''];
				if (!empty($object->layer_id)):
				$ES_Layer	=	new ES_Layer();
				$objLayer	=	$ES_Layer->getById($object->layer_id);
				if (!empty($objLayer)):
					$robject->layer		=	(object)[	'layer_id'		=>	(int)$objLayer->layer_id,
														'title'			=>	(string)$objLayer->name];
				endif;
			endif;
			return $robject;
		}catch(Exception $e)
		{  $this->es_error($e);   }
	}

	public function getPledgeCount(){
	    try{
	        $params = [];
	        $params['index'] 	= 	$this->index;

	        $params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]],
	                                                           ['term' => ['is_pledged'=>(int)1]]]];

	        $result	=	$this->es_count($params);
	        return (!empty($result['count']))?$result['count']:0;
	    }
	    catch(Exception $e)
	    {	$this->es_error($e);	}
	}

	public function getCheerAwardableEmployees($object){
		try
		{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['from']  	= 	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['body']['query']['bool']['must']				=	[['term' => ['enabled' => 1]]];

			if(!empty($object->supervisor_email)):
				$reportees			= 	['term' => ['supervisor_email' => $object->supervisor_email]];
				array_push($params['body']['query']['bool']['must'], $reportees);
			endif;

			if(!empty($object->exclude)):
				$params['body']['query']['bool']['must_not']		=	['terms' => ['employee_id'	=>	$object->exclude ]];
			endif;

			if(!empty($object->date_doj)):
				$condition		=	['range'=>	[ 'date_of_joining' => [ 'gte' => (string)$object->date_doj,"format"=> "yyyy-MM-dd"]]];
				array_push($params['body']['query']['bool']['must'],$condition);
			endif;

			if(!empty($object->empcode_like)):
				$condition		=	['bool' =>  [ 'should' =>  [ ['wildcard' => ['employee_code'	=>	"INT-*"]],
														   	     ['wildcard' => ['employee_code'	=>	"T-*"]]]]];
				array_push($params['body']['query']['bool']['must'],$condition);
			endif;

			if(!empty($object->departments)):
					$condition		=	['terms'=>	[ 'department_id' => $object->departments]];
					array_push($params['body']['query']['bool']['must'],$condition);
			endif;

			if (!empty($object->query)):
				$query	=	['query_string' => ['fields'		=>	['full_name^3', 'first_name^2', 'middle_name', 'last_name'],
												'query'			=> (string)trim($object->query).'*',
												'phrase_slop'	=>	1,
												'type'			=>	'best_fields']];
				array_push($params['body']['query']['bool']['must'], $query);
			endif;

			$params['body']['sort']	=	[['first_name' => ['order'	=>	'asc']], ['middle_name' => ['order' => 'asc']], ['last_name' => ['order' => 'asc']]];

			$objects	=	$this->es_search($params);
			$rObjects	=	[];
			if ($objects['hits']['total']>0):
				foreach ($objects['hits']['hits'] as $object):
					if (!empty($object['_source'])):
						$object				=		(object)$object['_source'];
						$robject			=		$this->setEmployeeObject($object);
						array_push($rObjects,$robject);
					endif;
				endforeach;
			endif;
			return (!empty($rObjects)?$rObjects:[]);
		}
		catch(Exception $e)
		{ $this->es_error($e);   }
	}


	public function getEmployeesByDeptIds($ids){
		try{
			$params = [];
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['scroll']  	= 	'30s';
			$params['body']['query']['bool']['should']		= 	['terms' => ['department_id' => array_values($ids)]];

			$results	=	$this->es_search($params);
			$rObjects	=	[];
			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						if (!empty($object['_source'])):
							$object				=		(object)$object['_source'];
							$robject			=		$this->setEmployeeObject($object);
							array_push($rObjects,$robject);
						endif;
					endforeach;
				endif;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return $rObjects;
		}
		catch(Exception $e)
		{ $this->es_error($e);   }
	}

	public function getReportees($object){
		try{
			$params = [];
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['scroll']  	= 	'30s';
			$params['body']['query']['bool']['must']		= 	['term' => ['supervisor_email' =>$object->supervisor_email ]];

			$results	=	$this->es_search($params);
			$rObjects	=	[];

			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
				if ($results['hits']['total']>0):
					foreach ($results['hits']['hits'] as $object):
						if (!empty($object['_source'])):
							$object				=		(object)$object['_source'];
							$robject			=		$this->setEmployeeObject($object);
							array_push($rObjects,$robject);
						endif;
					endforeach;
				endif;
				$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
			}
			return $rObjects;
		}
		catch(Exception $e)
		{ $this->es_error($e);   }
	}
}
?>
