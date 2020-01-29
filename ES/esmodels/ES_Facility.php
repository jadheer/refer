<?php
class ES_Facility  extends ESSource
{
	var $index;
	var $type;
	public function __construct()
	{
		parent::__construct();
		$this->index	=	'facilities';
		$this->type		=	'facility';
	}
	
	public function __destruct()
	{	parent::__destruct();	}
	
	public function getFacilityById($object){
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$object->facility_id;
				
			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				$row		=	(object)$result['_source'];
				$robject	=	new stdClass();
			
				$robject->facility_id 	=	(int)$row->facility_id;
				$robject->title 		=	(string)$this->common->entityDecode($row->title);
				$robject->address		=	(string)$this->common->entityDecode($row->address);
				$robject->link 			=	(string)$row->url;
				$robject->phone_number	=	(string)$row->phone_number;
				$robject->phone_isd		=	(string)$row->phone_isd;
				$robject->phone_extn	=	(string)$row->phone_extn;
				$robject->promo_image 	=	(object)[	'base_url'		=>	(string)_AWS_URL._FACILITIES_IMAGES_DIR,
														'image_path'	=>	(string)$row->promo_image];
				
				$pictures	=	[];
				if (!empty($row->gallery)):
					foreach ($row->gallery as $pic):
						$pObj	=	new stdClass();
						$pObj->picture_path		=	(string)$pic['picture_path'];
						$pObj->picture_caption	=	(string)$this->common->entityDecode($pic['picture_caption']);
						array_push($pictures, $pObj);
					endforeach;
				endif;
				$robject->gallary	=	(object)[	'base_url'	=>	(string)_AWS_URL._FACILITIES_IMAGES_DIR,
													'pictures'	=>	$pictures];
				
				$ES_Geography	=	new ES_Geography();
				$ObjGeos		=	$ES_Geography->getById($row->geography_id);
				$robject->geography	=	new stdClass();
				if (!empty($ObjGeos)):
					$robject->geography	= (object)['geography_id' => (int)$ObjGeos->geography_id, 'title' => (string)$ObjGeos->value];
				endif;
				
				$ES_Location	=	new ES_Location();
				$Objlocs		=	$ES_Location->getById($row->location_id);
				$robject->location	=	new stdClass();
				if (!empty($Objlocs)):
					$robject->location	= (object)['location_id' => (int)$Objlocs->location_id, 'title' => (string)$Objlocs->value];
				endif;
					
				$robject->likes	= (object)[	'count'		=>	(int)count($row->likes),
											'is_liked'	=>	(boolean)in_array($object->employee_id,$row->likes)];

				$robject->work_phone = (object)['isd_code' => (string)(!empty($row->phone_isd)?str_replace('+', '', $row->phone_isd):''),
												'number' => (string)ltrim($row->phone_number,'0'),
												'extension' => (string)$row->phone_extn];
			endif;
			return !empty($robject)?$robject:new stdClass();
		}
		catch(Exception $e)
		{  $this->es_error($e);	}
	}
	
	public function getFacilities($object, $objects, $geos){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>1]],
																['term' => ['published'=>1]]]];
				
			$params['body']['sort']	= [['geography_id' => ['order' => 'asc']], ['sorting_text' => ['order' => 'asc']], ['facility_id' => ['order' => 'asc']]];
			return $this->setOutput($this->es_search($params),$object, $objects, $geos);
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results, $object, $objects, $geos){
		try{
			if ($results['hits']['total']>0):
				$ES_Location	=	new ES_Location();
				$Objlocs		=	$ES_Location->getLocations();
				$ObjMaster		=	(object)['geographies' => $geos, 'locations' => $Objlocs];
				
				foreach ($results['hits']['hits'] as $res):
					if (!empty($res['_source'])):
						$row	=	(object)$res['_source'];
						$facility	=	$this->setObject($row, $object, $ObjMaster);
						array_push($objects[$row->geography_id], $facility);
					endif;
				endforeach;
			endif;
			return $objects;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setObject($row, $object, $ObjMaster){
		try{
			$robject	=	new stdClass();
			$robject->facility_id 	=	(int)$row->facility_id;
			$robject->title 		=	(string)$this->common->entityDecode($row->title);
			$robject->address		=	(string)$this->common->entityDecode($row->address);
			$robject->link 			=	(string)$row->url;
			$robject->phone_number	=	(string)$row->phone_number;
			$robject->phone_isd		=	(string)$row->phone_isd;
			$robject->phone_extn		=	(string)$row->phone_extn;
			$robject->promo_image 	=	(object)[	'base_url'		=>	(string)_AWS_URL._FACILITIES_IMAGES_DIR,
													'image_path'	=>	(string)$row->promo_image];
				
			$pictures	=	[];
			if (!empty($row->gallery)):
				foreach ($row->gallery as $pic):
					$pObj	=	new stdClass();
					$pObj->picture_path		=	(string)$pic['picture_path'];
					$pObj->picture_caption	=	(string)$this->common->entityDecode($pic['picture_caption']);
					array_push($pictures, $pObj);
				endforeach;
			endif;
			$robject->gallary	=	(object)[	'base_url'	=>	(string)_AWS_URL._FACILITIES_IMAGES_DIR,
	  											'pictures'	=>	$pictures];
			
			$robject->geography	=	new stdClass();
			$Key	=	array_search($row->geography_id, array_column($ObjMaster->geographies, 'geography_id'));
			if ($Key!==false):
				$robject->geography	= (object)['geography_id' => (int)$ObjMaster->geographies[$Key]->geography_id,
												'title' => (string)$ObjMaster->geographies[$Key]->title];
			endif;
			
			$robject->location	=	new stdClass();
			$Key	=	array_search($row->location_id, array_column($ObjMaster->locations, 'location_id'));
			if ($Key!==false):
				$robject->location	= (object)['location_id' => (int)$ObjMaster->locations[$Key]->location_id,
												'title' => (string)$ObjMaster->locations[$Key]->title];
			endif;
			
			$robject->likes	= (object)[	'count'		=>	(int)count($row->likes),
										'is_liked'	=>	(boolean)in_array($object->employee_id,$row->likes)];
			
			$robject->work_phone	=	(object)[   'isd_code'		=>	(string)(!empty($row->phone_isd)?str_replace('+', '', $row->phone_isd):''),
													'number' 		=>	(string)ltrim($row->phone_number,'0'),
													'extension'		=>	(string)$row->phone_extn];
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