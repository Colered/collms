<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Bookstore extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('UTC');
		$this->load->model('search');
		$this->load->library('errorlog');
        $this->load->controller('invoice');

        $this->load->helper('my_helper');

	}

	public function confirmShipping()
	{
		$uname    = $this->input->get('user');
		$passwd    = $this->input->get('pass');
		$order_ref    = $this->input->get('order_reference');
		$orderstatus    = $this->input->get('status');
		$orderstatus    = 4;


		$authDetails = $this->invoice->_verifyCredentials($uname, $passwd, PAY_TYPE_BOOKSTORE);

		if(!empty($authDetails)) {

		     if($orderstatus > 0 && $orderstatus<>""){

		        if($this->search->checkOrder_Reference($order_ref)){


		                $cur_status = $this->search->getSingleVal('ps_orders','current_state',array('reference'=>$order_ref));
                        if(!$cur_status){
								$response = array(
									'response' => 'shipped',
									'message' => 'Order status has been changed',
								);

                        }else{
								 $response = array(
									'response' => 'already_shipped',
									'message' => 'Order already has been shipped',
								 );
					     }

		        }else{
		             $response = array(
						'response' => 'reference_error',
						'message' => 'Order Reference number does not exist',
					 );

		        }

		     }else{
		         $response = array(
						'response' => 'shipping_status_error',
						'message' => 'Please provide the shipping status',
			     );

				// write message to the log file
				$this->errorlog->lwrite('You can not left the shipping status blank');

		     }

		} else {

			$response = array(
				'response' => 'user_not_found',
				'message' => 'Username/password may be wrong.',
			);

			// write message to the log file
			$this->errorlog->lwrite('An error occurred while trying to authenticate your account with username='.$uname.' and password='.$passwd.'');

		}

		xml_viewpage($response);

	}


}
