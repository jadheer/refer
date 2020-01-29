<?php 
class ES_ChatSession  extends ESSource {
	var $index;
	var $type;
	public function __construct(){	
		parent::__construct();
		$this->index	=	str_replace('es_', '', strtolower(get_class($this))).'s';
		$this->type		=	str_replace('es_', '', strtolower(get_class($this)));
	}
    public function __destruct()
	{	parent::__destruct();	}

	public function getChatSessionById($sessionId) {
		try{
			$params = [];
			$params['index'] 	= 	$this->index;
			$params['type']		=	$this->type;
			$params['id']		=	(int)$sessionId;
	
			$result	=	$this->es_get($params);
			if (!empty($result['_source'])):
				return $this->setObject((object)$result['_source']);
			endif;
			return [];
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getLatestChatSession(){
		try{
			$params = [];
			$params['from']		=	(int)0;
			$params['size']  	= 	(int)1;
			$params['index'] 	= 	$this->index;
			$params['body']['query']['bool']	=	[	'must' => [	['term' => ['enabled'=>1]],
																	['range'=>	[ 'start_datetime' => [ 'lte' => (string)date('Y-m-d H:i:s') ,"format"=> "yyyy-MM-dd HH:mm:ss"]]]]];
			$params['body']['sort']	=	[['start_datetime' => ['order'	=>	'desc']], ['session_id' => ['order'	=>	'desc']]];
			$results	=	$this->es_search($params);
			$robject	=	new stdClass();
			if ($results['hits']['total']>0):
				if (isset($results['hits']['hits'][0]['_source'])):
					$robject	=	$this->setObject((object)$results['hits']['hits'][0]['_source']);
				endif;
			endif;
			return $robject;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	public function getChatSessions($object){
		try{
			$params = [];
			$params['from']		=	$object->start??0;
			$params['size']  	= 	$object->end??1000;
			$params['index'] 	= 	$this->index;
			
			$params['body']['query']['bool']['must'] = [['term' => ['enabled'=>1]]];
			$params['body']['query']['bool']['must_not'] = [['term' => ['session_id'=>$object->omit]]];
			
			$params['body']['sort']	=	[['start_datetime' => ['order'	=>	'desc']]];
			return $this->setOutput($this->es_search($params));
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setOutput($results){
		try{
			$sessionIds	=	[];
			$objects	=	[];
			if ($results['hits']['total']>0):
				/*get all members from community ids */
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						array_push($sessionIds, $obj['_source']['session_id']);
					endif;
				endforeach;
			
				$ES_ChatQuestion	=	new ES_ChatQuestion();
				$ObjQuestions		=	$ES_ChatQuestion->getQuestionCounts($sessionIds);
				foreach ($results['hits']['hits'] as $obj):
					if (!empty($obj['_source'])):
						$row		=	(object)$obj['_source'];
						$robject	=	new stdClass();
						$robject->online	    	= 	(boolean)false;
						$robject->session_id    	= 	(int)$row->session_id;
						$robject->session_title		= 	(string)$this->common->entityDecode($row->session_title);
						$robject->start_datetime	= 	(string)date('Y-m-d H:i:s', strtotime($row->start_datetime));
						$robject->end_datetime		= 	(string)date('Y-m-d H:i:s', strtotime($row->end_datetime));

						if (isset($ObjQuestions[$robject->session_id])):
							array_push($objects, $robject);
						endif;
					endif;
				endforeach;
			endif;
			return $objects;
		}
		catch(Exception $e)
		{	$this->es_error($e);	}
	}
	
	private function setObject($row){
		try{
			$robject	=	new stdClass();
				
			$robject->online	=	false;
			$robject->is_cmd	=	false;
			if (strtotime($row->start_datetime)<=time() && strtotime($row->end_datetime)>=time()):
				$robject->online	=	true;
			endif;
			$robject->session_id    = 	(int)$row->session_id;
			$robject->session_title	= 	(string)$this->common->entityDecode($row->session_title);
			$robject->start_datetime	= 	(string)date('Y-m-d H:i:s', strtotime($row->start_datetime));
			$robject->end_datetime		= 	(string)date('Y-m-d H:i:s', strtotime($row->end_datetime));
			$robject->questions		=	[];
				
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