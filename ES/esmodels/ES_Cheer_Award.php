<?php
class ES_Cheer_Award  extends ESSource {
	var $index;
	var $type;
	public function __construct(){
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}

    public function __destruct()
	{	parent::__destruct();}


	public function getAwardsByAwardKey($objIcons,$obj,$master_obj){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;

			$params['body']['query']['bool']['must']	=	[['term' =>	['enabled' => (int)1]], ['terms' =>	['award_key' => $obj->nominate_arr]] ];
			$params['body']['sort']						= 	['award_id' => ['order' => 'asc']];

			$results	=	$this->es_search($params);
			$rObjects	=	[];

			if(!empty($objIcons)):
				$objIcons	=	array_column($objIcons, NULL ,"icon_id");
			endif;

			if ($results['hits']['total']>0):
				foreach ($results['hits']['hits'] as $object):
					$row							=		(object)$object['_source'];
					$robject						=		new stdClass();
					$robject->description			= 		(string)$this->common->entityDecode($row->description);
					$citations						=		[];
					if(!empty($row->citations)):
						foreach ($row->citations as $key => $citation):
							$citation					=   	(object)$citation;
							$cit						=		new stdClass();
							$cit->citation_id			=		(int)$citation->citation_id;
							$cit->text					=		(string)$this->common->entityDecode($citation->text);
							array_push($citations, $cit);
						endforeach;
					endif;
					$robject->citations				= 		$citations;
					$robject->award_id				= 		(int)$row->award_id;
					$robject->award_key				= 		(string)$row->award_key;
					$robject->award_type			= 		(string)$master_obj->award_types[$row->award_type];
					$robject->award_frequency		= 		(string)$master_obj->cheer_awards[$row->award_key];
					$robject->title					= 		(string)$this->common->entityDecode($row->title);
					$robject->icon_name				=		(string)isset($objIcons[$row->icon_map_id])?$objIcons[$row->icon_map_id]->title : "";
					$robject->points				=		(int)isset($master_obj->award_points_map[$row->award_key])?$master_obj->award_points_map[$row->award_key]:0;
					$robject->award_individual		=		(boolean)true;
					$robject->award_team			=		(boolean)false;
					$robject->additional_points		=		(boolean)($row->award_type == "NM")?false:true;
					if($obj->type == "myteam" || $obj->type == "peer"):
						$robject->award_team			=		(boolean)in_array($row->award_key,$master_obj->award_team_receiver)?true:false;
						$robject->award_individual		=		(boolean)in_array($row->award_key,$master_obj->award_individual_receiver)?true:false;
					endif;
					if($obj->type == "self" || $robject->award_key == "_CEO_CHOICE"):
						$robject->additional_points		=		(boolean)false;
					endif;
					array_push($rObjects, $robject);
				endforeach;
			endif;
			return $rObjects;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getAwards($master_obj,$certificate=true){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;

			$params['body']['query']['bool']['must']	=	[['term' =>	['enabled' => (int)1]]];
			$params['body']['sort']						= 	['award_id' => ['order' => 'asc']];

			$results	=	$this->es_search($params);
			$rObjects	=	[];

			if ($results['hits']['total']>0):
				$es_icon				=		new ES_Icon();
				$objIcons				=		$es_icon->getIcons();
				if(!empty($objIcons)):
					$objIcons	=	array_column($objIcons, NULL ,"icon_id");
				endif;

				foreach ($results['hits']['hits'] as $object):
					$row						=		(object)$object['_source'];
					$robject					=		new stdClass();
					$robject->description			= 		(string)$this->common->entityDecode($row->description);
					$robject->award_id				= 		(int)$row->award_id;
					$robject->title					= 		(string)$this->common->entityDecode($row->title);
					$robject->icon_name				=		(string)isset($objIcons[$row->icon_map_id])?(string)$this->common->entityDecode($objIcons[$row->icon_map_id]->title) : "";
					if($certificate):
						$robject->certificate			=		(object)[	'base_url'		=>	(string)_AWS_URL._CHEERS_IMAGES_DIR,
																			'image_path'	=>	(string)$row->certificate_image];
					endif;
					$robject->award_type			= 		(string)$master_obj->award_types[$row->award_type];
					$robject->award_frequency		= 		(string)!empty($master_obj->cheer_awards[$row->award_key])?$master_obj->cheer_awards[$row->award_key]:"Adhoc";
					array_push($rObjects,$robject);
				endforeach;
			endif;
			return $rObjects;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}

	public function getCitationsByIds($arr){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['type']  	= 	$this->type;

			$params['body']['query']['bool']['filter']				= 	['nested'	=>	[	'path' => 'citations',
															   								'query'	=>  ['bool' => ['must' => ['terms' => ['citations.citation_id' => array_values($arr) ]]]],
																							'inner_hits'	=>	['from'	=>	(int)0 , 'size'	=>	(int)50]
															 			]];

			$results	=	$this->es_search($params);
			$rObjects	=	[];
			foreach($results['hits']['hits'] as $key => $result):
				if (isset($result['inner_hits']['citations']['hits']['hits'])):
					if ($result['inner_hits']['citations']['hits']['total']>0):
						$docs	=	$result['inner_hits']['citations']['hits']['hits'];
						foreach ($docs as $obj):
							$row	=	(object)$obj['_source'];
							$rObjects[$row->citation_id] = (string)$this->common->entityDecode($row->text);
						endforeach;
					endif;
				endif;
			endforeach;
			return $rObjects;
		}
		catch(Exception $e)
		{ $this->es_error($e);	}
	}

	public function getAwardIdByKey($award_key){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1;
			$params['index'] 	= 	$this->index;

			$params['body']['query']['bool']['must']	=	[['term' =>	['award_key' => $award_key ]] ];
			$result		=	$this->es_search($params);
			$robject	=		new stdClass();
			if ($result['hits']['total']>0):
				$row							=		(object)$result['hits']['hits'][0]['_source'];
				$robject->award_id				= 		(int)$row->award_id;
				$robject->award_key				= 		(string)$row->award_key;
				$robject->award_type			= 		(string)$row->award_type;
			endif;
			return (!empty($robject->award_id)?$robject:0);
		}
		catch(Exception $e)
		{ 	$this->es_error($e);	}
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
