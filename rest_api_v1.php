<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
 
// Using the REST library for codeigniter
// library used https://github.com/philsturgeon/codeigniter-restserver
// Library PATH.'/libraries/Rest.php';

require APPPATH.'/libraries/Rrest.php';


// Usage 
/*

To book:
https://www.sitename.com/API/index.php/Rest_api_v1/book

{"event_unique_id":"25900604","SD_id":"29811","samid":"10447NN","email":"mital.ndinfosys@gmail.com","firstname":"Test Fname","lastname":"Test Lastname","tickets":"2","checksum":"CHECKSUM"}

To cancel:
https://www.sitename.com/API/Rest_api_v1/cancel/
{"booking_id":"ABC-001-002-1003-XZD999","tickets":"2"}

Get availability both functions needs event unique id to get list
https://www.sitename.com/API/Rest_api_v1/available/
{"event_unique_id":32134}

Get locations
https://www.sitename.com/API/Rest_api_v1/locations
{"event_unique_id":32134}


Retrieve based on given user id 
https://www.sitename.com/API/Rest_api_v1/retrieve/
{'sam_id':9999}



*/
class Rest_api_v1 extends Rest // This class extends Rest controller and Rest will extend CI controller
{
	private $event_unique_id;	
	private $allowed_ip= array("BLOCKED",'255.255.255.255','254.254.254.252'); // original ips removed for customer privacy
	private $ip0 = "80";
	private $ip1 = "238";
	private $ip2 = array("BLKD","11","10","6","7","8");
	private $enc_key = "Si30U860Ba7d8xo0lpqrQtS10TDxuMmu4vY7"; // agreed with partners
	public $errors,$error_code="API Error Code ";
 	
	function __construct()
	{
		parent::__construct();
		$this->load->model('rest_api_model'); // Load database model	
	}
	private function check_ip() // check IP
	{
		$ip_pos = in_array($_SERVER['REMOTE_ADDR'],$this->allowed_ip);
		if($ip_pos > 0)
			return true;
		
		$ip_part = explode($_SERVER['REMOTE_ADDR']);
		if(in_array($ip_part[2],$this->ip2) && $ip_part[1]==$this->ip1 && $ip_part[0]==$this->ip0)
			return true;
		
		$this->errors['error']= $this->error_code ."1111";
		$this->response( $this->errors, 403);		
	}
	
	private function get_inputvars()
	{
		$inputJSON = file_get_contents('php://input'); // get input from STDIN 
		$event_unique_id = json_decode( $inputJSON, TRUE );
		return $event_unique_id;
	}
  	
	public function available_post()
    {
		$this->check_ip(); 
		$event_unique_id = $this->get_inputvars();
			
		if(empty($event_unique_id))
        {
			$this->errors['error']= $this->error_code ."1001";
			$this->response( $this->errors, 400);
        }
		
		if(is_array($event_unique_id))			
			$this->rest_api_model->set_event_unique_id(implode(",",$event_unique_id));
		else
			$this->rest_api_model->set_event_unique_id($event_unique_id);
		
		$available = $this->rest_api_model->get_available_multi();
		
        if(!empty($available))
        {
            $this->response($available, 200); 
        }
        else
        {
            $this->errors['error']= $this->error_code ."1002";
			$this->response( $this->errors, 400);
        }
    }
    
    public function availble_delete()
    {
    	$this->response(array('status' => 0, 'error' => 'Method not allowed.'), 405); 
    }
    
	public function available_put()
    {
		$this->response(array('status' => 0, 'error' => 'Method not allowed.'), 405);
    }
	
	
	public function locations_post() // sends the list of locations for given event
    {
		$this->check_ip();
		$event_unique_id = $this->get_inputvars();

		if(empty($event_unique_id))
        {
			 $this->errors['error']= $this->error_code ."2001";
			$this->response( $this->errors, 400);
        }
		
		$this->rest_api_model->set_event_unique_id($event_unique_id);
		
		$result = $this->rest_api_model->get_locations();
		
        if(!empty($result))
            $this->response($result, 200); 
        else
		{
			 $this->errors['error']= $this->error_code ."2002";
			$this->response( $this->errors, 400);
		}
    }

	
	public function book_post() // Books the place for event, see the commented list of required parameters
    {
		$this->check_ip();		

		$param = $this->get_inputvars();
		/* 
		{"event_unique_id":"25900604","SD_id":"29811","samid":"10447NN","email":"mital.ndinfosys@gmail.com","firstname":"Test Fname","lastname":"Test Lastname","tickets":"2"}
		/* Validate Parameters */
		
		if(empty($param['checksum']))
		{
			 $this->errors['error']= $this->error_code ."3015";
			 $this->response( $this->errors, 400);
		}
		if(empty($param['samid']))
		{
			 $this->errors['error']= $this->error_code ."3004";
			$this->response( $this->errors, 400);
		}
		
		if(!$this->check_sum($param['samid'],$param['checksum']))
		{
			 $this->errors['error']= $this->error_code ."3014";
			 $this->response( $this->errors, 400);
		}	
		
		if(empty($param['event_unique_id']))
		{
			 $this->errors['error']= $this->error_code ."3001";
			$this->response( $this->errors, 400);
		}

		if(empty($param['SD_id']))
		{
			 $this->errors['error']= $this->error_code ."3003";
			$this->response( $this->errors, 400);
		}		
		
		if(empty($param['email']))
		{
			 $this->errors['error']= $this->error_code ."3005";
			$this->response( $this->errors, 400);
		}
		else
		{
			if(substr(trim($param['email']),-1) == '=' )
				$param['email'] = $this->decrypt_data($param['email']);	
		}
		
        if(empty($param['firstname']))
		{
			 $this->errors['error']= $this->error_code ."3006";
			$this->response( $this->errors, 400);
		}
        else
		{
			if(substr(trim($param['firstname']),-1) == '=' )
				$param['firstname'] = $this->decrypt_data($param['firstname']);	
		}
		if(empty($param['lastname']))
		{
			 $this->errors['error']= $this->error_code ."3007";
			$this->response( $this->errors, 400);
		}
		else
		{
			if(substr(trim($param['lastname']),-1) == '=' )
				$param['lastname'] = $this->decrypt_data($param['lastname']);	
		}
		
        if(empty($param['tickets']))
		{
			 $this->errors['error']= $this->error_code ."3008";
			$this->response( $this->errors, 400);
		}
		
		$this->rest_api_model->set_event_unique_id($param['event_unique_id']);
		
		$film = $this->rest_api_model->get_film();
		if(empty($film))
		{
			$this->errors['error']= $this->error_code ."3002";
			$this->response( $this->errors, 400);
		}
		
		if(!$film['live'] || $film['live_date'] > date('Y-m-d'))
		{
			 $this->errors['error']= $this->error_code ."3011";
			$this->response( $this->errors, 400);
		}		
		
		if($film['max_ticket']< $param['tickets'])
		{
			 $this->errors['error']= $this->error_code ."3009";
			$this->response( $this->errors, 400);
		}
		
		$tickets = $this->rest_api_model->get_available($param['SD_id']);
		if(!$tickets || $tickets < $param['tickets'])
		{
			$this->errors['error']= $this->error_code ."3010";
			$this->response( $this->errors, 400);
		}
		$result=$this->rest_api_model->check_email($param['samid'],$param['email']);
			if(!$result)
			{
				$this->errors['error']= $this->error_code ."3017";
				$this->response( $this->errors, 400);
			}
		
		$uid=$this->rest_api_model->set_user($param['samid'],$param['email'],$param['firstname'],$param['lastname']);					
		
		
		$booked_tickets=$this->rest_api_model->user_event_tickets($uid,$film['id']);
		
		if(($booked_tickets + $param['tickets']) > $film['max_ticket'])
		{
			 $this->errors['error']= $this->error_code ."3012";
			$this->response( $this->errors, 400);
		}		 
		
		$other_ticket_sameday=$this->rest_api_model->user_event_day_tickets($uid,$film['screening_date'],$param['SD_id']);
		if($other_ticket_sameday)
		{
			 $this->errors['error']= $this->error_code ."3013";
			$this->response( $this->errors, 400);
		}
		
		$booking_data = $this->rest_api_model->book_ticket($uid,$param['tickets'],$param['SD_id']);				
		$loc = $this->rest_api_model->location_row($booking_data['location_id']);
		
		$booking_data['film_name'] =$film['film_name']; //film_image,film_release_callout
		$booking_data['brand_name'] =$loc['cinema']; // Address
		$booking_data['location'] =$loc['location'];   // Address
		$booking_data['film_summary'] = substr(strip_tags($film['film_summary']),0,200);  // film
		$booking_data['film_r_call'] = $film['film_release_callout'];   // film
		$booking_data['the_film_image'] = $film['film_image']; // film
		$booking_data['fname'] = $param['firstname']; // film
		$booking_data['email'] = $param['email']; // film
		
		$subject = "Here's your free ticket to see ".$booking_data['film_name'];
		$this->send_email($param['email'],"booking_email",$booking_data,$subject);
		$result = array();
		
		$result['event_unique_id'] = $param['event_unique_id'];
		$result['samid'] = $param['samid'];
		$result['booking_id'] =  $booking_data['booking_id'];
		$result['screening_date'] =  $booking_data['screening_date'];
		$result['screening_time'] =  $booking_data['screening_time'];
		$result['location'] =  $booking_data['location'];
		$result['address'] =  array("address1"=>$loc['address1'],"address2"=>$loc['address2'],"city"=>$loc['city'],"post_code"=>$loc['post_code']);
		$result['post_code'] =  $loc['post_code'];
		$result['tickets'] =  $booking_data['requested_ticket'];
		$result['cert'] =  $film['cert'];
		
		//Booking ID, screening time, unique date, location name, location address, postcode, cert, number of tickets;		
		$this->response($result, 200);
	 
	}
	
	public function cancel_post()
    {
		$this->check_ip();
		$vars = $this->get_inputvars();

		if(empty($vars['booking_id']))
        {
			 $this->errors['error']= $this->error_code ."4001";
			$this->response( $this->errors, 400);
        }
		if(empty($vars['tickets']))
        {
			 $this->errors['error']= $this->error_code ."4002";
			$this->response( $this->errors, 400);
        }
		if($vars['tickets'] < 1 )
		{
			$this->errors['error']= $this->error_code ."4003";
			$this->response( $this->errors, 400);
        }
		
		$row= $this->rest_api_model->get_booking($vars['booking_id']);
		if(!$row)
        {
			$this->errors['error']= $this->error_code ."4004";
			$this->response( $this->errors, 400);
        }
		
		if($row['requested_ticket'] < $vars['tickets'])
		{
			$this->errors['error']= $this->error_code ."4005";
			$this->response( $this->errors, 400);
        }
		
		$this->rest_api_model->update_booking($vars['booking_id'],$vars['tickets'],$row['location_id'],$row['film_id']);
		
		$user = $this->rest_api_model->get_user('',$row['user_id']);
		$film = $this->rest_api_model->get_film($row['film_id']);
		
		$cancel_data['f_name'] = $user['fname'];
		$cancel_data['film_name'] = $film['film_name'];
		
		$subject = "We're sorry you can't come along to our movie screening";
		$this->send_email($user['email'],"cancel_email",$cancel_data,$subject);
		
		$loc = $this->rest_api_model->location_row($row['location_id']);
		
		$result = array();
		$result['booking_id'] =  $vars['booking_id'];
		$result['tickets'] =  $row['requested_ticket']-$vars['tickets'];		
		$result['event_unique_id'] =  $film['event_unique_id'];		
		$result['samid'] = $user['poc'];
		$result['screening_date'] =  $row['screening_date'];
		$result['screening_time'] =  $row['screening_time'];
		$result['location'] =  $loc['location'];
		$result['address'] =  array("address1"=>$loc['address1'],"address2"=>$loc['address2'],"city"=>$loc['city'],"post_code"=>$loc['post_code']);
		$result['post_code'] =  $loc['post_code'];		
		$result['cert'] =  $film['cert'];		
				
        if(!empty($result))
            $this->response($result, 200); 
        else
		{
			 $this->errors['error']= $this->error_code ."2002";
			$this->response( $this->errors, 400);
		}
    }
	
	public function retrieve_post()
    {
		$this->check_ip();
		$vars = $this->get_inputvars();

		if(empty($vars['samid']))
        {
			$this->errors['error']= $this->error_code ."5001";
			$this->response( $this->errors, 400);
        }
		
		$user = $this->rest_api_model->get_user($vars['samid']);
		if(!$user)
        {
			$this->errors['error']= $this->error_code ."5002";
			$this->response( $this->errors, 400);
        }		

		$response['samid'] = $vars['samid'];
		$response['films'] = $this->rest_api_model->get_user_bookings($user['id']);
		if(!$response['films'])
		{	
			$this->errors['error']= $this->error_code ."5003";
			$this->response( $this->errors, 400);
        }		
				
        if(!empty($response))
            $this->response($response, 200); 
        else
		{
			 $this->errors['error']= $this->error_code ."5055";
			$this->response( $this->errors, 400);
		}
    }
	
	public function notify_post()
    {
		$this->check_ip();
		$param = $this->get_inputvars();

		if(empty($param['checksum']))
		{
			 $this->errors['error']= $this->error_code ."6015";
			 $this->response( $this->errors, 400);
		}
		if(empty($param['samid']))
		{
			 $this->errors['error']= $this->error_code ."6004";
			$this->response( $this->errors, 400);
		}
		
		if(!$this->check_sum($param['samid'],$param['checksum']))
		{
			 $this->errors['error']= $this->error_code ."6014";
			 $this->response( $this->errors, 400);
		}	
		
		if(empty($param['event_unique_id']))
		{
			 $this->errors['error']= $this->error_code ."6001";
			$this->response( $this->errors, 400);
		}

		if(empty($param['SD_id']))
		{
			 $this->errors['error']= $this->error_code ."6003";
			$this->response( $this->errors, 400);
		}		
		
		if(empty($param['email']))
		{
			 $this->errors['error']= $this->error_code ."6005";
			$this->response( $this->errors, 400);
		}
		else
		{
			if(substr(trim($param['email']),-1) == '=' )
				$param['email'] = $this->decrypt_data($param['email']);	
					
		}
        if(empty($param['firstname']))
		{
			 $this->errors['error']= $this->error_code ."6006";
			$this->response( $this->errors, 400);
		}
		else
		{
			if(substr(trim($param['firstname']),-1) == '=' )
				$param['firstname'] = $this->decrypt_data($param['firstname']);	
					
		}
			
        if(empty($param['lastname']))
		{
			 $this->errors['error']= $this->error_code ."6007";
			$this->response( $this->errors, 400);
		}
		else
		{
			if(substr(trim($param['lastname']),-1) == '=' )
				$param['lastname'] = $this->decrypt_data($param['lastname']);	
					
		}
		
		$result=$this->rest_api_model->check_email($param['samid'],$param['email']);
		if(!$result)
		{
			$this->errors['error']= $this->error_code ."6017";
			$this->response( $this->errors, 400);
		}
		
		$uid=$this->rest_api_model->set_user($param['samid'],$param['email'],$param['firstname'],$param['lastname']);	
		$ans = $this->rest_api_model->add_to_notify($param['SD_id'],$uid);
		
		if($ans)
		{
			$response['samid'] = $param['samid'];
			$response['event_unique_id'] = $param['event_unique_id'];
			$response['SD_id'] = $param['SD_id'];
			$response['Notification'] = 1;
			$this->response($response, 200); 
		}
		else
		{
			$this->errors['error']= $this->error_code ."6018";
			$this->response( $this->errors, 400);
		}
        
    }
	
	private function send_email($to,$template,$booking_data,$subject)
	{
		$from = "noreply-admin@NOSITENAME.com";

		$this->load->library('email');
		$config = array (
				  'mailtype' => 'html',
				  'charset'  => 'utf-8',
				  'priority' => '1'
				   );
		$this->email->initialize($config);
		$this->email->from($from, 'Sender Name');
		$this->email->to($to);
		
		$this->email->subject($subject);
		
		$message=$this->load->view($template,$booking_data,TRUE);
		
		$this->email->message($message);
		$this->email->send();       
		
	}
	
	private function check_sum($code_original,$chksum)
	{
		// Removed the code for customer privacy
		return true;
		return false;
	}
	
	 // second arguments for mcrypt_create_iv(): MCRYPT_RAND (system random number generator), MCRYPT_DEV_RANDOM (read data from /dev/random) and MCRYPT_DEV_URANDOM (read data from /dev/urandom)? Do they offer different consistent speeds? I wonder if it's because /dev/random (the default random source) is running out of collected entropy; the function will block when it does.
  	
}
 