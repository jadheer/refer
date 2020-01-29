<?php
class ES_Microgive_Initiative extends ESSource
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

    public function getMicrogiveInitiatives($object) {
        try{
            $params = [];
            $params['from']   =   (int)$object->start??0;
            $params['size']   =   (int)$object->end??1000;
            $params['index']  =   $this->index;
            $params['body']['query']['bool']  = [ 'must' => [   ['term' => ['enabled'=>1]],
                                                                ['term' => ['published'=>1]]]];

            if(!empty($object->type)):
                array_push($params['body']['query']['bool']['must'], ['term' =>  ['type'  =>  (string)$object->type]]);
                if ($object->type=="V"):
                    $condition = ['nested' =>  [    'path'        => 'initiative_details',
                                                    'query'       =>  ['bool' => ['must' => [['range' => ['initiative_details.event_date' => [ 'gte' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]]]]]]];
                    array_push($params['body']['query']['bool']['must'],$condition);
                else:
                    $condition = ['nested' =>  [    'path'        => 'initiative_details',
                                                    'query'       =>  ['bool' => ['must' => [['range' => ['initiative_details.event_end_date' => [ 'gte' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]]]]]]];
                    array_push($params['body']['query']['bool']['must'],$condition);
                endif;
            else:
                $params['body']['query']['bool']['must']           =   [[  'bool'    =>     [ 'must' =>   [ 'nested'  =>    [    'path'   =>  'initiative_details',
                                                                                                                                'query'  =>   ['bool' => ['should' =>   [
                                                                                                                                                                            ['range' => ['initiative_details.event_end_date' => [ 'gte' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]],
                                                                                                                                                                            ['range' => ['initiative_details.event_date' => [ 'gte' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]]
                                                                                                                                                                        ]
                                                                                                                                                                    ]]]]]]];
            endif;


            if (!empty($object->category_id)):
                array_push($params['body']['query']['bool']['must'], ['term' =>  ['category_id'  =>  $object->category_id]]);
            endif;

            if (!empty($object->project_id)):
                array_push($params['body']['query']['bool']['must'], ['term' =>  ['project_id'  =>  $object->project_id]]);
            endif;

            if (!empty($object->event_date)):
                $condition = ['nested' =>  [    'path'        => 'initiative_details',
                                                'query'       =>  ['bool' => ['must' => [['term' => ['initiative_details.event_date' => (string)$object->event_date]]]]]]];
                array_push($params['body']['query']['bool']['must'], $condition);
            endif;

            if (!empty($object->event_end_date)):
                $condition = ['nested' =>  [    'path'        => 'initiative_details',
                                                'query'       =>  ['bool' => ['must' => [['term' => ['initiative_details.event_end_date' => (string)$object->event_end_date]]]]]]];
                array_push($params['body']['query']['bool']['must'], $condition);
            endif;

            if (!empty($object->query)):
                $query  = ['multi_match'   =>  ['query' => $object->query,'fields' => ['short_description','title']]];
                array_push($params['body']['query']['bool']['must'], $query);
            endif;
            return $this->setOutput($this->es_search($params),$object,true);
        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }

    /*--------------------------------------------- PAST INITIATIVE FUNCTIONS  -------------------------------------------------*/

    public function getMicrogivePastInitiativeDetail($object) {
        try{
            $params = [];
            $params['from']   =   (int)$object->start??0;
            $params['size']   =   (int)$object->end??1000;
            $params['index']  =   $this->index;
            $params['body']['query']['bool'] ['must'] = [
                                                            ['term' => ['enabled'=>1]],
                                                            ['term' => ['published'=>1]],
                                                            ['term' => ['project_id' => $object->project_id]]
                                                        ];

		    if(!empty($object->event_date)):
		       	$condition          = [ 'nested' =>  [  'path'        =>  'initiative_details',
		                                                        'query'       =>  ['bool' => ['should' => [ ['term' => ['initiative_details.event_date' => (string)$object->event_date]] ,
		                                                                                                    ['term' => ['initiative_details.event_end_date' => (string)$object->event_date]]]]],
		                										'inner_hits'  =>  ['from' => 0, 'size'  =>  (int)100 ]]];
		  		array_push($params['body']['query']['bool']['must'], $condition);
		    else:
		        $condition          = [ 'nested' =>  [  'path'        =>  'initiative_details',
		                                                        'query'       =>  ['bool' => ['should' => [ ['range' => ['initiative_details.event_end_date' => [ 'lt' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]] ,
		                                                                                                    ['range' => ['initiative_details.event_date' => [ 'lt' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]]]]],
		                										'inner_hits'  =>  [ 'from' => 0, 'size'  =>  (int)100 ]]];
		        array_push($params['body']['query']['bool']['must'], $condition);
		    endif;
            $results = $this->es_search($params);
            $arr = [];
            if (!empty($results['hits']['total'])):
                foreach ($results['hits']['hits'] as $key => $obj):
                    $innerdoc   =   $results['hits']['hits'][$key]['_source']??[];
                    $row        =   $obj['inner_hits'];
                    if($row['initiative_details']['hits']['total'] > 0 ):
                        foreach ($row['initiative_details']['hits']['hits'] as $key => $arr_det) {
                        	array_push($arr, $this->setInitiativeObject($innerdoc,$arr_det['_source']));
                        }
                    endif;
                endforeach;
            endif;

            function sortByDate($a, $b){
                $t1 = strtotime($a->event_date);
                $t2 = strtotime($b->event_date);
                return $t2 - $t1;
            }
            usort($arr, 'sortByDate');
			return  $arr;
        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }

    public function getInitiativeBySearch($object){
    	try{
    		$params = [];
    		$params['index'] 	= 	$this->index;
    		$params['type']  	= 	$this->type;
    		$params['scroll']  	= 	'30s';
    		$params['size']  	= 	1000;
    		$params['body']['query']['bool'] ['must'] = [	['term' => ['enabled'=>1]],
    														['term' => ['published'=>1]],
    														['term' => ['project_id' => $object->project_id]]
    													];

    		$this->search_token		=	trim($object->query);
    		$this->setFullTextToken();
    		$arr = [];

    		//search in description
    		$sub_query           =   [ 'nested'  =>    [   'path'   =>  'initiative_details',
    		    											'query'       => [ ['query_string' => ['query' =>  (string)$this->search_token , 'default_field' => 'initiative_details.short_description' ]],
    				    													],
    		    																'inner_hits'  =>  [ 'from' => 0, 'size'  =>  (int)100 ]]
    		    								];
    		array_push($params['body']['query']['bool']['must'], $sub_query);

 			$results = $this->es_search($params);

 			while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
	 			if (!empty($results['hits']['total'])):
	 				foreach ($results['hits']['hits'] as $key => $obj):
	 					$innerdoc   =   $results['hits']['hits'][$key]['_source']??[];
	 					$row        =   $obj['inner_hits'];
	 					if($row['initiative_details']['hits']['total'] > 0 ):
	 						foreach ($row['initiative_details']['hits']['hits'] as $key => $arr_det) {
	 							$robject	=	$this->setInitiativeObject($innerdoc,$arr_det['_source']);
	 							if($robject->event_date <  date('Y-m-d')){
	 								array_push($arr, $robject);
	 							}
	 						}
	 					endif;
	 				endforeach;
	 			endif;
	 			$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
 			}

 			//search in title with date check
 			array_pop($params['body']['query']['bool']['must']);
    		$title_query		=	[[ 'query_string' => [	'fields'		=>	['title'],
    														'query'			=> (string)$this->search_token ]],
    								 [ 'nested' =>  [  'path'        =>  'initiative_details',
    													'query'       =>  ['bool' => ['should' => [ ['range' => ['initiative_details.event_end_date' => [ 'lt' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]] ,
    																			['range' => ['initiative_details.event_date' => [ 'lt' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]]]]],
    																			'inner_hits'  =>  [ 'from' => 0, 'size'  =>  (int)100 ]]]];

    		array_push($params['body']['query']['bool']['must'], $title_query);

    		$results = $this->es_search($params);
    		while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
	   			if (!empty($results['hits']['total'])):
	    			foreach ($results['hits']['hits'] as $key => $obj):
	    				$innerdoc   =   $results['hits']['hits'][$key]['_source']??[];
	    				$row        =   $obj['inner_hits'];
	    				if($row['initiative_details']['hits']['total'] > 0 ):
	    					foreach ($row['initiative_details']['hits']['hits'] as $key => $arr_det) {
	    						array_push($arr, $this->setInitiativeObject($innerdoc,$arr_det['_source']));
	    					}
	    				endif;
	    			endforeach;
	    		endif;
	    		$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
    		}

            $temp = array_unique(array_column($arr, 'microgive_initiative_detail_id'));
            $unique_arr = array_intersect_key($arr, $temp);

    		function date_sorter($a, $b) {
    			return  strtotime($b->event_date) - strtotime($a->event_date);
    		}
    		usort($unique_arr , 'date_sorter');
    		$unique_arr	=	array_slice($unique_arr, $object->start, $object->end);
    		return $unique_arr;

    	} catch(Exception $e)
    	{ $this->es_error($e);  }
    }

    private function setInitiativeObject($innerdoc,$row){
    	try{
    			$arr_row                                    =    $row;
	    		$objDetail                                  =    new stdClass();
	    		$objDetail->initiative_id                   =    (int)$innerdoc['initiative_id'];
	    		$objDetail->microgive_initiative_detail_id  =    $arr_row['microgive_initiative_detail_id'];
	    		$objDetail->type                            =    (string)$this->common->entityDecode($innerdoc['type']);
	    		$objDetail->title                           =    (string)$this->common->entityDecode($innerdoc['title']);
	    		$objDetail->promo_image                     =    (object)[  'base_url'     =>  (string)_AWS_URL._MG_INITIATIVES_IMAGES_DIR,
	    																	'image_path'   =>  $arr_row['promo_image']];
	    		$objDetail->short_description               =    (string)$this->common->entityDecode($arr_row['short_description']);

	    		$arr_enabled_participants = array_filter($arr_row['participants'], function ($participant) {
	    			return ($participant['enabled'] == 1);
	    		});

    			$objDetail->participants_count              =    count($arr_enabled_participants);
    			if($objDetail->type == "V"){
    				$objDetail->event_date = $arr_row['event_date'];
    			}
    			else{
    				$objDetail->event_date = $arr_row['event_end_date'];
    			}
    		return $objDetail;
    	} catch(Exception $e)
    	{ $this->es_error($e);  }
    }
    /*--------------------------------------------- PAST INITIATIVE FUNCTIONS ENDS HERE  -------------------------------------------------*/

    public function getInitiativeById($object){
        try{
            $params = [];
            $params['index']    =     $this->index;
            $params['type']     =     $this->type;

            $params['body']['query']['bool']['must'] = [['term' => ['initiative_id' => (int)$object->initiative_id]]];

            $condition          = [ 'nested' =>  [  'path'        =>  'initiative_details',
                                                    'query'       =>  ['bool' => ['should' => [ ['range' => ['initiative_details.event_end_date' => [ 'gte' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]] ,
                                                                                                ['range' => ['initiative_details.event_date' => [ 'gte' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]]]]],
                                                    'inner_hits'  =>  ['from' => 0, 'size'  =>  (int)100 ]]];
            $params['body']['query']['bool']['must'][1] = $condition;
            $result = $this->es_search($params);

            $innerdoc   =   $result['hits']['hits'][0]['_source']??[];

            if(!empty($innerdoc)):
                $robject                        =       new stdClass();
                $robject->initiative_id         =       (int)$innerdoc['initiative_id'];
                $robject->title                 =       (string)$this->common->entityDecode($innerdoc['title']);
                $robject->type                  =       (string)$this->common->entityDecode($innerdoc['type']);
                $robject->promo_image           =       [   'base_url'    =>  (string)_AWS_URL._MG_INITIATIVES_IMAGES_DIR,
                                                            'image_path'  =>  (string)$innerdoc['promo_image']
                                                        ];
                $robject->short_description     =       (string)$this->common->entityDecode($innerdoc['short_description']);
                $robject->description           =       (string)$this->common->entityDecode($innerdoc['description']);

                $arr_enabled_comments = array_filter($innerdoc['comments'], function ($comment) {
                    return ($comment['enabled'] == 1);
                });

                $robject->comment_count         =       (int)count($arr_enabled_comments);

                $initiative_details             =       [];

                if (!empty($result['hits']['total'])):
                    foreach ($result['hits']['hits'] as $key => $obj):
                        if($obj['inner_hits']['initiative_details']['hits']['total'] > 0 ):
                            $row                            =       $obj['inner_hits'];
                            foreach ($row['initiative_details']['hits']['hits'] as $key => $arr_det) {
                                $arr_row                                    =   $arr_det['_source'];
                                $objDetail                                  =    new stdClass();
                                $objDetail->microgive_initiative_detail_id  =    $arr_row['microgive_initiative_detail_id'];
                                $objDetail->promo_image                     =    (object)[  'base_url'      =>  (string)_AWS_URL._MG_INITIATIVES_IMAGES_DIR,
                                                                                            'image_path'   =>  $arr_row['promo_image']];

                                $objDetail->short_description               =    (string)$this->common->entityDecode($arr_row['short_description']);
                                $objDetail->map_link                        =    (string)$this->common->entityDecode($arr_row['map_link']);
                                $objDetail->map_link_title                  =    (string)$this->common->entityDecode($arr_row['map_link_name']);
                                $objDetail->event_date                      =    $arr_row['event_date'];
                                $objDetail->time_range_from                 =    $arr_row['time_range_from'];
                                $objDetail->time_range_to                   =    $arr_row['time_range_to'];
                                $objDetail->registration_allowed_date       =    $arr_row['registration_allowed_date'];
                                $objDetail->is_tantative_date               =    $arr_row['is_tantative_date'];
                                $objDetail->max_registrations_allowed       =    $arr_row['max_registrations_allowed'];
                                $objDetail->event_end_date                  =    $arr_row['event_end_date'];
                                // $objDetail->participants                    =    $arr_row['participants']??[];

                                $arr_enabled_participants = array_filter($arr_row['participants'], function ($participant) {
                                    return ($participant['enabled'] == 1);
                                });

                                $objDetail->participants_count              =    count($arr_enabled_participants);

                                $is_active_participant                      =    (in_array($object->employee_id, array_column($arr_row['participants'], 'employee_id'))?true:false);

                                $searchedValue = $object->employee_id;
                                $arr_participant        =  array_filter(
                                    $arr_row['participants'],
                                    function ($e) use (&$searchedValue) {
                                        return $e['employee_id'] == $searchedValue;
                                    }
                                );

                                $arr_participant = array_values($arr_participant);

                                $objDetail->show_register               =    true;
                                $objDetail->show_cancel                 =    false;
                                $objDetail->show_updates                =    false;

                                if(!empty($arr_participant)){
                                    if($is_active_participant && $arr_participant[0]['delete_reason'] == NULL){
                                        $objDetail->show_register               =    false;
                                        $objDetail->show_cancel                 =    true;
                                        $objDetail->show_updates                =    true;
                                    }

                                    if (date('Y-m-d',strtotime($objDetail->registration_allowed_date)) < date('Y-m-d') || $arr_participant[0]['delete_reason'] == '0' || $arr_participant[0]['delete_reason'] == '1') {
                                        $objDetail->show_register               =    false;
                                        $objDetail->show_cancel                 =    false;
                                    }
                                }else{
                                    if (date('Y-m-d',strtotime($objDetail->registration_allowed_date)) < date('Y-m-d')) {
                                        $objDetail->show_register               =    false;
                                        $objDetail->show_cancel                 =    false;
                                    }
                                }

                                //feedback enable
                                $objDetail->enable_feedback                 =   (boolean)($robject->type == "V" &&  $arr_row['event_date'] < date('Y-m-d') && !in_array($object->employee_id,$arr_row['feedbacks'] )  && $is_active_participant)?true:false;

                                if ($robject->type == 'D' && date('Y-m-d',strtotime($arr_row['event_end_date'])) >= date('Y-m-d')) {
                                    $objDetail->show_donate_button = true;
                                }
                                else{
                                    $objDetail->show_donate_button = false;
                                }

                                array_push($initiative_details, $objDetail);
                            }
                        endif;
                    endforeach;
                endif;

                function date_compare($a, $b)
                {
                    $t1 = strtotime($a->event_date);
                    $t2 = strtotime($b->event_date);
                    return $t1 - $t2;
                }
                usort($initiative_details, 'date_compare');

                $robject->details = $initiative_details;
            endif;

            return !empty($robject)?$robject:new stdClass();
        }
        catch(Exception $e)
        {  $this->es_error($e); }
    }

    /*public function getInitiativeById($object){
        try{
            $params = [];
            $params['index']  =   $this->index;
            $params['type']   = $this->type;
            $params['id']   = (int)$object->initiative_id;

            $result = $this->es_get($params);
            if (!empty($result['_source'])):
                $robject  = $this->setObject((object)$result['_source'], $object);
            endif;
            return !empty($robject)?$robject:new stdClass();
        }
        catch(Exception $e)
        {  $this->es_error($e); }
    }*/

    public function checkMicrogiveInitiativeParticipate($object) {
        try{
            $params = [];
            $params['index']  =   $this->index;
            $params['body']['query']['bool']  = [ 'must' => [   ['term' => ['enabled'=>1]],
                                                                ['term' => ['published'=>1]]]];

            $condition = ['nested' =>  [    'path'        => 'initiative_details',
                                            'query'       =>  ['bool' => ['must' => [['term' => ['initiative_details.microgive_initiative_detail_id' => $object->microgive_initiative_detail_id]]]]]]];
            array_push($params['body']['query']['bool']['must'],$condition);

            $results    =   $this->es_search($params);
            if (!empty($results['hits']['hits'])):
                return (object)$this->setOutput($this->es_search($params),$object,false)[0];
            endif;
            return !empty($robject)?$robject:new stdClass();

        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }

    private function setOutput($results,$object,$partial=false){
        try{
            $arr    = [];
            if ($results['hits']['total']>0):
                foreach ($results['hits']['hits'] as $obj):
                    if (!empty($obj['_source'])):
                        $row  = (object)$obj['_source'];
                        array_push($arr, $this->setObject($row, $object,$partial));
                    endif;
                endforeach;
            endif;

            if(!empty($arr[0]->type) && $arr[0]->type == 'D'):
                function sortByDate($a, $b)
                {
                    $t1 = strtotime($a->event_end_date);
                    $t2 = strtotime($b->event_end_date);
                    return $t1 - $t2;
                }
                usort($arr, 'sortByDate');
            endif;


            return $arr;
        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }

    private function setObject($row, $object, $partial=false){
        try{
            $robject                        =   	new stdClass();
			$robject->initiative_id         =   	(int)$row->initiative_id;
            $robject->title                 =   	(string)$this->common->entityDecode($row->title);
            $robject->type                  =   	(string)$this->common->entityDecode($row->type);
			$robject->promo_image           =   	[   'base_url'    =>  (string)_AWS_URL._MG_INITIATIVES_IMAGES_DIR,
                                                        'image_path'  =>  (string)$row->promo_image  ];
			$robject->short_description     =   	(string)$this->common->entityDecode($row->short_description);
            $robject->comment_count         =       (int)count($row->comments);

           	if (!$partial):
            	$robject->description      	=   	(string)$this->common->entityDecode($row->description);
            	$initiative_details         =   	[];
	            foreach($row->initiative_details as $key => $detail) {
	                array_push($initiative_details, $this->setInitiativeDetailObject($detail,$object,$row->type));
	            }
	            $robject->details = $initiative_details;
            else:
                /*---------- Show donation last date if type is donation for initiative listing by donate --------*/
                if($robject->type == 'D'){
                    $robject->event_end_date = $row->initiative_details[0]['event_end_date'];
                }
           	endif;

            return $robject;
        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }


    private function setInitiativeDetailObject($detail,$object,$type){
    	try{
    		$objDetail                                  =    new stdClass();
    		$objDetail->microgive_initiative_detail_id  =    $detail['microgive_initiative_detail_id'];
    		$objDetail->promo_image                     =    (object)[  'base_url'      =>  (string)_AWS_URL._MG_INITIATIVES_IMAGES_DIR,
    																	'image_path'   =>  $detail['promo_image']];

    		$objDetail->short_description               =    (string)$this->common->entityDecode($detail['short_description']);
    		$objDetail->map_link                        =    (string)$this->common->entityDecode($detail['map_link']);
    		$objDetail->map_link_title                  =    (string)$this->common->entityDecode($detail['map_link_name']);
    		$objDetail->event_date                      =    $detail['event_date'];
    		$objDetail->time_range_from                 =    $detail['time_range_from'];
    		$objDetail->time_range_to                   =    $detail['time_range_to'];
    		$objDetail->registration_allowed_date       =    $detail['registration_allowed_date'];
    		$objDetail->is_tantative_date               =    $detail['is_tantative_date'];
    		$objDetail->max_registrations_allowed       =    $detail['max_registrations_allowed'];
    		$objDetail->event_end_date                  =    $detail['event_end_date'];

    		$objDetail->participants                    =    $detail['participants']??[];

    		$arr_enabled_participants = array_filter($objDetail->participants, function ($participant) {
    			return ($participant['enabled'] == 1);
    		});

			// $objDetail->participants_count              =    count($arr_enabled_participants);
			// $objDetail->participants                    =    $arr_enabled_participants??[];

			$objDetail->participants_count              =    count($arr_enabled_participants);

			$is_active_participant                      =    (in_array($object->employee_id, array_column($detail['participants'], 'employee_id'))?true:false);

            $searchedValue = $object->employee_id;
            $arr_participant        =  array_filter(
                $detail['participants'],
                function ($e) use (&$searchedValue) {
                    return $e['employee_id'] == $searchedValue;
                }
            );
            $arr_participant = array_values($arr_participant);

            $objDetail->show_register               =    true;
            $objDetail->show_cancel                 =    false;

            if(!empty($arr_participant)):
                if($is_active_participant && $arr_participant[0]['delete_reason'] == NULL){
                    $objDetail->show_register               =    false;
                    $objDetail->show_cancel                 =    true;
                }
                if (date('Y-m-d',strtotime($objDetail->registration_allowed_date)) < date('Y-m-d') || $arr_participant[0]['delete_reason'] == '0' || $arr_participant[0]['delete_reason'] == '1') {
                    $objDetail->show_register               =    false;
                    $objDetail->show_cancel                 =    false;
                }
            endif;

            //feedback enable
            $objDetail->enable_feedback                 =   (boolean)($type == "V" &&  $detail['event_date'] < date('Y-m-d') && !in_array($object->employee_id,$detail['feedbacks'] )  && $is_active_participant)?true:false;

            if ($type == 'D' && date('Y-m-d',strtotime($detail['event_end_date'])) >= date('Y-m-d')) {
                $objDetail->show_donate_button = true;
            }
            else{
                $objDetail->show_donate_button = false;
            }

    		return $objDetail;
    	} catch(Exception $e)
    	{ $this->es_error($e);  }
    }

    public function refresh(){
        try{
            $params = [];
            $params['index']      =   $this->index;
            return $this->es_indices_refresh($params);
        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }

    public function apply($object){
        try {
            $params             =   [];
            $params['index']    =   $this->index;
            $params['type']     =   $this->type;

            $params['body']['query']['bool']['must']    =   ['term'=>   ['initiative_id' => $object->initiative_id]];

            $params['body']['script']['inline']         =   '
                        for (int i = 0; i < ctx._source.initiative_details.size(); i++){
                            if(ctx._source.initiative_details[i].microgive_initiative_detail_id == params.microgive_initiative_detail_id){
                                ctx._source.initiative_details[i].participants.add(params.participant);
                            }
                        }';

            $params['body']['script']['params'] = ['participant' => $object->participation_data,'microgive_initiative_detail_id' => (int)$object->microgive_initiative_detail_id];

            return $this->es_updateByQuery($params);

        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }

    public function updateParticipantStatus($object){
        try{
            $params = [];
            $params['index']        =   $this->index;
            $params['type']         =   $this->type;

            if($object->enabled == 0){
                $delete_reason = (String)$object->delete_reason;
            }
            else{
                $delete_reason = NULL;
            }

            $params['body']['query']['bool']['must']    =   ['term'=>   ['initiative_id' => $object->initiative_id]];

            $params['body']['script']['inline']         =   '
                        for (int i = 0; i < ctx._source.initiative_details.size(); i++){
                            if(ctx._source.initiative_details[i].microgive_initiative_detail_id == params.microgive_initiative_detail_id){
                                for (int j = 0; j < ctx._source.initiative_details[i].participants.size(); j++){
                                    if(ctx._source.initiative_details[i].participants[j].employee_id == params.employee_id){
                                        ctx._source.initiative_details[i].participants[j].enabled            =   params.enabled;
                                        ctx._source.initiative_details[i].participants[j].delete_reason      =   params.delete_reason;
                                    }
                                }
                            }
                        }';

            $params['body']['script']['params'] =   ['enabled' => (int)$object->enabled, 'microgive_initiative_detail_id' => (int)$object->microgive_initiative_detail_id, 'employee_id' => (int)$object->employee_id, 'delete_reason' => $delete_reason];

            return $this->es_updateByQuery($params);
        }
        catch(Exception $e)
        {   $this->es_error($e);    }
    }

    public function getDiscussions($object){
        try{
            $params 			= 	[];
            $params['index'] 	= 	$this->index;
            $params['type']  	= 	$this->type;
            $params['_source']	=	false;

            $params['body']['query']['bool']['must']	= 	[	['term' 	=> ['initiative_id' => (int)$object->initiative_id]],
            ['nested'	=>	['path' => 'discussions',
            'query'	=> ['bool' => ['must' => [['term' => ['discussions.enabled' => (int)1]]]]],
            'inner_hits'	=>	['from'	=>	(int)$object->start, 'size'	=>	(int)$object->end, 'sort' => ['discussions.created_on' => ['order'	=>	'desc' ]]]]]];

            $results	=	$this->es_search($params);
            $robjects	=	[];
            $empIds		=	[];

            if (isset($results['hits']['hits'][0]['inner_hits']['discussions']['hits']['hits'])):
                if ($results['hits']['hits'][0]['inner_hits']['discussions']['hits']['total']>0):
                    $docs	=	$results['hits']['hits'][0]['inner_hits']['discussions']['hits']['hits'];
                    foreach ($docs as $obj):
                        $obj						=	(object)$obj['_source'];
                        $object						=	new stdClass();
                        $object->discussion_id		=	(int)$obj->discussion_id;
                        $object->description		=	(string)$this->common->entityDecode($obj->description);
                        $object->created_on			=	(string)$obj->created_on;
                        $object->employee			=	(object)['employee_id' 	=> 	(int)$obj->employee_id];

                        array_push($empIds, (string)$obj->employee_id);
                        array_push($robjects, $object);
                    endforeach;
                endif;
            endif;

            $posts		=	[];
            $empIds		=	array_unique($empIds);
            if (!empty($empIds)):
                $ES_Employee	=	new ES_Employee();
                $objEmployees	=	$ES_Employee->getEmployeeByIds($empIds);

                if (!empty($objEmployees)):
                    foreach ($robjects as $robject):
                        $Key	=	array_search($robject->employee->employee_id, array_column($objEmployees, 'employee_id'));
                        if ($Key!==false):
                            $robject->employee		=	(object)[	'employee_id' 		=> 	(int)$objEmployees[$Key]['employee_id'],
                                                                    'first_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['first_name']),
                                                                    'middle_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['middle_name']),
                                                                    'last_name'			=>	(string)$this->common->entityDecode($objEmployees[$Key]['last_name']),
                                                                    'display_name'		=>	(string)$this->common->entityDecode($objEmployees[$Key]['display_name']),
                                                                    'profile_picture'	=>	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
                                                                    'image_path'        =>	(string)$objEmployees[$Key]['profile_picture']]];
                            array_push($posts,$robject);
                        endif;
                    endforeach;
                endif;
            endif;
            return $posts;
        }
        catch(Exception $e)
        { $this->es_error($e);	}

    }


    public function addDiscussion($object){
        try{
            $params = [];
            $params['index']  	= 	$this->index;
            $params['type']  	= 	$this->type;
            $params['id']    	= 	(int)$object->initiative_id;

            $doc 			= 	(object)[	'discussion_id'		=>	(int)$object->discussion_id,
                                            'employee_id'		=>	(int)$object->employee_id,
                                            'description'		=>	(string)$this->common->entityEncode($object->description),
                                            'created_on'		=>	(string)$object->created_on,
                                            'enabled'			=>	(int)1];

            $params['body']['script']['inline']	=	'ctx._source.discussions.add(params.discussion)';
            $params['body']['script']['params']	=	['discussion' => $doc];
            return $this->es_update($params);
        }
        catch(Exception $e)
        {	$this->es_error($e);	}
    }



    public function addDonation($object){
        try{
            $params = [];
            $params['index']  	= 	$this->index;
            $params['type']  	= 	$this->type;
            $params['id']    	= 	(int)$object->initiative_id;

            $doc 	= 	(object)[	'donation_id'		=>		(int)$object->donation_id,
                                    'employee_id'		=>		(int)$object->employee_id,
                                    'currency_id'       =>      (int)$object->currency_id,
                                    'amount'			=>		(int)$object->amount,
                                    'created_on'		=>		(string)$object->created_on];

            $params['body']['script']['inline']		=	'for (int i = 0; i < ctx._source.initiative_details.size(); i++){if(ctx._source.initiative_details[i].microgive_initiative_detail_id == params.microgive_initiative_detail_id){ctx._source.initiative_details[i].donations.add(params.donation)}}';
            $params['body']['script']['params']		=	['microgive_initiative_detail_id' => (int)$object->microgive_initiative_detail_id, 'donation' => $doc ];

            return $this->es_update($params);
        }
        catch(Exception $e)
        { 	$this->es_error($e);	}
    }

    public function getMicrogiveInitiativeParticipants($object) {
        try{
            $ES_employee = new ES_Employee();
            $ES_location = new ES_Location();
            $params = [];
            $params['index']  =   $this->index;
            $params['_source']  =   ['initiative_details.participants'];

            $params['body']['query']['bool']['must']=   [   ['terms'    => ['initiative_detail_ids' => [(int)$object->microgive_initiative_detail_id ]]],
                                                            ['nested'   => ['path' => 'initiative_details',
                                                                            'query' => [ 'bool'  => ['must' => [ ['term' => ['initiative_details.microgive_initiative_detail_id' => (int)$object->microgive_initiative_detail_id]],
                                                                                                                ['nested'   =>  ['path' => 'initiative_details.participants',
                                                                                                                 'query'    => ['bool' => ['must' => [['term' => ['initiative_details.participants.enabled' => (int)1]] ]]],
                                                                                                                        'inner_hits'    =>  ['from' =>  (int)$object->start, 'size' =>  (int)$object->end, 'sort'=>['initiative_details.participants.employee_id' => ['order' => 'desc']] ]]]]]],
                                                                            'inner_hits'    =>  ['from' =>  (int)0, 'size'  =>  (int)1]
                                                            ]]];

            $result             = $this->es_search($params);
            $arr_participants   = [];
            $rObject            = [];

            if (!empty($result['hits']['total'])):
                if(!empty($result['hits']['hits'][0]['inner_hits']['initiative_details']['hits']['total'])):
                    $inner_doc      =       $result['hits']['hits'][0]['inner_hits']['initiative_details']['hits']['hits'][0]['inner_hits']??[];
                    if($inner_doc['initiative_details.participants']['hits']['total']>0):
                        $participantlist    =       $inner_doc['initiative_details.participants']['hits']['hits']??[];
                        if (!empty($participantlist)):
                            foreach($participantlist as  $key => $participant):
                                $obj_participant                                =       $participant['_source'];
                                array_push($arr_participants, $obj_participant['employee_id']);
                            endforeach;
                        endif;
                    endif;
                endif;
            endif;

            if (!empty($arr_participants)) {
                $arrEmp = $ES_employee->getEmployeeByIds($arr_participants);
                if (!empty($arrEmp)) {
                    foreach ($arrEmp as $objEmp) {
                        $objEmp                         =   (object)$objEmp;
                        $objEmpLoop                     =   new stdClass();
                        $objEmpLoop->employee_id        =   $objEmp->employee_id;
                        $objEmpLoop->name               =   $objEmp->first_name.' '.$objEmp->middle_name.' '.$objEmp->last_name;
                        $objEmpLoop->profile_picture    =   (object)[   'base_url'      =>  (string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
                                                                        'image_path'   =>  $objEmp->profile_picture];
                        $objEmpLoop->location           =   '';
                        if (!empty($objEmp->location_id)) {
                            $objLocation            =   $ES_location->getById($objEmp->location_id);
                            $objEmpLoop->location   =   $objLocation->value;
                        }
                        $objEmpLoop->position_title     =   $objEmp->position_title;
                        array_push($rObject, $objEmpLoop);
                    }
                }
            }
            return $rObject;
        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }

    public function getMicrogiveInitiativeDonors($object) {
        try{
            $ES_employee = new ES_Employee();
            $ES_location = new ES_Location();
            $params = [];
            $params['index']  =   $this->index;
            $params['_source']  =   ['initiative_details.donations'];

            $params['body']['query']['bool']['must']=   [   ['terms'    => ['initiative_detail_ids' => [(int)$object->microgive_initiative_detail_id ]]],
                                                            ['nested'   => ['path' => 'initiative_details',
                                                                            'query' => [ 'bool'  => ['must' => [ ['term' => ['initiative_details.microgive_initiative_detail_id' => (int)$object->microgive_initiative_detail_id]],
                                                                                                                ['nested'   =>  ['path' => 'initiative_details.donations',
                                                                                                                 'query'    => ['bool' => ['must' => ['exists' => ['field'=>'initiative_details.donations.donation_id'] ]]],
                                                                                                                        'inner_hits'    =>  ['from' =>  (int)$object->start, 'size' =>  (int)$object->end, 'sort'=>['initiative_details.donations.employee_id' => ['order' => 'desc']] ]]]]]],
                                                                            'inner_hits'    =>  ['from' =>  (int)0, 'size'  =>  (int)1]
                                                            ]]];

            $result             = $this->es_search($params);
            $rObject            = [];
            $arr_participants   = [];


            if (!empty($result['hits']['total'])):
                if(!empty($result['hits']['hits'][0]['inner_hits']['initiative_details']['hits']['total'])):
                    $inner_doc      =       $result['hits']['hits'][0]['inner_hits']['initiative_details']['hits']['hits'][0]['inner_hits']??[];
                    if($inner_doc['initiative_details.donations']['hits']['total']>0):
                        $donorlist    =       $inner_doc['initiative_details.donations']['hits']['hits']??[];
                        if (!empty($donorlist)):
                            foreach($donorlist as  $key => $donor):
                                $obj_donor                                =       $donor['_source'];
                                array_push($arr_participants, $obj_donor['employee_id']);
                            endforeach;
                        endif;
                    endif;
                endif;
            endif;

            if (!empty($arr_participants)) {
                $arrEmp = $ES_employee->getEmployeeByIds($arr_participants);
                if (!empty($arrEmp)) {
                    foreach ($arrEmp as $objEmp) {
                        $objEmp                         =   (object)$objEmp;
                        $objEmpLoop                     =   new stdClass();
                        $objEmpLoop->employee_id        =   $objEmp->employee_id;
                        $objEmpLoop->name               =   $objEmp->first_name.' '.$objEmp->middle_name.' '.$objEmp->last_name;
                        $objEmpLoop->profile_picture    =   (object)[   'base_url'      =>  (string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
                                                                        'image_path'   =>  $objEmp->profile_picture];
                        $objEmpLoop->location           =   '';
                        if (!empty($objEmp->location_id)) {
                            $objLocation            =   $ES_location->getById($objEmp->location_id);
                            $objEmpLoop->location   =   $objLocation->value;
                        }
                        $objEmpLoop->position_title     =   $objEmp->position_title;
                        array_push($rObject, $objEmpLoop);
                    }
                }
            }
            return $rObject;
        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }

    public function addGalleryPicture($object){
        try{
            $params = [];
            $params['index']    =   $this->index;
            $params['type']     =   $this->type;

            $params['body']['query']['bool']['must']    =   ['term'=>   ['initiative_id' => $object->initiative_id]];

            $params['body']['script']['inline']         =   '
                        for (int i = 0; i < ctx._source.initiative_details.size(); i++){
                            if(ctx._source.initiative_details[i].microgive_initiative_detail_id == params.microgive_initiative_detail_id){

                                ctx._source.initiative_details[i].gallery.add(params.obj_image);

                            }
                        }';

            $obj_image            =   (object)[ 'picture_id'        =>  (int)$object->picture_id,
                                                'picture_path'      =>  (string)$object->picture_path,
                                                'employee_id'       =>  (int)$object->employee_id,
                                                'created_on'        =>  (string)$object->created_on,
                                                'approved'          =>  (int)0,
                                                'rejected'          =>  (int)0,
                                                'enabled'           =>  (int)1 ];

            $params['body']['script']['params'] = ['obj_image' => $obj_image,'microgive_initiative_detail_id' => (int)$object->microgive_initiative_detail_id];
            return $this->es_updateByQuery($params);
        }
        catch(Exception $e)
        {   $this->es_error($e);    }
    }

    public function getMicrogiveProjects($obj) {
        try {
            $obj->type='V';
            $objPastVoluntaryInitiatives = $this->getMicrogivePastProjectIds($obj);
            $obj->type='D';
            $objPastDonationInitiatives = $this->getMicrogivePastProjectIds($obj);
            $rObject = array_merge($objPastVoluntaryInitiatives,$objPastDonationInitiatives);
            return $rObject;
        }
        catch(Exception $e)
        {   $this->db_error($e); }
    }

    public function getMicrogivePastProjectIds($object) {
        try{
            $params = [];
            $params['from']     =     (int)$object->start??0;
            $params['size']     =     (int)$object->end??1000;
            $params['index']    =     $this->index;
            $params['body']['query']['bool']  = [ 'must' => [   [   'term' => ['enabled'=>1]],
                                                                [   'term' => ['published'=>1]]]];
            $params['body']['query']['bool']['should']=[];
            if (!empty($object->type)):
                array_push($params['body']['query']['bool']['must'], ['term' =>  ['type'  =>  (string)$object->type]]);
            endif;
            if ($object->type=="V"):
                $condition = ['nested' =>  [    'path'        => 'initiative_details',
                                                'query'       =>  ['bool' => ['must' => [['range' => ['initiative_details.event_date' => [ 'lt' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]]]]]]];
                array_push($params['body']['query']['bool']['must'],$condition);
            elseif($object->type=="D"):
                $condition = ['nested' =>  [    'path'        => 'initiative_details',
                                                'query'       =>  ['bool' => ['must' => [['range' => ['initiative_details.event_end_date' => [ 'lt' => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]]]]]]];
                array_push($params['body']['query']['bool']['must'],$condition);
            endif;
            $result = $this->es_search($params);
            $arr = [];
            if ($result['hits']['total']>0):
                foreach ($result['hits']['hits'] as $obj):
                    if (!empty($obj['_source'])):
                        $row                            =   (object)$obj['_source'];
                        $rObject                        =   new stdClass();
                        $rObject->initiative_id         =   (int)$row->initiative_id;
                        $rObject->project_id            =   (string)$row->project_id;
                        array_push($arr, $rObject);
                    endif;
                endforeach;
            endif;
            return (!empty($arr)?$arr:[]);
        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }

    public function updateTags($object){
        try{
            $params = [];
            $params['index']        =   $this->index;
            $params['type']         =   $this->type;

            $params['body']['query']['bool']['must']    =   ['term'=>   ['initiative_id' => $object->initiative_id]];

            $params['body']['script']['inline']         =   '
                        for (int i = 0; i < ctx._source.initiative_details.size(); i++){
                            if(ctx._source.initiative_details[i].microgive_initiative_detail_id == params.microgive_initiative_detail_id){
                                for (int j = 0; j < ctx._source.initiative_details[i].gallery.size(); j++){
                                    if(ctx._source.initiative_details[i].gallery[j].picture_id == params.picture_id){
                                        ctx._source.initiative_details[i].gallery[j].tags = params.tags;
                                    }
                                }
                            }
                        }';

            $params['body']['script']['params'] =   ['tags' => array_map('intval', explode(',',$object->tags)), 'microgive_initiative_detail_id' => (int)$object->microgive_initiative_detail_id, 'picture_id' => (int)$object->picture_id];

            return $this->es_updateByQuery($params);
        }
        catch(Exception $e)
        {   $this->es_error($e);    }
    }

    public function getMicrogiveProjectsByIds($obj){
        try{
            $params                         =   [];
            $params['index']                =   'microgive_projects';
            $params['type']                 =   'microgive_project';
            $params['size']                 =   1000;
            $params['scroll']               =   '30s';
            $params['body']['query']['bool']['must'][0]['ids'] =   ['values' => array_values(array_map('intval',$obj->projectIds))];
            $params['body']['sort']         =   ['project_id' => ['order'  =>  'asc']];

            if (!empty($obj->query)):
                $query  = ['multi_match'   =>  ['query' => $obj->query,'fields' => ['description','title']]];
                array_push($params['body']['query']['bool']['must'], $query);
            endif;

            $params['body']['query']['bool'][ 'must'][1] = ['term' => ['enabled'=>1]];


            if (!empty($obj->query)):
                $query  = ['multi_match'   =>  ['query' => $obj->query,'fields' => ['description','title']]];
                array_push($params['body']['query']['bool']['must'], $query);
            endif;

            $results  = $this->es_search($params);
            $rObject  = [];
            while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
                if ($results['hits']['total']>0):
                    foreach ($results['hits']['hits'] as $object):
                        $obj                        =   (object)$object['_source'];
                        $objProject                 =   new stdClass();
                        $objProject->project_id     =   $obj->project_id;
                        $objProject->title          =   $obj->title;
                        $objProject->description    =   $obj->description;
                        $objProject->promo_image    =   (object)[   'base_url'    =>  (string)_AWS_URL._MG_PROJECTS_IMAGES_DIR,
                                                                    'image_path'  =>  (string)$obj->promo_image];
                        array_push($rObject, $objProject);
                    endforeach;
                endif;
                $results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
            }
            return $rObject;
        }
        catch(Exception $e)
        { $this->es_error($e);   }
    }

    /*--------------------------------------------- PAST INITIATIVE DETAIL BY DETAIL ID FUNCTIONS  -------------------------------------------------*/

    public function getInitiativeDetailByDetailId($object) {
    	try{
    		$params = [];
    		$params['index']  =   $this->index;
    		$params['body']['query']['bool']['must']	=   [   ['terms'    => ['initiative_detail_ids' => [$object->microgive_initiative_detail_id]]],
                                                                ['term'     => ['enabled'=>(int)1]] ,
										    					['nested'   => [    'path'          => 'initiative_details',
										    										'query'         =>  ['bool' => ['must' => ['term' => ['initiative_details.microgive_initiative_detail_id' => $object->microgive_initiative_detail_id]]]],
										    										'inner_hits'    =>  ['from' =>  (int)0, 'size'  =>  (int)1]]]];

    		$results        =     $this->es_search($params);
    		$robject		=	  new stdClass();
    		if (isset($results['hits']['hits'][0]['inner_hits']['initiative_details']['hits']['hits'])):
    			if ($results['hits']['hits'][0]['inner_hits']['initiative_details']['hits']['total']>0):
    				$docs	=	$results['hits']['hits'][0]['inner_hits']['initiative_details']['hits']['hits'];
    				$row							=		(object)$results['hits']['hits'][0]['_source'];
    				$robject						=	    $this->setObjectInitiativeDetailByDetailId($row, $object,true);
    				$robject->initiative_details	=		$this->setInitiativeDetailByDetailIdObject($docs[0]['_source'], $object,$robject->type);
    			endif;
    		endif;
    		return $robject;
    	}
    	catch(Exception $e)
    	{ $this->es_error($e);  }
    }

    private function setObjectInitiativeDetailByDetailId($row, $object, $partial=false){
        try{
            $robject                        =       new stdClass();
            $robject->initiative_id         =       (int)$row->initiative_id;
            $robject->title                 =       (string)$this->common->entityDecode($row->title);
            $robject->type                  =       (string)$this->common->entityDecode($row->type);
            $robject->promo_image           =       [   'base_url'    =>  (string)_AWS_URL._MG_INITIATIVES_IMAGES_DIR,
                                                        'image_path'  =>  (string)$row->promo_image  ];
            $robject->short_description     =       (string)$this->common->entityDecode($row->short_description);
            if (!$partial):
                $robject->description       =       (string)$this->common->entityDecode($row->description);
                $initiative_details         =       [];
                foreach($row->initiative_details as $key => $detail) {
                    array_push($initiative_details, $this->setInitiativeDetailObject($detail,$object,$row->type));
                }
                $robject->details = $initiative_details;
            endif;
            return $robject;
        }
        catch(Exception $e)
        { $this->es_error($e);  }
    }

    private function setInitiativeDetailByDetailIdObject($detail,$object,$type){
        try{
            $objDetail                                  =    new stdClass();
            $objDetail->microgive_initiative_detail_id  =    $detail['microgive_initiative_detail_id'];
            $objDetail->promo_image                     =    (object)[  'base_url'      =>  (string)_AWS_URL._MG_INITIATIVES_IMAGES_DIR,
                                                                        'image_path'   =>  $detail['promo_image']];

            $objDetail->short_description               =    (string)$this->common->entityDecode($detail['short_description']);
            $objDetail->map_link                        =    (string)$this->common->entityDecode($detail['map_link']);
            $objDetail->map_link_title                  =    (string)$this->common->entityDecode($detail['map_link_name']);
            $objDetail->event_date                      =    $detail['event_date'];
            $objDetail->time_range_from                 =    $detail['time_range_from'];
            $objDetail->time_range_to                   =    $detail['time_range_to'];
            $objDetail->registration_allowed_date       =    $detail['registration_allowed_date'];
            $objDetail->is_tantative_date               =    $detail['is_tantative_date'];
            $objDetail->max_registrations_allowed       =    $detail['max_registrations_allowed'];
            $objDetail->event_end_date                  =    $detail['event_end_date'];
            $participants                               =    $detail['participants'];

            $arr_enabled_participants = array_filter($participants, function ($participant) {
                return ($participant['enabled'] == 1);
            });

            $is_active_participant                      =    (in_array($object->employee_id, array_column($arr_enabled_participants, 'employee_id'))?true:false);

            //feedback enable
            $objDetail->enable_feedback                 =   (boolean)($type == "V" &&  $detail['event_date'] < date('Y-m-d') && !in_array($object->employee_id,$detail['feedbacks'] ) && $is_active_participant)?true:false;

            if ($type == 'D' && date('Y-m-d',strtotime($detail['event_end_date'])) >= date('Y-m-d')) {
                $objDetail->show_donate_button = true;
            }
            else{
                $objDetail->show_donate_button = false;
            }

            return $objDetail;
        } catch(Exception $e)
        { $this->es_error($e);  }
    }

    /*--------------------------------------------- PAST INITIATIVE DETAIL BY DETAIL ID FUNCTIONS ENDS HERE  -------------------------------------------------*/

    public function addFeedBack($object){
    	try{
    		$params = [];
    		$params['index']  	= 	$this->index;
    		$params['type']  	= 	$this->type;
    		$params['body']['query']['bool']['must']	=   ['terms'    => ['initiative_detail_ids' => [$object->microgive_initiative_detail_id]]];

    		$params['body']['script']['inline']         =   '
                        for (int i = 0; i < ctx._source.initiative_details.size(); i++){
                            if(ctx._source.initiative_details[i].microgive_initiative_detail_id == params.microgive_initiative_detail_id){
                               ctx._source.initiative_details[i].feedbacks.add(params.feedback)
                            }
                        }';
    		$params['body']['script']['params']	=	['feedback' => (int)$object->employee_id , 'microgive_initiative_detail_id' => $object->microgive_initiative_detail_id ];
    		return $this->es_updateByQuery($params);
    	}
    	catch(Exception $e)
    	{ $this->es_error($e);	}
    }

    public function getInitiativePictures($object){
    	try{
    		$params = [];
    		$params['index'] 	= 	$this->index;
    		$params['type']  	= 	$this->type;
    		$params['_source']  	= 	'initiative_details.gallery';
    		$params['body']['query']['bool']['must']= 	[	['terms' 	=> ['initiative_detail_ids' => [(int)$object->id ]]],
    														['nested'	=> ['path' => 'initiative_details',
    																		'query'	=> [ 'bool'	 => ['must' => [ ['term' => ['initiative_details.microgive_initiative_detail_id' => (int)$object->id]],
    																											['nested'	=>	['path' => 'initiative_details.gallery',
    																											 'query'	=> ['bool' => ['must' => [['term' => ['initiative_details.gallery.enabled' => (int)1]] , ['term' => ['initiative_details.gallery.approved' => (int)1]] , ['term' => ['initiative_details.gallery.rejected' => (int)0]] ]]],
    																													'inner_hits'	=>	['from'	=>	(int)$object->start, 'size'	=>	(int)$object->end, 'sort'=>['initiative_details.gallery.picture_id' => ['order' => 'desc']] ]]]]]],
    																		'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	(int)1]
    														]]];
    		$result		=	$this->es_search($params);
    		$arr		=	[];
    		if (!empty($result['hits']['total'])):
    			if(!empty($result['hits']['hits'][0]['inner_hits']['initiative_details']['hits']['total'])):
    				$inner_doc		=		$result['hits']['hits'][0]['inner_hits']['initiative_details']['hits']['hits'][0]['inner_hits']??[];
    				if($inner_doc['initiative_details.gallery']['hits']['total']>0):
    					$gallerylist	=		$inner_doc['initiative_details.gallery']['hits']['hits']??[];
	    				if (!empty($gallerylist)):
	    					foreach($gallerylist as  $key => $gallery):
	    						$gallery								=		$gallery['_source'];
		    					$pObj									=		new stdClass();
		    					$pObj->picture_id						=		(int)$gallery['picture_id'];
		    					$pObj->microgive_initiative_detail_id	=		(int)$object->id;
		    					$pObj->picture_path						=		(string)$gallery['picture_path'];
		    					$pObj->tags								=		[];
		    					$tag_arr								=		$gallery['tags'];
		    					if(!empty($tag_arr)):
		    						$ES_Employee		=		new ES_Employee();
		    						$objEmployees		=		array_column($ES_Employee->getEmployeeByIds($tag_arr),NULL,"employee_id");
		    					endif;
		    					if(!empty($tag_arr)):
			    					foreach($gallery['tags'] as $id):
				    					if(isset($objEmployees[$id]) && $objEmployees[$id]['enabled']==1):
				    						$emp	=	$this->setEmpObject($objEmployees,$id);
				    						array_push($pObj->tags, $emp);
				    					endif;
			    					endforeach;
                                    if(!function_exists('sortByName')):
    			    					function sortByName($a, $b) {
    			    						$a	=	(array)$a;
    			    						$b	=	(array)$b;
    			    						return strcmp($a['employee_name'] , $b['employee_name']);
    			    					}
                                    endif;
		    						usort($pObj->tags, 'sortByName');
		    					endif;
		    					array_push($arr, $pObj);
	    					endforeach;
	    				endif;
    				endif;
    			endif;
    		endif;
    		return $arr;
    	}
    	catch(Exception $e)
    	{  $this->es_error($e);	}
    }

    private function setEmpObject($objEmployees,$id){
    	try{

    		$emp						=	new stdClass();
    		$emp->employee_id 			= 	(int)$objEmployees[$id]['employee_id'];
    		$first_name					=	!empty($objEmployees[$id]['first_name'])?$this->common->entityDecode($objEmployees[$id]['first_name']):"";
    		$middle_name				=	!empty($objEmployees[$id]['middle_name'])?$this->common->entityDecode($objEmployees[$id]['middle_name']):"";
    		$last_name					=	!empty($objEmployees[$id]['last_name'])?$this->common->entityDecode($objEmployees[$id]['last_name']):"";
    		$emp->first_name			=	$first_name;
    		$emp->middle_name			=	$middle_name;
    		$emp->last_name				=	$last_name;
    		$sfirst_name				=	!empty($first_name)?$first_name." ":"";
    		$smiddle_name				=	!empty($middle_name)?$middle_name." ":"";
    		$emp->employee_name			=	(string)$sfirst_name.$smiddle_name.$last_name;
    		$emp->position_title		=	(string)$this->common->entityDecode($objEmployees[$id]["position_title"]);
    		$emp->employee_code			=	(string)$objEmployees[$id]['employee_code'];
    		$emp->display_name			=	(string)$objEmployees[$id]['display_name'];
    		$emp->profile_picture		=	(object)[	'base_url'		=>	(string)_AWS_URL._EMPLOYEE_IMAGES_DIR,
    													'image_path'	=>	(string)$objEmployees[$id]['profile_picture']];

    		return $emp;

    	}catch(Exception $e){
    		$this->es_error($e);
    	}
    }

    public function getVolunteeringDetailsLog($pobject){
    	try{
    		$params = [];
    		$params['index'] 	= 	$this->index;
    		$params['type']  	= 	$this->type;
    		$params['scroll']  	= 	'30s';
			$params['size']  	= 	1000;
    		$params['body']['query']['bool']['must']= 	[	['term' 	=> ['enabled' => (int)1 ]] ,['term' => ['published' => (int)1 ]] , ['term' 	=> ['type' => (string)"V" ]],
    														['nested'	=> ['path' => 'initiative_details',
    														 'query'	=> [ 'bool'	 => ['must' => [ ['range' => ['initiative_details.event_date' => ['lt' => (string)date("Y-m-d"), "format"=> "yyyy-MM-dd"] ]],
										    														 [		'nested'		=>	[	'path' => 'initiative_details.participants',
										    														 		'query'			=> 	['bool' => ['must' => [['term' => ['initiative_details.participants.enabled' => (int)1]] , ['term' => ['initiative_details.participants.employee_id' => (int)$pobject->employee_id]] ]]],
										    														 		'inner_hits'	=>	['from'	=>	(int)0, 'size'=>1]
										    														 ]]]]],
    															'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	(int)1000,  'sort'=>[ ['initiative_details.event_date' => ['order' => 'desc']],
    																																							  ['initiative_details.time_range_to' => ['order' => 'desc']]]]
    													]]];
    		$results		=	$this->es_search($params);
    		$arr			=	[];
    		while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
	    		if (!empty($results['hits']['total'])):
	    			foreach ($results['hits']['hits'] as $key => $object):
	    				if($object['inner_hits']['initiative_details']['hits']['total'] > 0 ):
	    					$row 							=		(object)$object['_source'];
		    				foreach($object['inner_hits']['initiative_details']['hits']['hits'] as $skey => $detail):
		    					$detail										=		(object)$detail['_source'];
	    						$innerdoc	=	$object['inner_hits']['initiative_details']['hits']['hits'][0]['inner_hits']['initiative_details.participants']??[];
			    				if($innerdoc['hits']['total'] > 0):
			    					$robject                       	 			=  	 	new stdClass();
			    					$robject->initiative_id         			=   	(int)$row->initiative_id;
			    					$robject->title                				=   	(string) $this->common->entityDecode($row->title);
			    					$robject->microgive_initiative_detail_id   	=   	(int)$detail->microgive_initiative_detail_id;
			    					$robject->event_date             			=   	(string)$detail->event_date;
			    					$robject->time_range_from        			=   	(string)$detail->time_range_from;
			    					$robject->time_range_to          			=   	(string)$detail->time_range_to;
			    					$ts1                						=   	new DateTime($detail->time_range_from);
			    					$ts2               		 					=   	new DateTime($detail->time_range_to);
			    					$interval           						=   	$ts1->diff($ts2);
			    					$robject->hours     						=   	(int)$interval->format('%h');
			    					$robject->minutes    						=   	(int)$interval->format('%i');
			    					$robject->sorter							=		strtotime($detail->event_date." ".$detail->time_range_to);
			    					array_push($arr, $robject);
		    					endif;
		    				endforeach;
	    				endif;
		    		endforeach;
	    		endif;
	    		$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
    		}
			function cmp($a, $b){
    			return strcmp($b->sorter,$a->sorter);
    		}
    		usort($arr, "cmp");
    		$arr	=	array_slice($arr, $pobject->start, $pobject->end);
    		return $arr;
    	}
    	catch(Exception $e)
    	{ $this->es_error($e);	}
    }

    public function getDonationDetailsLog($pobject){
    	try{
    		$params = [];
    		$params['index'] 	= 	$this->index;
    		$params['type']  	= 	$this->type;
    		$params['size']  	= 	1000;
     		$params['scroll']  	= 	'30s';
    		$params['body']['query']['bool']['must']= 	[	['term' 	=> ['type' => (string)"D" ]],
    														['nested'	=> ['path' => 'initiative_details',
    														  				'query'	=> [ 'bool' => ['must'	 =>
    																					[	'nested'		=>	[	'path' => 'initiative_details.donations',
    																						'query'			=> 	['bool' => ['must' => ['term' => ['initiative_details.donations.employee_id' => (int)$pobject->employee_id]]]],
    																						'inner_hits'	=>	['from'	=>	(int)0, 'size'=>100 ,'sort'=>['initiative_details.donations.donation_id' => ['order' => 'desc']]]
    																						]]]],
    														  				 'inner_hits'	=>	['from'	=>	(int)0, 'size'=> 100 ]]]
    		];

    		$results		=	$this->es_search($params);
    		$es_currency 	=	new ES_Currency();
    		$objCurrency	=	$es_currency->getCurrencies();
    		if(!empty($objCurrency)):
    			$objCurrency	=	array_column($objCurrency, 'abbreviation','currency_id');
    		endif;

    		$arr			=	[];
    		while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
    			if (!empty($results['hits']['total'])):
	    			foreach ($results['hits']['hits'] as $key => $object):
		    			if($object['inner_hits']['initiative_details']['hits']['total'] > 0 ):
		    			$row 							=		(object)$object['_source'];
		    			foreach($object['inner_hits']['initiative_details']['hits']['hits'] as $skey => $detail):
			    			$detail										=		(object)$detail['_source'];
			    			$innerdoc	=	$object['inner_hits']['initiative_details']['hits']['hits'][0]['inner_hits']['initiative_details.donations']??[];
				    		if($innerdoc['hits']['total'] > 0):
				    			foreach($innerdoc['hits']['hits'] as $key => $donation):
				    				$donation									=		(object)$donation['_source'];
				    				$robject                       	 			=  	 	new stdClass();
					    			$robject->donation_id           			=   	(int)$donation->donation_id;
					    			$robject->initiative_id         			=   	(int)$row->initiative_id;
					    			$robject->title                				=   	(string) $this->common->entityDecode($row->title);
					    			$robject->microgive_initiative_detail_id   	=   	(int)$detail->microgive_initiative_detail_id;
				    				$robject->currency_id            			=   	(int)$donation->currency_id;
				    				$robject->abbreviation           			=   	(string)$objCurrency[$donation->currency_id]??'';
				    				$robject->amount                 			=   	(int)$donation->amount;
				    				$robject->donated_on                 		=   	(string)$donation->created_on;
				    				$robject->sorter							=		strtotime($donation->created_on);
				    				array_push($arr, $robject);
				    			endforeach;
			    			endif;
			    			endforeach;
		    			endif;
	    			endforeach;
    			endif;
    			$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
		}
    		function cmp($a, $b){
    			return strcmp($b->sorter,$a->sorter);
    		}
    		usort($arr, "cmp");
    		$arr	=	array_slice($arr, $pobject->start, $pobject->end);
    		return $arr;
    	}
    	catch(Exception $e)
    	{ $this->es_error($e);	}
    }

    public function getDistinctMicrogiveDatesByType($obj) {
    	try{
    		$params = [];
    		$params['index']  =   $this->index;
    		$params['type']   =   $this->type;
    		$params['size']   =   1000;
    		$params['scroll'] = '30s';
    		$dates        = [];

    		$params['body']['query']['bool']  = [ 'must' => [   ['term' => ['enabled'=>1]],
    															['term' => ['published'=>1]]]];
    		//check if upcoming or past
    		$range	=  ($obj->event_type == "upcoming")?'gte':'lt';

            if(!empty($obj->project_id)):
                $condition  =   ['term' => ['project_id' => $obj->project_id]];
                array_push($params['body']['query']['bool']['must'], $condition);
            endif;

    		if(!empty($obj->type)):
    			$condition	=	['term' => ['type'=> $obj->type]];
    			array_push($params['body']['query']['bool']['must'], $condition);
    		endif;


    		$condition          = [ 'nested' =>  [  'path'        =>  'initiative_details',
    												'query'       =>  [	'bool' => ['should' => [ ['range' => ['initiative_details.event_end_date' => [ $range => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]] ,
    																							 ['range' => ['initiative_details.event_date' => [ $range => (string)date('Y-m-d') ,"format"=> "yyyy-MM-dd"]]]]]],
    												'inner_hits'  =>  ['from' => 0, 'size'  =>  (int)100 ]]];

    		array_push($params['body']['query']['bool']['must'], $condition);

    		$results  = $this->es_search($params);
    		while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
    			if (!empty($results['hits']['total'])):
    				foreach ($results['hits']['hits'] as $key => $object):
    					if($object['inner_hits']['initiative_details']['hits']['total'] > 0 ):
    						$row 							=		(object)$object['_source'];
    						foreach($object['inner_hits']['initiative_details']['hits']['hits'] as $skey => $detail):
    							$detail				=		(object)$detail['_source'];
    							$date				=		($row->type == "V")? $detail->event_date :  $detail->event_end_date;
    							array_push($dates, $date);
    						endforeach;
    					endif;
    				endforeach;
    			endif;
    			$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
    		}
    		function date_sort($a, $b) {
    			return strtotime($a) - strtotime($b);
    		}
    		$dates  = array_values(array_unique($dates));
    		usort($dates, "date_sort");
    		return  $dates;
    	}
    	catch(Exception $e)
    	{ $this->es_error($e);  }
    }


    public function getVolunteerHours($pobject){
    	try{
    		$params = [];
    		$params['index'] 	= 	$this->index;
    		$params['type']  	= 	$this->type;
    		$params['scroll']  	= 	'30s';
    		$params['size']  	= 	1000;
    		$params['body']['query']['bool']['must']= 	[	['term' 	=> ['enabled' => (int)1 ]] ,['term' => ['published' => (int)1 ]] , ['term' 	=> ['type' => (string)"V" ]],
    														['nested'	=> ['path' => 'initiative_details',
    																'query'	=> [ 'bool'	 => ['must' => [  ['range' => ['initiative_details.event_date' => ['lt' => (string) date("Y-m-d") , "format"=> "yyyy-MM-dd"]]],
												    								[		'nested'		=>	[	'path' => 'initiative_details.participants',
												    										'query'			=> 	['bool' => ['must' => [['term' => ['initiative_details.participants.enabled' => (int)1]] , ['term' => ['initiative_details.participants.employee_id' => (int)$pobject->employee_id]] ]]],
												    										'inner_hits'	=>	['from'	=>	(int)0, 'size'=>1]
												    								]]]]],
    																		'inner_hits'	=>	['from'	=>	(int)0, 'size'	=>	(int)1000,  'sort'=>[ ['initiative_details.event_date' => ['order' => 'desc']],
    																		['initiative_details.time_range_to' => ['order' => 'desc']]]]
    														]]];
    		if(!empty($pobject->current)):
    			$condition =	['range' => ['initiative_details.event_date' => ['gte' => (string)$pobject->from , "format"=> "yyyy-MM-dd"] ]];
    			array_push($params['body']['query']['bool']['must'][3]['nested']['query']['bool']['must'],$condition);
    		endif;
    		$results		=	$this->es_search($params);
    		$common			= 	new Common();
    		$arr			=	[];
    		$robj			=	new stdClass();
    		$robj->hours	=	"";
    		$robj->count	=	0;
    		$total_seconds	=	0;

    		while (isset($results['hits']['hits']) && count($results['hits']['hits']) > 0) {
    			if (!empty($results['hits']['total'])):
    			foreach ($results['hits']['hits'] as $key => $object):
	    			if($object['inner_hits']['initiative_details']['hits']['total'] > 0 ):
	    			$row 							=		(object)$object['_source'];
		    			foreach($object['inner_hits']['initiative_details']['hits']['hits'] as $skey => $detail):
		    				$detail										=		(object)$detail['_source'];
		    				$innerdoc	=	$object['inner_hits']['initiative_details']['hits']['hits'][0]['inner_hits']['initiative_details.participants']??[];
			    			if($innerdoc['hits']['total'] > 0):
				    			$robject                       	 			=  	 	new stdClass();
				    			$robject->event_date             			=   	(string)$detail->event_date;
				    			$robject->time_range_from        			=   	(string)$detail->time_range_from;
				    			$robject->time_range_to          			=   	(string)$detail->time_range_to;
				    			$ts1                						=   	new DateTime($detail->time_range_from);
				    			$ts2               		 					=   	new DateTime($detail->time_range_to);
				    			$interval           						=   	$ts1->diff($ts2);
				    			$robject->hours     						=   	(int)$interval->format('%h');
				    			$robject->minutes    						=   	(int)$interval->format('%i');
				    			$total_seconds     							+=  	$common->explode_time($robject->hours,$robject->minutes);
				    			$robject->sorter							=		strtotime($detail->event_date." ".$detail->time_range_to);
			    				array_push($arr, $robject);
		    				endif;
		    			endforeach;
	    			endif;
    			endforeach;
    			endif;
    			$results = $this->es_scroll(["scroll_id" => $results['_scroll_id'], "scroll" => "30s"]);
    		}
    		$robj->hours	= 	$common->second_to_hhmm($total_seconds);
    		$robj->count	=	count($arr);
    		return $robj;
    	}
    	catch(Exception $e)
    	{ $this->es_error($e);	}
    }

    public function getDonationCount($pobject){
    	try{
    		$params = [];
    		$params['index'] 	= 	$this->index;
    		$params['type']  	= 	$this->type;
    		$params['size']  	= 	0;
    		$params['body']['aggs']['donations']		=	[	'nested' =>  [ 'path'=> 'initiative_details.donations'],
    															'aggs' => [ 'donation_count' => [ 'filter' => [  'bool' => ['must' => [['match' => ['initiative_details.donations.employee_id' => (int)$pobject->employee_id]]]]],
    																								'aggs' => ['donation_employee' => ['terms' => [ 'field' => 'initiative_details.donations.employee_id']]]



    															]]];
    		if(!empty($pobject->current)):
    			$condition =	['range' => ['initiative_details.donations.created_on' => ['gte' => (string)$pobject->from , "format"=> "yyyy-MM-dd"] ]];
    			array_push($params['body']['aggs']['donations']['aggs']['donation_count']['filter']['bool']['must'],$condition);
    		endif;
    		$result		=	$this->es_search($params);
    		return 	(!empty($result['aggregations']['donations']['donation_count']['doc_count']))?$result['aggregations']['donations']['donation_count']['doc_count']:0;
    	}
    	catch(Exception $e)
    	{ $this->es_error($e);	}
    }

    public function getVolunteerDates($pobject){
    	try{
    		$params = [];
    		$params['index'] 	= 	$this->index;
    		$params['type']  	= 	$this->type;
    		$params['size']  	= 	0;
    		$range				=	($pobject->volunteer_date == "past")?'lt':'gte';
    		$params['body']['query']['bool']['must']= 	[	['term' 	=> ['enabled' => (int)1 ]] ,['term' => ['published' => (int)1 ]] , ['term' 	=> ['type' => (string)"V" ]]];
    		$params['body']['aggs']['details']		=	[	'nested' =>  [ 'path'=> 'initiative_details'],
    														'aggs' => [ 'details_dates' => [ 'filter' => [  'bool' => ['must' => ['range' => ['initiative_details.event_date' => [ $range => (string) date("Y-m-d") , "format"=> "yyyy-MM-dd"  ]]]]],
    																						 'aggs' => ['participants' => [ 	'nested' => ['path' => 'initiative_details.participants'],
    																						 									'aggs' => [ 'participated'   =>   [ 'filter' =>  ['bool' =>['must' => [['term' => ['initiative_details.participants.employee_id' => (int)$pobject->employee_id ]],['term' =>['initiative_details.participants.enabled'=> 1 ]] ]]],
    																						 											'aggs' => ['detail_aggs' =>  [ 'reverse_nested' => ['path' => 'initiative_details'] ,
    																						 																			'aggs' => ['detail_distinct' => ['terms' => ['field' => 'initiative_details.event_date']]]]]
    																						 									]]
    																						 							 ]]
    													],
    														]];
    		$date				=	"";
    		$result				=	$this->es_search($params);
    		$date_buckets 		=	(!empty($result['aggregations']['details']['details_dates']['participants']['participated']['detail_aggs']['detail_distinct']['buckets']))?$result['aggregations']['details']['details_dates']['participants']['participated']['detail_aggs']['detail_distinct']['buckets']:[];

    		if(!empty($date_buckets)):
    			$date_buckets	=	array_column($date_buckets,"key_as_string","key");
    			ksort($date_buckets);
    			$date_buckets	=	array_values($date_buckets);
    			$date	=	($pobject->volunteer_date == "past")?end($date_buckets):$date_buckets[0];
    		endif;
    		return $date;
    	}
    	catch(Exception $e)
    	{ $this->es_error($e);	}
    }
}
?>
