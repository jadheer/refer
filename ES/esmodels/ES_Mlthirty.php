<?php
class ES_Mlthirty extends ESSource
{
  var $index;
  var $type;
  public function __construct()
  {
    parent::__construct();
    $this->index  = str_replace('es_', '', strtolower(get_class($this))).'s';
    $this->type   = str_replace('es_', '', strtolower(get_class($this)));
  }

  public function __destruct()
  { parent::__destruct(); }

  public function getEvents($obj) {
    try {
      $rObject = [];
      $rObject['contests'] = $this->getContests($obj);
      $rObject['articles'] = $this->getArticles($obj);
      return $rObject;
    }
    catch(Exception $e)
    { $this->es_error($e);  }
  }

  public function getArticles($object){
    try{
      $params = [];
      $params['from']   = 0;
      $params['size']   = 1000;
      $params['index']  =   'articles';

      $params['body']['query']['bool']  = ['must' => [['term' => ['enabled'=>(int)1]],
                                ['term' => ['published'=>(int)1]],
                                ['term' => ['publish_ml30'=>(int)1]],
                                ['range'=> [ 'pub_datetime' => [ 'lte' => (string)date('Y-m-d H:i:s') ,"format"=> "yyyy-MM-dd HH:mm:ss"]]]]];
      if (!empty($object->query)):
        $query  = ['multi_match'  =>  ['query' => (string)trim($object->query), 'fields' => ['title', 'body']]];
        array_push($params['body']['query']['bool']['must'], $query);
      endif;
      $params['body']['sort'] = [['pub_datetime' => ['order' => 'desc']], ['article_id' => ['order' => 'desc']]];

      $results = $this->es_search($params);

      $arr   = [];
      if ($results['hits']['total']>0):
        foreach ($results['hits']['hits'] as $obj):
          if (!empty($obj['_source'])):
            $row  = (object)$obj['_source'];
            $robject = new stdClass();
            $robject->article_id      = (int)$row->article_id;
            $robject->pub_datetime    = (string)$row->pub_datetime;
            $robject->title       = (string)$this->common->entityDecode($row->title);
            $robject->image       = [ 'base_url'    =>  (string)_AWS_URL._ARTICLES_IMAGES_DIR,
                              'image_path'  =>  (string)$row->image];
            $robject->body        = (string)$this->common->entityDecode($row->body);
            $robject->enable_like     = (boolean)(!empty($row->enable_like)?true:false);
            $robject->enable_comment  = (boolean)(!empty($row->enable_comment)?true:false);
            $robject->comment_count   = (int)count($row->comments);
            $robject->like_count    = (int)count($row->likes);
            $robject->liked       = (boolean)(in_array($object->employee_id,$row->likes));
            array_push($arr, $robject);
          endif;
        endforeach;
      endif;
      return $arr;
    }
    catch(Exception $e)
    { $this->es_error($e);  }
  }

  public function getContests($object){
    try {
      $params = [];
      $params['from']   = $object->start??0;
      $params['size']   =   $object->end??1000;
      $params['index']  =   'mlthirty_contests';

      $params['body']['query']['bool']  = ['must' => ['term' => ['enabled'=>(int)1]]];

      $results = $this->es_search($params);
      $arr    = [];
      if ($results['hits']['total']>0):
        foreach ($results['hits']['hits'] as $obj):
          if (!empty($obj['_source'])):
            $row  = (object)$obj['_source'];
            $robject = new stdClass();
            $robject->contest_id = (int)$row->contest_id;
            $robject->title = (string)$row->title;
            $robject->hash_tag = (string)$row->hash_tag;
            $robject->description = (string)$row->description;
            $robject->upcoming_challenge_date = '';
            $robject->current_challenge_end_date = '';
            $robject->participate_now = false;
            $object->start = 0;
            $object->end = 1;
            $object->contest_id = $row->contest_id;
            $object->current = true;
            $object->upcoming = false;
            $objChallenge = (!empty($this->getChallenges($object)[0])?$this->getChallenges($object)[0]:'');
            if (!empty($objChallenge)) {
              $robject->current_challenge_end_date = $objChallenge->end_date;
              $robject->participate_now = true;
            }
            $object->upcoming = true;
            $object->current = false;
            $objChallenge = (!empty($this->getChallenges($object)[0])?$this->getChallenges($object)[0]:'');
            if (!empty($objChallenge)) {
              $robject->upcoming_challenge_date = $objChallenge->start_date;
            }
            array_push($arr, $robject);
          endif;
        endforeach;
      endif;
      return $arr;
    }
    catch(Exception $e)
    { $this->es_error($e);  }
  }

  public function getChallenges($object){
    try {
      $params = [];
      $params['from']   = $object->start??0;
      $params['size']   =   $object->end??1000;
      $params['index']  =   'mlthirty_challenges';

      $params['body']['query']['bool']  = ['must' => [['term' => ['enabled'=>(int)1]]]];

      if (!empty($object->contest_id)):
        array_push($params['body']['query']['bool']['must'], ['term' =>  ['contest_id'  => (int)$object->contest_id]]);
      endif;

      if (!empty($object->curr_date)):
        $condition  = ['range'=>  [ 'end_date' => [ 'gte' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]];
        array_push($params['body']['query']['bool']['must'], $condition);
      endif;

      if (!empty($object->current)):
        $condition  = ['range'=>  [ 'start_date' => [ 'lte' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]];
        array_push($params['body']['query']['bool']['must'], $condition);
        $condition  = ['range'=>  [ 'end_date' => [ 'gte' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]];
        array_push($params['body']['query']['bool']['must'], $condition);
      endif;

      if (!empty($object->upcoming)):
        $condition  = ['range'=>  [ 'start_date' => [ 'gt' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]];
        array_push($params['body']['query']['bool']['must'], $condition);
      endif;

      $params['body']['sort'] = [['start_date' => ['order'  =>  'asc']]];

      $results = $this->es_search($params);
      $arr    = [];$id_arr	=	[];$obj_posts = [];$new_arr	=	[];
      if ($results['hits']['total']>0):
	      foreach ($results['hits']['hits'] as $obj):
		      if (!empty($obj['_source'])):
		      	$row  		= 	(object)$obj['_source'];
		      	if($row->num_posts_allowed == "S"):
		      		array_push($id_arr, (int)$row->mlthirty_challenge_id);
		      	endif;
		      	array_push($arr,$row);
		      endif;
	      endforeach;

	      //check if employee has posted or not
	      if(!empty($id_arr)):
	      		$es_post 		=		new ES_MlThirty_post();
	      		$obj			=		new stdClass();
	      		$obj->ids		=		$id_arr;
	      		$obj->employee_id	=	$object->employee_id;
	      		$obj_posts		=		$es_post->checkEmployeePosted($obj);
	      endif;

	      foreach ($arr as $row):
            $robject	=	$this->setChallengeOutput($row,$obj_posts);
            array_push($new_arr, $robject);
         endforeach;
        return $new_arr;
      endif;
    }
    catch(Exception $e)
    { $this->es_error($e);  }
  }


  public function getChallengeById($object){
  	try {
  		$params 			= 	[];
  		$params['index']  	=   'mlthirty_challenges';
  		$params['type']  	=   'mlthirty_challenge';
  		$params['id']  		= 	(int)$object->mlthirty_challenge_id;
  		$objects				=	$this->es_get($params);
  		$obj_posts			=	[];
  		if (!empty($objects['_source'])):
  			$row  		= 	(object)$objects['_source'];
  			if($row->num_posts_allowed == "S"):
  				$obj				=		new stdClass();
  				$obj->ids			=		[$object->mlthirty_challenge_id];
	  			$obj->employee_id	=		$object->employee_id;
	  			$es_post 			=		new ES_MlThirty_post();
	  			$obj_posts			=		$es_post->checkEmployeePosted($obj);
  			endif;
  			return (object)$this->setChallengeOutput($row,$obj_posts);
  		endif;
  		return [];
  	}
  	catch(Exception $e)
  	{ $this->es_error($e);  }
  }

  private function setChallengeOutput($row,$post_obj){
  	try{
  		$robject = new stdClass();
  		$robject->mlthirty_challenge_id 		= 	(int)$row->mlthirty_challenge_id;
  		$robject->contest_id 					= 	(int)$row->contest_id;
  		$robject->title 						= 	(string)$row->title;
  		$robject->description 					= 	(string)$row->description;
  		$robject->start_date 					= 	(string)$row->start_date;
  		$robject->end_date 						= 	(string)$row->end_date;
  		$robject->is_image_upload 				= 	(int)$row->is_image_upload;
  		$robject->is_employee_tagging 			= 	(int)$row->is_employee_tagging;
  		$robject->no_employee_tag 				= 	(int)$row->no_employee_tag;
  		$robject->default_point 				= 	(int)$row->default_point;
  		$robject->created_on 					= 	(string)$row->created_on;
  		$robject->updated_on 					= 	(string)$row->updated_on;
      $robject->completed = false;

      if ($row->num_posts_allowed == 'S' && isset($post_obj[$row->mlthirty_challenge_id])) {
    		$robject->completed 					= 	true;
      }
  		$robject->enable_post					=	(boolean)(isset($post_obj[$row->mlthirty_challenge_id])?false:true);
      $robject->is_active = (boolean)((($row->start_date<=(string)date('Y-m-d')) && ($row->end_date>=(string)date('Y-m-d')))?true:false);
  		return $robject;
  	}
  	catch(Exception $e)
  	{	$this->es_error($e);	}
  }

}
?>
