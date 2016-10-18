<?php
// Class name : NotifyPartner
// Description : required to notify the partner about the booking

// Method used : CURL to connect partner URL and PUT data.
// Data format : currently partner is require to send XML data.

/* Usage : 
	$obj = new NotifyPartner();
	$obj -> init(URL,array('timeout'=>120,'is_put'=>false));
	$data=array('partnerIdentifier'=>1123011,
				'dateBooked'=>'2013-02-01',
				'timeBooked'=>'12:30:11',
				'dateOfEvent'=>'2013-02-12',
				'timeOfEvent'=>'18:30:00',
				'numberOfTickets'=>'4',
				'location'=>'Showcase, London',
				'samId'=>'U10001001',
				'bookingId'=>'BKG-103-209-U2090BX-09325');
				
	$obj -> set_data($data);

	$obj -> notify();

*/
class NotifyPartner 
{
	private  $notify_url,
			 $timeout=60, // default timeout for the call
			 $return_transfer=true, // get output in string 
			 $is_put=true,   // Http method to use
			 $xml_data,$json_data, // set the XML/Json data
			 $data_len, // set the data length
			 $ssl_verify = false, // Do not verify SSL certificate
			 $host_verify = false,  // do not verify host
 			 $peer_verify = false; // do not verify peer's certificate
	private $curl_handle;
	public $execution_time ; 
	
	
	function __construct()
	{
	}  // constructor ends;
	
	
	public function init($notify_url,$settings=array())
	{
		//  Initialize CURL handle 
		$this->curl_handle  = curl_init();	

		$this->notify_url = $notify_url;
		//TEST SERVER "https://stage-origin-*******.******.com/rest/partner/1715446/treats/1715325/bookings")
		//LIVE SERVER "https://********.********.com/rest/partner/3149052/treats/$treat_id/bookings");
		
		foreach($settings as $key => $value)
		{
			$this->$key = $value; // set settings 
		}
		
	}  // init function ends
	
	public function set_data($data_array,$method='xml')
	{
		if($method=='xml')
			$this->set_xml_data($data_array);
		else	
			$this->set_json_data($data_array);
	}
	
	private function set_xml_data($data) // takes xmldata as an array
	{
		$temp = array();
		foreach($xmldata as $key=>$value)
		{
			$temp[] = '<'.$key.'>'.$value.'</'.$key.'>'; 
		}
		$this->xml_data = implode('',$temp); // create xml data string 
		// example string provided by partner
		//<redemptionDVO><partnerIdentifier>XX3149052</partnerIdentifier><dateBooked>2013-02-01</dateBooked><timeBooked>12:30:11</timeBooked><dateOfEvent>2013-02-12</dateOfEvent><timeOfEvent>18:30:00</timeOfEvent><numberOfTickets>4</numberOfTickets><location>Showcase, London</location><samId>U10001001</samId><bookingId>BKG-103-209-U2090BX-09325</bookingId></redemptionDVO>
		
	}
	
	private function set_json_data($data)
	{
		$this->json_data = json_encode($data);	// create json string
	}
	
	public function notify()
	{
		if(!$this->curl_handle)
			return ERROR_CODE_NO_URL; // defined in common error_code.php file
			
		curl_setopt($this->curl_handle, CURLOPT_URL, $this->notify_url);
		
		curl_setopt($this->curl_handle,CURLOPT_TIMEOUT,$this->timeout);
		curl_setopt($this->curl_handle,CURLOPT_RETURNTRANSFER,$this->return_transfer);
		curl_setopt($this->curl_handle,CURLOPT_PUT,$this->is_put);
		curl_setopt($this->curl_handle,CURLOPT_POSTFIELDS,$this->xml_data);
		curl_setopt($this->curl_handle, CURLOPT_SSL_VERIFYPEER, $this->peer_verify);
		curl_setopt($this->curl_handle, CURLOPT_SSL_VERIFYHOST, $this->host_verify);
		curl_setopt($this->curl_handle, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PUT', "Content-length: ".strlen($this->xml_data))); 
   
		/*  Execute the request and also time the transaction */
		$start = array_sum(explode(' ', microtime()));
		$result = curl_exec($this->curl_handle);
		$stop = array_sum(explode(' ', microtime()));
		
	   /*  Check for errors */
		
		$returnCode = '';
		if ( curl_errno($this->curl_handle) ) 
		{
			$returnCode['error'] = 'cURL ERROR -> ' . curl_errno($this->curl_handle) . ': ' . curl_error($this->curl_handle);
		} else 
		{
			$returnCode = (int)curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
		}
		$returnCode['time'] = $this->execution_time;
		
		/*  Close the handle  */
		curl_close($this->curl_handle);
		
		/*  Output the results and time */
		return  $returnCode ;
		
	}  // Notify function ends
	
} // class ends