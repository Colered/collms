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
}