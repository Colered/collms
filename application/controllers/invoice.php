<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Invoice extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('search');
		$this->load->library('errorlog');
		$this->load->helper('my_helper');
	}
	public function VerificarReferencia()
	{
		$uname    = $this->input->get('psUsuario');
		$passwd    = $this->input->get('psPassword');
		$canal    = $this->input->get('psCanal');
		$inNum    = $this->input->get('psReferencia');
		$authDetails = $this->_verifyCredentials($uname, $passwd, PAY_TYPE_BANKPOPULAR);
		if(!empty($authDetails))
		{
			$invoiceDetails = $this->_searchFedena($inNum);
			$invoiceDetails1 = $this->_searchBookstore($inNum);
			$invoice = array();
			if($invoiceDetails['CodigoMensaje'] == '100' && $invoiceDetails1['CodigoMensaje'] == '100')
			{
				$invoice['CodigoMensaje'] = '104';
				$invoice['DescripcionMensaje'] = 'Invoice Not Valid';
				$message = 'Dear Admin<br/>The invoice number - '.$inNum.' exists in both databases. Please check.<br/><br/>Thanks,<br/>Banco Popular.';
				$subject = 'Duplicate Invoice Found';
				$this->_sendEmail($subject, $message);

				// write message to the log file
				$this->errorlog->lwrite('The invoice number - '.$inNum.' exists in both databases Bookstore and Fedena.');

			}elseif($invoiceDetails['CodigoMensaje'] == '102' && $invoiceDetails1['CodigoMensaje'] == '102'){
				$invoice['CodigoMensaje'] = '104';
				$invoice['DescripcionMensaje'] = 'Invoice Not Found';
				$message = 'Dear Admin<br/>The invoice number - '.$inNum.' does not found in any database. Please check.<br/><br/>Thanks,<br/>Banco Popular.';
				$subject = 'Invoice Not Found';
				$this->_sendEmail($subject, $message);

				// write message to the log file
				$this->errorlog->lwrite('The invoice number - '.$inNum.' does not found in any database Bookstore and Fedena.');

			}elseif($invoiceDetails['CodigoMensaje'] == '100' && $invoiceDetails1['CodigoMensaje'] != '100'){
				$invoice = $invoiceDetails;
			}elseif($invoiceDetails['CodigoMensaje'] != '100' && $invoiceDetails1['CodigoMensaje'] == '100'){
				$invoice = $invoiceDetails1;
			}elseif($invoiceDetails['CodigoMensaje'] == '103' && $invoiceDetails1['CodigoMensaje'] != '100'){
				$invoice['CodigoMensaje'] = '104';
				$invoice['DescripcionMensaje'] = 'Invoice Not Valid';

				// write message to the log file
				$this->errorlog->lwrite('The invoice number - '.$inNum.' exist in our record but there is no due amount against this invoice.');

			}
		}else{
				$invoice = array(
				'CodigoMensaje' => '101',
				'DescripcionMensaje' => 'Authentication Failed',
				);

				// write message to the log file
				$this->errorlog->lwrite('An error occurred while trying to authenticate your account with username='.$uname.' and password='.$passwd.'');
		}


		xml_viewpage($invoice);
	}
	/* Function to authenticate API Call */
	public function _verifyCredentials($uname, $passwd, $type)
	{
		$authDetails = $this->search->getDetails($uname, $passwd, $type);
		return $authDetails;
	}
	/* Below function will search the invoice number in Bookstore. If found return the details otherwise error*/
	public function _searchBookstore($inNum)
	{
		$orderDetails = $this->search->getBookstoreInvoiceDetails($inNum);
		if($orderDetails && sizeof($orderDetails) == 1)
		{
			$customerDetails = $this->search->getCustomer($orderDetails[0]['id_customer']);
			$chkcustomer = $this->search->checkCustomer($orderDetails[0]['id_customer']);
			if($chkcustomer)
			{
				$this->search->updateCustomer($customerDetails,$orderDetails[0]['id_customer']);
			}
			else
			{
				$this->search->insertCustomer($customerDetails);
			}
			$app = BOOKSTORE_APP_ID;
			$this->search->insertInvoice($inNum,$app);
			$invoiceDetails = '';
			$invoiceDetails = array(
			'CodigoMensaje' => '100',
			'DescripcionMensaje' => 'Invoice Found',
			'NumeroReferencia' => $inNum,
			'TipoReferencia' => 'Books Order Payment',
			'IDDocumento' => $orderDetails[0]['id_customer'],
			'NombreCliente' => $orderDetails[0]['firstname'].''.$orderDetails[0]['lastname'],
			'MontoImpuesto' => '0.00',
			'MontoTotal' => $orderDetails[0]['total_paid'],
			'Moneda' => 'USD',
			'Detalles' => ''
			);
		}else{
			$invoiceDetails = array(
				'CodigoMensaje' => '102'
			);
		}
	return $invoiceDetails;
	}
	/* Below function will search the invoice number in Fedena. If found return the details otherwise error*/
	public function _searchFedena($inNum)
	{
		$studentDetails = $this->search->getStudent($inNum);
		if($studentDetails)
		{
			$StudentID = $studentDetails[0]['id'];
			$feeDetails = $this->search->getFedenaInvoiceDetails($StudentID);
			if($feeDetails)
			{
				$chkstudent = $this->search->checkStudent($inNum);
				if($chkstudent)
				{
					$this->search->updateStudent($studentDetails,$inNum);
				}
				else{
					$this->search->insertStudent($studentDetails);
				}
				$count = sizeof($feeDetails);
				$balance = '';
				foreach($feeDetails as $fees)
				{
					$balance = $balance + $fees['balance'];
				}
				$due_blnc = $balance - $feeDetails[$count-1]['balance'];
				$app = FEDENA_APP_ID;
				$prevDetails = $this->search->checkInvoice($inNum);
				if($prevDetails)
					$this->search->updateInvoice($inNum, $app, $prevDetails[0]['id']);
				else
					$this->search->insertInvoice($inNum, $app);
				$invoiceDetails = array(
				'CodigoMensaje' => '100',
				'DescripcionMensaje' => 'Invoice Found',
				'NumeroReferencia' => $inNum,
				'TipoReferencia' => 'Fee Submission',
				'IDDocumento' => $feeDetails[0]['admission_no'],
				'NombreCliente' => $feeDetails[0]['first_name'].' '.$feeDetails[0]['middle_name'].' '.$feeDetails[0]['last_name'],
				'MontoImpuesto' => '0.00',
				'MontoTotal' => $balance,
				'Moneda' => 'USD',
				'Fecha' => '',
				'Detalles' => $due_blnc
				);
			}else{
					$invoiceDetails = array(
					'CodigoMensaje' => '103'
					);
				}
		}else{
			$invoiceDetails = array(
				'CodigoMensaje' => '102'
				);
		}
		return $invoiceDetails;
	}
	/* Converts the array result into xml */
	function _toxml(SimpleXMLElement $object, array $data)
	{
		foreach ($data as $key => $value)
		{
			if (is_array($value))
			{
				$new_object = $object->addChild($key);
				_toxml($new_object, $value);
			}
			else
			{
				$object->addChild($key, $value);
			}
		}
	}
	public function RegistrarPago()
	{
		$uname    = $this->input->get('psUsuario');
		$passwd    = $this->input->get('psPassword');
		$canal    = $this->input->get('psCanal');
		$inNum    = $this->input->get('psReferencia');
		$transactionId    = $this->input->get('pIdTransaccionBanco');
		$amount = $this->input->get('pValorPagado');
		$paymentDate = $this->input->get('pFechaPago');
		$authDetails = $this->_verifyCredentials($uname, $passwd, PAY_TYPE_BANKPOPULAR);
		if(!empty($authDetails))
		{
			$apps = $this->search->searchInvoice($inNum);
			if($apps[0]['app_id'] == FEDENA_APP_ID)
			{
				$this->_callFedena($inNum, $transactionId, $amount, $paymentDate, $canal);
			}elseif($apps[0]['app_id'] == BOOKSTORE_APP_ID){
				$this->_callBookstore($inNum, $transactionId, $amount, $paymentDate, $canal);
			}else{
				$invoiceDetails = array(
				'CodigoMensaje' => '104',
				'DescripcionMensaje' => 'Invoice Not Found',
				);

				// write message to the log file
				$this->errorlog->lwrite('The invoice number - '.$inNum.' exists in both databases Bookstore and Fedena.');

				xml_viewpage($invoiceDetails);

			}
		}else{
				$invoiceDetails = array(
				'CodigoMensaje' => '101',
				'DescripcionMensaje' => 'Authentication Failed',
				);

				// write message to the log file
				$this->errorlog->lwrite('An error occurred while trying to authenticate your account with username='.$uname.' and password='.$passwd.'');

				xml_viewpage($invoiceDetails);
		}
	}
	//update fee transaction details in fedena as well as LMS
	public function _callFedena($inNum, $transactionId, $amount, $paymentDate, $canal)
	{
		$originalAmount = $amount;
		$studentDetails = $this->search->getStudent($inNum);
		if($studentDetails)
		{
			$StudentID = $studentDetails[0]['id'];
			$feeDetails = $this->search->getFedenaInvoiceDetails($StudentID);
			if($feeDetails)
			{
				$totalBlnc = '';
				foreach($feeDetails as $fees)
				{
					$totalBlnc = $totalBlnc + $fees['balance'];
				}
				if($amount > $totalBlnc)
				{
					$updateDetails = array(
						'CodigoMensaje' => '105',
						'DescripcionMensaje' => 'Amount is greater than invoice amount',
					);

					// write message to the log file
					$this->errorlog->lwrite('Amount is greater than invoice amount');

				}else{
					foreach($feeDetails as $fees)
					{
						if($amount <= $fees['balance'])
						{
							$finance_id = $fees['id'];
							if($amount == $fees['balance'])
							{
								$isPaid = '1';
								$title = 'Receipt No. F'.$finance_id;
							}else{
								$isPaid = '0';
								$title = 'Receipt No. (partial) F'.$finance_id;
							}
							$balance = $fees['balance'];
							$due_amt = round($balance - $amount,2);
							$school_id = $fees['school_id'];
							$receipts = $this->search->getMaxReceiptNo('FinanceFee');
							$receipt_no = $receipts['0']['receipt_no'];
							$receipt_no = $receipt_no + 1;
							$FeeCollectionID = $fees['fee_collection_id'];
							$this->search->updateFedenaFeeDetails($StudentID, $FeeCollectionID, $transactionId, $amount, $paymentDate, $inNum, $isPaid, $due_amt, $title, $finance_id, $school_id, $receipt_no);
							break;
						}
						else{
							$isPaid = '1';
							$balance = $fees['balance'];
							$pending_amt = round($amount - $balance,2);
							$due_amt = '0.00';
							$amount = $balance;
							$finance_id = $fees['id'];
							$title = 'Receipt No. F'.$finance_id;
							$school_id = $fees['school_id'];
							$receipts = $this->search->getMaxReceiptNo('FinanceFee');
							$receipt_no = $receipts['0']['receipt_no'];
							$receipt_no = $receipt_no + 1;
							$FeeCollectionID = $fees['fee_collection_id'];
							$this->search->updateFedenaFeeDetails($StudentID, $FeeCollectionID, $transactionId, $amount, $paymentDate, $inNum, $isPaid, $due_amt, $title, $finance_id, $school_id, $receipt_no);
							$amount = $pending_amt;
						}
					}
					$app = FEDENA_APP_ID;
					$lms_txn_id = $this->search->getMaxLMSTxnId();
					if($lms_txn_id)
						$lms_txn_id = $lms_txn_id[0]['lms_txn_id'] + 1;
					else
						$lms_txn_id = DEFAULT_LMS_TXN_ID;
					$bank_id = BP_BANK_ID;
					if($this->search->updateLMS($inNum, $app, $transactionId, $lms_txn_id, $originalAmount, $paymentDate, $StudentID, '', '', $canal, $bank_id))
					{
						$updateDetails = array(
									'CodigoMensaje' => '100',
									'DescripcionMensaje' => 'Fee Details Updated',
									'NumeroReferencia' => $inNum,
									'NumeroAutorizacion' => $lms_txn_id,
									'IDTransaccionBanco' => $transactionId,
									'ValorPagado' => $originalAmount,
									'Moneda' => 'USD',
									);
					}else{
						$updateDetails = array(
									'CodigoMensaje' => '106',
									'DescripcionMensaje' => 'There is some problem in fee updation',
						);
						// write message to the log file
						$this->errorlog->lwrite('There is some problem in fee updation');

					}
				}
			}else{
				$updateDetails = array(
					'CodigoMensaje' => '103',
					'DescripcionMensaje' => 'No pending fees',
					);
			}
		}else{
				$updateDetails = array(
					'CodigoMensaje' => '102',
					'DescripcionMensaje' => 'No student found',
					);
		}


		xml_viewpage($updateDetails);
	}
	//update fee transaction details in Bookstore as well as LMS
	public function _callBookstore($inNum, $transactionId, $amount, $paymentDate, $canal)
	{
		$orderDetails = $this->search->getBookstoreInvoiceDetails($inNum);
		if($orderDetails){
		if($amount > $orderDetails[0]['total_paid']) {
			$updateDetails = array(
					'CodigoMensaje' => '105',
					'DescripcionMensaje' => 'Amount is greater than invoice amount',
			);

			// write message to the log file
			$this->errorlog->lwrite('Amount is greater than invoice amount');

		} else {
			$paymentType = 'BP - Internet Banking';
			if($this->search->updateBookstoreOrderDetails($inNum, $transactionId, $amount, $paymentDate, $paymentType))
			{
				$app = BOOKSTORE_APP_ID;
				$customers = $this->search->getCustomerId($inNum);
				$customer_id = $customers['0']['id_customer'];
				$lms_txn_id = $this->search->getMaxLMSTxnId();
				if($lms_txn_id)
						$lms_txn_id = $lms_txn_id[0]['lms_txn_id'] + 1;
					else
						$lms_txn_id = DEFAULT_LMS_TXN_ID;
				$bank_id = BP_BANK_ID;
				$this->search->updateLMS($inNum, $app, $transactionId, $lms_txn_id, $amount, $paymentDate, '', $customer_id, '', $canal, $bank_id);
				$updateDetails = array(
				'CodigoMensaje' => '100',
				'DescripcionMensaje' => 'Order Details Updated',
				'NumeroReferencia' => $inNum,
				'NumeroAutorizacion' =>$lms_txn_id,
				'IDTransaccionBanco' => $transactionId,
				'ValorPagado' => $amount,
				'Moneda' => 'USD',
				);
			}else{
				$updateDetails = array(
				'CodigoMensaje' => '106',
				'DescripcionMensaje' => 'Order Details Not Updated',
				);

				// write message to the log file
				$this->errorlog->lwrite('Order Details Not Updated');

			}
		}
		}else{
			$updateDetails = array(
				'CodigoMensaje' => '104',
				'DescripcionMensaje' => 'Invoice Not Valid',
				);

				// write message to the log file
				$this->errorlog->lwrite('The invoice number - '.$inNum.' exist in our record but there is no due amount against this invoice.');

		}


		xml_viewpage($updateDetails);
	}
	//Validating the transactions
	public function ConciliarPagos()
	{
		$uname    = $this->input->get('psUsuario');
		$passwd    = $this->input->get('psPassword');
		$canal    = $this->input->get('psCanal');
		$authDetails = $this->_verifyCredentials($uname, $passwd, PAY_TYPE_BANKPOPULAR);
		if(!empty($authDetails))
		{
			$xmlstring = '<DetalleConsulta>
			<IDTransaccion>111112</IDTransaccion>
			<FechaTransaccion>2014-06-23 15:30:48.000000</FechaTransaccion>
			<Valor>34.75</Valor>
			<Referencia>QREQBPRBM</Referencia>
			</DetalleConsulta>';

			// load as string
			$xml = simplexml_load_string($xmlstring);
			$json = json_encode($xml);
			$transactions = json_decode($json,TRUE);
			$txn_id = $transactions['IDTransaccion'];
			$txn_date = $transactions['FechaTransaccion'];
			$txn_amt = $transactions['Valor'];
			$txn_ref = $transactions['Referencia'];
			$txnDetails = $this->search->checkTransactionExist($txn_id);
			if($txnDetails)
			{
				$inv_no = $txnDetails['0']['invoice_number'];
				$amt = $txnDetails['0']['amount'];
				if($inv_no == $txn_ref && $amt == $txn_amt)
				{
					$invoiceDetails = array(
					'CodigoMensaje' => '100',
					'DescripcionMensaje' => 'Transaction validated successfully',
					);
				}
				else
				{
					$invoiceDetails = array(
					'CodigoMensaje' => '105',
					'DescripcionMensaje' => 'Transaction not validated successfully',
					);

					// write message to the log file
					$this->errorlog->lwrite('Transaction not validated successfully');

				}
			}else{
					$invoiceDetails = array(
					'CodigoMensaje' => '105',
					'DescripcionMensaje' => 'Transaction not validated successfully',
					);
					// write message to the log file
					$this->errorlog->lwrite('Transaction not validated successfully');
			}
		}else{
				$invoiceDetails = array(
				'CodigoMensaje' => '101',
				'DescripcionMensaje' => 'Authentication Failed',
				);

				// write message to the log file
				$this->errorlog->lwrite('An error occurred while trying to authenticate your account with username='.$uname.' and password='.$passwd.'');

		}

		xml_viewpage($invoiceDetails);
	}
	public function _sendEmail($subject, $message)
	{
		$to = TO_EMAIL;
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
		// More headers
		$headers .= "From:FROM_NAME <FROM_EMAIL>" . "\r\n";
		mail($to,$subject,$message,$headers);
	}
}
