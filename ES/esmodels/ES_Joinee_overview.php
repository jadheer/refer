<?php
class ES_Joinee_overview extends ESSource {
	var $index;
	var $type;
	public function __construct() {
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
	
	public function __destruct()
	{	parent::__destruct();	}
	
	public function getOverviews(){
		try{
			$params = [];
			$params['from']  	= 	0;
			$params['size']  	= 	1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']	=	['must' => [['term' => ['enabled'=>(int)1]],
																['term' => ['published'=>(int)1]]]];
			$params['body']['sort']	=	[['overview_order' => ['order' => 'asc']], ['overview_id' => ['order' => 'asc']]];
			return $this->setOutput($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results){
		$arr 		=	[];
		if ($results['hits']['total']>0):
			foreach ($results['hits']['hits'] as $view_obj):
				if (!empty($view_obj['_source'])):
					$row					=	(object)$view_obj['_source'];
					$robject				=	new stdClass();
					$robject->overview_id 	=	(int)$row->overview_id;
					$robject->title 		=	(string)$this->common->entityDecode($row->title);
					$robject->youtube_url 	=	(string)$row->youtube_url;
					$robject->overview_order=	(int)$row->overview_order;
					$robject->promo_image 	=	[	'base_url'		=>	(string)_AWS_URL._JOINEES_IMAGES_DIR,
													'image_path'	=>	(string)$row->promo_image];
					$robject->media				=	(object)[ 	"pdf" =>		$row->pdf_links];								

					array_push($arr, $robject);
				endif;
			endforeach;
		endif;
		return $arr;
	}
}
?>