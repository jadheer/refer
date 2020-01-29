<?php 
class ES_Downloadable  extends ESSource {
	var $index;
	var $type;
	public function __construct(){	
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}

    public function __destruct()
	{	parent::__destruct();	}

	public function getDownloadables() {
		try{
			$params = [];
			$params['from']		=	(int)0;
			$params['size']  	= 	(int)1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool'] = ['must' => [	['term' => ['published'=>1]],
															['range'=> [ 'start_date' => ['lte' => (string)date('Y-m-d') , "format"=> "yyyy-MM-dd"]]],
															['range'=> [ 'end_date' => ['gte' => (string)date('Y-m-d') , "format"=> "yyyy-MM-dd"]]]]];
			$params['body']['sort']	= [['sorting_text' => ['order' => 'asc']], ['start_date' => ['order' => 'asc']], ['end_date' => ['order' => 'asc']]];
			
			return $this->setOutput($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results){
		$arr 		=	[];
		if ($results['hits']['total']>0):
			foreach ($results['hits']['hits'] as $article_obj):
				if (!empty($article_obj['_source'])):
					$row	=	(object)$article_obj['_source'];
					$obj	=	new stdClass();
					
					$obj->downloadable_id	=	(int)$row->downloadable_id;
					$obj->title				=	(string)$this->common->entityDecode($row->title);
					$obj->message			=	(string)$this->common->entityDecode($row->message);
					
					$pictures	=	[];
					if (!empty($row->gallery)):
						foreach ($row->gallery as $pic):
							$pObj 	=	new stdClass();
							$pObj->card_is		=	(string)$pic['picture_id'];
							$pObj->picture_path	=	(string)$pic['picture_path'];
							array_push($pictures, $pObj);
						endforeach;
					endif;
					$obj->gallary = (object)['base_url'	=>	(string)_AWS_URL._CARDS_IMAGES_DIR,
											'cards'		=>	$pictures];
				endif;
				array_push($arr, $obj);
			endforeach;
		endif;
		return $arr;
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