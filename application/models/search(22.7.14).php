<?php
class Search extends CI_Model 
{
	public function getDetails($uname, $passwd)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$query = $this->_DB_LMS->get_where('authentication', array('username' => $uname, 'password' => $passwd));
		return $query->result_array();
	}
	public function getStudent($uniqueID)
	{
		$this->_DB_FED = $this->load->database('fedena', TRUE);
		$query = $this->_DB_FED->get_where('students', array('admission_no' => $uniqueID));
		return $query->result_array();
	}
	public function checkStudent($uniqueID)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$query = $this->_DB_LMS->get_where('users', array('admission_no' => $uniqueID));
		return $query->result_array();
	}
	public function updateStudent($studentDetails, $uniqueID)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$studentData = array(
				'student_id' => $studentDetails[0]['id'],
				'admission_no' => $studentDetails[0]['admission_no'] ,  
				'customer_id' => '',
				'first_name' => $studentDetails[0]['first_name'],
				'middle_name' => $studentDetails[0]['middle_name'],
				'last_name' => $studentDetails[0]['last_name'],
				'address_line1' =>$studentDetails[0]['address_line1'],
				'address_line2' => $studentDetails[0]['address_line2'],
				'city' => $studentDetails[0]['city'],  
				'state' => $studentDetails[0]['state'],
				'pin_code' => $studentDetails[0]['pin_code'],
				'country_id' => $studentDetails[0]['country_id'],
				'phone1' => $studentDetails[0]['phone1'],
				'phone2' => $studentDetails[0]['phone2'],
				'email' => $studentDetails[0]['email'],
				'created_at' => $studentDetails[0]['created_at'],
				'updated_at' => $studentDetails[0]['updated_at'],
				'school_id' => $studentDetails[0]['school_id']
				);
		$this->_DB_LMS->where('admission_no', $uniqueID);
		$this->_DB_LMS->update('users', $studentData); 
	}
	public function insertStudent($studentDetails)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$studentData = array(
				'student_id' => $studentDetails[0]['id'],
				'admission_no' => $studentDetails[0]['admission_no'] ,  
				'customer_id' => '',
				'first_name' => $studentDetails[0]['first_name'],
				'middle_name' => $studentDetails[0]['middle_name'],
				'last_name' => $studentDetails[0]['last_name'],
				'address_line1' =>$studentDetails[0]['address_line1'],
				'address_line2' => $studentDetails[0]['address_line2'],
				'city' => $studentDetails[0]['city'],  
				'state' => $studentDetails[0]['state'],
				'pin_code' => $studentDetails[0]['pin_code'],
				'country_id' => $studentDetails[0]['country_id'],
				'phone1' => $studentDetails[0]['phone1'],
				'phone2' => $studentDetails[0]['phone2'],
				'email' => $studentDetails[0]['email'],
				'created_at' => $studentDetails[0]['created_at'],
				'updated_at' => $studentDetails[0]['updated_at'],
				'school_id' => $studentDetails[0]['school_id'],
				'app_id' => FEDENA_APP_ID
				);
		$this->_DB_LMS->insert('users', $studentData); 
	}
	public function getFedenaInvoiceDetails($StudentID)
	{
		$this->_DB_FED = $this->load->database('fedena', TRUE);
		$this->_DB_FED->select('finance_fees.id,finance_fees.balance,finance_fees.school_id,finance_fees.fee_collection_id,students.admission_no,students.first_name,students.middle_name,students.last_name');
		$this->_DB_FED->from('finance_fees');
		$this->_DB_FED->join('students', 'finance_fees.student_id = students.id');
		$this->_DB_FED->where('student_id', $StudentID); 
		$this->_DB_FED->where('is_paid', '0'); 
		$query = $this->_DB_FED->get();		
		return $query->result_array();
	}
	public function getBookstoreInvoiceDetails($inNum)
	{
		$this->_DB_BOOK = $this->load->database('bookstore', TRUE);
		$this->_DB_BOOK->select('ps_orders.id_order, ps_orders.id_customer, ps_orders.total_paid, ps_customer.firstname, ps_customer.lastname');
		$this->_DB_BOOK->from('ps_orders');
		$this->_DB_BOOK->join('ps_customer', 'ps_customer.id_customer = ps_orders.id_customer');
		$this->_DB_BOOK->where('reference', $inNum); 
		$this->_DB_BOOK->where('current_state', BANCO_POPULAR_STATUS); 
		$query = $this->_DB_BOOK->get();
		return $query->result_array();
	}
	public function getCustomer($customerID)
	{
		$this->_DB_BOOK = $this->load->database('bookstore', TRUE);
		$this->_DB_BOOK->select('ps_customer.id_customer, ps_customer.firstname, ps_customer.lastname, ps_customer.email, ps_customer.date_add, ps_customer.date_upd, ps_customer.id_shop, ps_address.address1, ps_address.address2, ps_address.city, ps_address.id_state, ps_address.postcode, ps_address.id_country, ps_address.phone, ps_address.phone_mobile');
		$this->_DB_BOOK->from('ps_customer');
		$this->_DB_BOOK->join('ps_address', 'ps_customer.id_customer = ps_address.id_customer');
		$this->_DB_BOOK->where('ps_customer.id_customer', $customerID); 
		$this->_DB_BOOK->where('ps_customer.active', '1'); 
		$this->_DB_BOOK->where('ps_customer.deleted', '0');
		$this->_DB_BOOK->order_by('ps_address.id_address', 'DESC');
		$this->_DB_BOOK->limit('1');
		$query = $this->_DB_BOOK->get();
		return $query->result_array();
	}
	public function checkCustomer($customerID)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$query = $this->_DB_LMS->get_where('users', array('customer_id' => $customerID));
		return $query->result_array();
	}
	public function updateCustomer($customerDetails, $customerID)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$customerData = array(
				'student_id' => '',
				'admission_no' => '',  
				'customer_id' => $customerID,
				'first_name' => $customerDetails[0]['firstname'],
				'middle_name' => '',
				'last_name' => $customerDetails[0]['lastname'],
				'address_line1' =>$customerDetails[0]['address1'],
				'address_line2' => $customerDetails[0]['address2'],
				'city' => $customerDetails[0]['city'],  
				'state' => $customerDetails[0]['id_state'],
				'pin_code' => $customerDetails[0]['postcode'],
				'country_id' => $customerDetails[0]['id_country'],
				'phone1' => $customerDetails[0]['phone'],
				'phone2' => $customerDetails[0]['phone_mobile'],
				'email' => $customerDetails[0]['email'],
				'created_at' => $customerDetails[0]['date_add'],
				'updated_at' => $customerDetails[0]['date_upd'],
				'school_id' => $customerDetails[0]['id_shop'],
				'app_id' => BOOKSTORE_APP_ID
				);
		$this->_DB_LMS->where('customer_id', $customerID);
		$this->_DB_LMS->update('users', $customerData); 
	}
	public function insertCustomer($customerDetails)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$customerData = array(
				'student_id' => '',
				'admission_no' => '',  
				'customer_id' => $customerDetails[0]['id_customer'],
				'first_name' => $customerDetails[0]['firstname'],
				'middle_name' => '',
				'last_name' => $customerDetails[0]['lastname'],
				'address_line1' =>$customerDetails[0]['address1'],
				'address_line2' => $customerDetails[0]['address2'],
				'city' => $customerDetails[0]['city'],  
				'state' => $customerDetails[0]['id_state'],
				'pin_code' => $customerDetails[0]['postcode'],
				'country_id' => $customerDetails[0]['id_country'],
				'phone1' => $customerDetails[0]['phone'],
				'phone2' => $customerDetails[0]['phone_mobile'],
				'email' => $customerDetails[0]['email'],
				'created_at' => $customerDetails[0]['date_add'],
				'updated_at' => $customerDetails[0]['date_upd'],
				'school_id' => $customerDetails[0]['id_shop'],
				'app_id' => BOOKSTORE_APP_ID
				);
		$this->_DB_LMS->insert('users', $customerData); 
	}
	public function getMaxReceiptNo($finance_type){
		$this->_DB_FED = $this->load->database('fedena', TRUE);
		$this->_DB_FED->select('receipt_no');
		$this->_DB_FED->from('finance_transactions');
		$this->_DB_FED->where('finance_type', $finance_type);		
		$this->_DB_FED->order_by('id', 'DESC');
		$this->_DB_FED->limit('1');
		$query = $this->_DB_FED->get();
		return $query->result_array();
	}	
	public function updateFedenaFeeDetails($StudentID, $FeeCollectionID, $transactionId, $amount, $paymentDate, $inNum, $isPaid, $due_amt, $title, $finance_id, $school_id, $receipt_no)
	{
		$this->_DB_FED = $this->load->database('fedena', TRUE);
		$data = array(
               'is_paid' => $isPaid,
               'balance' => $due_amt,
               'updated_at' => $paymentDate
            );
		$this->_DB_FED->where('student_id', $StudentID);
		$this->_DB_FED->where('fee_collection_id', $FeeCollectionID);
		$this->_DB_FED->update('finance_fees', $data); 

		$paymentData = array(
				'title' => $title,
				'amount' => $amount ,   
				'category_id' => '3',
				'created_at' => $paymentDate,
				'updated_at' => $paymentDate,
				'transaction_date' => $paymentDate,
				'finance_id' => $finance_id,
				'finance_type' => 'FinanceFee',
				'payee_id' => $StudentID,
				'payee_type' => 'Student',
				'receipt_no' => $receipt_no,
				'payment_mode' => 'BP-Internet Banking',
				'school_id' => $school_id,
				'user_id' => '1',
				);
		$this->_DB_FED->insert('finance_transactions', $paymentData); 
		return true;
	}
	public function updateFedenaFeeDetailsByBHD($StudentID, $FeeCollectionID, $descRef, $amount, $paymentType, $inNum,$canal, $isPaid, $due_amt, $title, $finance_id, $school_id, $receipt_no)
	{
		$this->_DB_FED = $this->load->database('fedena', TRUE);
		$data = array(
               'is_paid' => $isPaid,
               'balance' => $due_amt,
               'updated_at' => date("Y-m-d H:i:s")
            );
		$this->_DB_FED->where('student_id', $StudentID);
		$this->_DB_FED->where('fee_collection_id', $FeeCollectionID);
		$this->_DB_FED->update('finance_fees', $data); 
        
		$paymentData = array(
				'title' => $title,
				'amount' => $amount ,   
				'payment_mode' => 'BHD- '.$paymentType,
				'category_id' => '3',
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s"),
				'transaction_date' => date("Y-m-d H:i:s"),
				'finance_id' => $finance_id,
				'finance_type' => 'FinanceFee',
				'payee_id' => $StudentID,
				'payee_type' => 'Student',
				'receipt_no' => $receipt_no,
				'school_id' => $school_id,
				'user_id' => '1'
				);
		$this->_DB_FED->insert('finance_transactions', $paymentData); 
		return true;
	}
	public function getMaxLMSTxnId()
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$this->_DB_LMS->select('lms_txn_id');
		$this->_DB_LMS->from('payment_details');
		$this->_DB_LMS->order_by('id', 'DESC');
		$this->_DB_LMS->limit('1');
		$query = $this->_DB_LMS->get();
		return $query->result_array();
	}
	public function updateLMS($inNum='', $app='', $transactionId='', $lms_txn_id = '', $amount='', $paymentDate='', $StudentID='', $customer_id='', $paymentType='', $canal='', $bank_id='')
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$paymentData = array(
				'invoice_number' => $inNum,
				'app_id' => $app ,  
				'txn_id' => $transactionId,
				'lms_txn_id' => $lms_txn_id,
				'amount' => $amount,
				'payment_date' => $paymentDate,
				'student_id' => $StudentID,
				'customer_id' => $customer_id,
				'payment_type' => $paymentType,
				'canal' => $canal,				
				'bank_id' => $bank_id	 
				);
		$this->_DB_LMS->insert('payment_details', $paymentData); 
		return true;
	}
	
	public function updateBookstoreOrderDetails($inNum, $transactionId, $amount, $paymentDate, $paymentType)
	{
		$this->_DB_BOOK = $this->load->database('bookstore', TRUE);
		$data = array(
               'current_state' => '2',
               'invoice_date' => $paymentDate,
               'date_upd' => $paymentDate
            );
		$this->_DB_BOOK->where('reference', $inNum);
		$this->_DB_BOOK->update('ps_orders', $data);

		$paymentData = array(
				'order_reference' => $inNum,
				'id_currency' => '1' ,   //INR
				'amount' => $amount,
				'payment_method' => $paymentType,
				'transaction_id' => $transactionId,
				'date_add' => $paymentDate,
				);
		$this->_DB_BOOK->insert('ps_order_payment', $paymentData); 
		return true;
	}
	public function getCustomerId($inNum)
	{
		$this->_DB_BOOK = $this->load->database('bookstore', TRUE);
		$query = $this->_DB_BOOK->get_where('ps_orders', array('reference' => $inNum));
		return $query->result_array();
	}	
	public function checkTransactionExist($txn_id)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$this->_DB_LMS->select('id, invoice_number, amount');
		$this->_DB_LMS->from('payment_details');
		$this->_DB_LMS->where('txn_id', $txn_id);		
		$query = $this->_DB_LMS->get();
		return $query->result_array();
	}
	public function insertInvoice($inNum, $app='')
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$invoiceData = array(
				'invoice_number' => $inNum,
				'app_id' => $app
				);
		$this->_DB_LMS->insert('invoices', $invoiceData); 
	}
	public function updateInvoice($inNum, $app, $id)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$invoiceData = array(
				'invoice_number' => $inNum,
				'app_id' => $app
				);
		$this->_DB_LMS->where('id', $id);
		$this->_DB_LMS->update('invoices', $invoiceData); 
	}
	public function searchInvoice($inNum)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$this->_DB_LMS->select('app_id');
		$this->_DB_LMS->from('invoices');
		$this->_DB_LMS->where('invoice_number', $inNum);		
		$query = $this->_DB_LMS->get();
		return $query->result_array();
	}
	public function checkInvoice($inNum)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$this->_DB_LMS->select('id');
		$this->_DB_LMS->from('invoices');
		$this->_DB_LMS->where('invoice_number', $inNum);
		$this->_DB_LMS->where('app_id', FEDENA_APP_ID);
		$query = $this->_DB_LMS->get();
		return $query->result_array();
	}
}