<?php
class Search extends CI_Model 
{
	public function getDetails($uname, $passwd)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$query = $this->_DB_LMS->get_where('authentication', array('username' => $uname, 'password' => $passwd));
		return $query->result_array();
	}
	public function getBookstoreInvoiceDetails($inNum)
	{
		$this->_DB_BOOK = $this->load->database('bookstore', TRUE);
		$this->_DB_BOOK->select('*');
		$this->_DB_BOOK->from('ps_orders');
		$this->_DB_BOOK->join('ps_customer', 'ps_customer.id_customer = ps_orders.id_customer');
		$this->_DB_BOOK->where('reference', $inNum); 
		$query = $this->_DB_BOOK->get();
		return $query->result_array();
	}
	public function getFedenaInvoiceDetails($StudentID, $FeeCollectionID)
	{
		$this->_DB_FED = $this->load->database('fedena', TRUE);
		$this->_DB_FED->select('*');
		$this->_DB_FED->from('finance_fees');
		$this->_DB_FED->join('students', 'finance_fees.student_id = students.id');
		$this->_DB_FED->join('finance_fee_collections', 'finance_fee_collections.id = finance_fees.fee_collection_id');
		$this->_DB_FED->where('student_id', $StudentID); 
		$this->_DB_FED->where('fee_collection_id', $FeeCollectionID); 
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
				'payment_mode' => 'Internet Banking',
				'school_id' => $school_id,
				'user_id' => '1',
				


				);
		$this->_DB_FED->insert('finance_transactions', $paymentData); 
		return true;



	}
	public function updateBookstoreOrderDetails($inNum, $transactionId, $amount, $paymentDate)
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
				'payment_method' => 'Banco Popular Internet Banking',
				'transaction_id' => $transactionId,
				'date_add' => $paymentDate,

				);
		$this->_DB_BOOK->insert('ps_order_payment', $paymentData); 
		return true;



	}
	public function updateLMS($inNum='', $transactionId='', $amount='', $paymentDate='', $app='', $customer_id='', $StudentID='', $FeeCollectionID='')
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$paymentData = array(
				'invoice_number' => $inNum,
				'app_name' => $app ,  
				'txn_id' => $transactionId,
				'amount' => $amount,
				'payment_date' => $paymentDate,
				'student_id' => $StudentID,
				'fee_collection_id' => $FeeCollectionID,
				'customer_id' => $customer_id		

				);
		$this->_DB_LMS->insert('payment_details', $paymentData); 


	}
	public function getCustomerId($inNum)
	{
		$this->_DB_BOOK = $this->load->database('bookstore', TRUE);
		$query = $this->_DB_BOOK->get_where('ps_orders', array('reference' => $inNum));
		return $query->result_array();
	}
	public function getFeeAmount($StudentID, $FeeCollectionID)
	{
		$this->_DB_FED = $this->load->database('fedena', TRUE);
		$query = $this->_DB_FED->get_where('finance_fees', array('student_id' => $StudentID, 'fee_collection_id' => $FeeCollectionID));
		return $query->result_array();

	}
	public function getMaxReceiptNo($finance_type){
		$this->_DB_FED = $this->load->database('fedena', TRUE);
		$this->_DB_FED->select('*');
		$this->_DB_FED->from('finance_transactions');
		$this->_DB_FED->where('finance_type', $finance_type);		
		$this->_DB_FED->order_by('id', 'DESC');
		$this->_DB_FED->limit('1');
		$query = $this->_DB_FED->get();
		return $query->result_array();
	}
	public function checkTransactionExist($txn_id)
	{
		$this->_DB_LMS = $this->load->database('default', TRUE);
		$this->_DB_LMS->select('*');
		$this->_DB_LMS->from('payment_details');
		$this->_DB_LMS->where('txn_id', $txn_id);		
		$query = $this->_DB_LMS->get();
		return $query->result_array();
	}


}