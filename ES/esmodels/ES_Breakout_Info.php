<?php
class ES_Breakout_Info  extends ESSource {
	var $index;
	var $type;
	public function __construct(){
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
	public function __destruct()
	{	parent::__destruct();	}
	
	
	/* for all breakout information */
	public function getInfo($object){
		try{
			$params 			= 	[];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
			
			
			$params['body']['query']['bool']['must'] 		= 	[['term' => ['enabled'=>1]], ['term' => ['breakout_id'=> (int)$object->breakout_id]] ];
			$params['body']['sort']							=	['sort_order'=>['order'=> 'asc']];
			
			$results	=	$this->es_search($params);
			$arr		=	[];
			foreach ($results['hits']['hits'] as $result):
				$row		=	(object)$result['_source'];
				array_push($arr,$this->setObject($row,$object));
			endforeach;
			return $arr;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setObject($row,$aobject){
		try{
			$robject	=	new stdClass();
			$robject->info_id    			= 	(int)$row->info_id;
			$robject->breakout_id    		= 	(int)$row->breakout_id;
			$robject->sort_order			= 	(int)$row->sort_order;
			$robject->title					= 	(string)$this->common->entityDecode($row->title);
			$robject->description			= 	(string)$this->common->entityDecode($row->description);
			$robject->created_on			= 	(string)$row->created_on;
			$robject->updated_on			= 	(string)$row->updated_on;
			//media object
			$robject->media					=	(object)[ 	"pdf" =>		$row->pdf_links,
															"text" =>		$row->text_links,
															"youtube" => 	$row->youtube_links
												];
			
			
			$pictures	=	[];
			if (!empty($row->gallery)):
				foreach ($row->gallery as $pic):
					$pObj	=	new stdClass();
					$pObj->picture_path		=	(string)$pic['picture_path'];
					$pObj->picture_caption	=	(string)$pic['picture_caption'];
					array_push($pictures, $pObj);
				endforeach;
			endif;
			$robject->gallery	=	(object)[	'base_url' => (string)_AWS_URL._BREAKOUTS_IMAGES_DIR,
												'pictures' => $pictures];
			
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