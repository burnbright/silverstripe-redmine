<?php
class RedmineAdmin extends LeftAndMain{
	
	static $url_segment = "support";
	static $url_rule = '/$Action';
	
	static $menu_title = 'Support';
	
	static $url,$username,$password,$apikey;
	static $projectid;
	static $cachetime = 3000; //just under one hour
	static $emailfrom,$emailto;
	
	
	//This is needed until the API supports retrieving enumerations
	static $priorities = array(
			3 => 'Low',
			4 => 'Normal',
			5 => 'High',
			6 => 'Urgent',
			7 => 'Immediate'
	);
	
	static $trackers = array(
		3 => 'Support',
		1 => 'Bug',
		2 => 'Feature'
		
	);
	
	static function set_url($url){
		self::$url = $url;
	}
	
	function set_login($user,$password){
		self::$username = $user;
		self::$password = $password;
	}
	
	function set_api_key($key){
		self::$apikey = $key;
	}
	
	function set_project_id($pid){
		self::$projectid = $pid;		
	}
	
	function set_email_to($email){
		self::$emailto = $email;
	}
	
	function set_email_from($email){
		self::$emailfrom = $email;
	}
	
	/**
	 * Makes a call to API and returns SimpleXML object.
	 */
	protected function apiCall($action,$method = "GET",$data = null,$instantupdate = false){
		
		$cachetime = ($instantupdate) ? 0 : self::$cachetime;
		$service = new RestfulService(self::$url."/",$cachetime);
		
		$service->httpHeader('Accept: application/xml');
		$service->httpHeader('Content-Type: application/x-www-form-urlencoded');
		
		if(self::$apikey){
			$service->basicAuth(self::$apikey,"blah");
		}else{
			$service->basicAuth(self::$username,self::$password);
		}
		
		$response = $service->request($action,$method,$data);
		
		if($method == 'POST')
			return $response;
			
		return $response->simpleXML();
	}
	
	
	/**
	 * Get a dataobject set of project issues
	 * docs http://www.redmine.org/projects/redmine/wiki/Rest_Issues
	 */
	function Issues(){
		
		//TODO: error messages.
		
		$action = 'issues.xml';
		if(self::$projectid){
			$action .= "?project_id=".self::$projectid;
		}
		
		$sxml = $this->apiCall($action);

		$issues = $sxml->xpath('/issues/issue');
		
		$dos = new DataObjectSet();
		
		$cols = array(
			'id',
			'subject',
			'description',
			'done_ratio',
			'estimated_hours'
		);
		
		$datecols = array(
			'created_on',
			'updated_on',
			'start_date',
			'due_date'
		);
		
		$sortablecols = array(
			'project',
			'tracker',
			'status',
			'priority',
			'author',
			'assigned_to'
		);
		
		require_once('classTextile.php');
		$textile = new Textile();	
		foreach($issues as $issue){
			$fields = array();
			foreach($cols as $col){
				$fields[$col] = (string)$issue->{$col};
			}
			
			foreach($sortablecols as $col){
				$fields[$col] = (string)$issue->{$col}['name'];
				$fields[$col."Sort"] = (string)$issue->{$col}['id'];
			}
			
			foreach($datecols as $col){
				$date = new SS_Datetime('col');
				$date->setValue((string)$issue->{$col});
				$fields[$col] = $date;
			}
			
			$fields['description'] = $textile->TextileThis($fields['description']);
			$dos->push(new DataObject($fields));
		}	
		
		return $dos;
		
	}
	
	function NewIssueForm(){
		
		$fields = new FieldSet(
			new TextField('subject','Subject'),
			new DropdownField('tracker_id','Type',self::$trackers),
			new DropdownField('priority_id','Priority',self::$priorities),
			new TextareaField('description','Description')
			
			//TODO: allow attachments
			//TODO: allow setting custom fields
		);
		
		$actions = new FieldSet(
			new FormAction('doSubmit','Submit New Support Request')
		);
		
		$validator = new RequiredFields(
			'subject',
			'priority_id',
			'description'
		);
		
		return new Form($this,'NewIssueForm',$fields,$actions,$validator);
	}
	
	
	/**
	 * http://www.redmine.org/projects/redmine/wiki/RedmineReceivingEmails
	 */
	function doSubmit($data,$form){
		
		if(!self::$emailto){
			user_error('Email is not set for sending issues to.',E_USER_ERROR);
			return;
		}
		
		$body = $data['description'];
		
		if($member = Member::currentUser())
			$body .= "\r\nSubmitted by user: ".$member->getName()." <".$member->Email.">";
		
		
		$body .= "\r\n\r\nProject: ".self::$projectid;
		$body .= "\r\nPriority: ".self::$priorities[$data['priority_id']];
		$body .= "\r\nTracker: ".self::$trackers[$data['tracker_id']];
		$body .= "\r\nStatus: New";
		//TODO, add Tracker (eg: bug, feature etc)
		
		$from = self::$emailfrom; //TODO: this is needed until I find out how to allow anonymous users to add to private projects
		
		$email = new Email($from,self::$emailto,$data['subject'],$body);
		$email->sendPlain();
		
		$form->sessionMessage("The issue has been successfully submitted. It will appear on the issues list soon.","good");
		
		Director::redirectBack();
	}
	
	/**
	 * Submit to the redmine api.
	 */
	function doAPISubmit($data,$form){
		
		unset($data['url']);
		unset($data['SecurityID']);
		unset($data['action_doSubmit']);
		
		if(self::$projectid){
			$data['project_id'] = self::$projectid; 	
		} 
		
		$xmlstr =<<<XML
			<issue>
			</issue>
XML;
		
		$xml_data = new SimpleXMLElement($xmlstr);
		foreach($data as $name => $value){
			$xml_data->addChild($name,$value);
		}
		
		$response = $this->apiCall('issues.xml','POST',$xml_data);//TODO: this isn't working
		
		Director::redirectBack();
		return;
	}
}
?>
