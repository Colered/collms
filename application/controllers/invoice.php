<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Invoice extends CI_Controller 
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('search');
	}
	public function VerificarReferencia()
	{
		$uname    = $this->input->get('psUsuario');
		$passwd    = $this->input->get('psPassword');
		$canal    = $this->input->get('psCanal');
		$inNum    = $this->input->get('psReferencia');
		$authDetails = $this->_verifyCredentials($uname, $passwd);
		if(!empty($authDetails))
		{		
			$this->_searchFedena($inNum);			
			//$this->_searchBookstore($inNum);			
		}else{
				$invoiceDetails = array(
				'CodigoMensaje' => '101',
				'DescripcionMensaje' => 'Authentication Failed',
				);
				header('Content-type: application/xml');
				$xml = new SimpleXMLElement('<DetalleConsulta  version="1.0"/>');
				$this->_toxml($xml, $invoiceDetails);
				print $xml->asXML();
		}
	}
	/* Function to authenticate API Call */
	public function _verifyCredentials($uname, $passwd)
	{
		$authDetails = $this->search->getDetails($uname, $passwd);
		return $authDetails;
	}
	/* Below function will search the invoice number in Bookstore. If found return the details otherwise error*/
	public function _searchBookstore($inNum)
	{
		$orderDetails = $this->search->getBookstoreInvoiceDetails($inNum);
		if($orderDetails)
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
			$invoiceDetails = '';
			if(!empty($orderDetails) && sizeof($orderDetails) == 1){
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
				'CodigoMensaje' => '102',
				'DescripcionMensaje' => 'Invoice Not Valid',
				);
			}
		}else{
			$invoiceDetails = array(
				'CodigoMensaje' => '102',
				'DescripcionMensaje' => 'Invoice Not Valid',
				);
		}
		header('Content-type: application/xml');
		$xml = new SimpleXMLElement('<DetalleConsulta  version="1.0"/>');
		$this->_toxml($xml, $invoiceDetails);
		print $xml->asXML();		
	}
	/* Below function will search the invoice number in Fedena. If found return the details otherwise error*/
	public function _searchFedena($inNum)
	{
		$studentDetails = $this->search->getStudent($inNum);
		if($studentDetails)
		{
			$chkstudent = $this->search->checkStudent($inNum);
			if($chkstudent)
			{
				$this->search->updateStudent($studentDetails,$inNum);
			}
			else{
				$this->search->insertStudent($studentDetails);
			}
			$StudentID = $studentDetails[0]['id'];
			$feeDetails = $this->search->getFedenaInvoiceDetails($StudentID);
			if($feeDetails)
			{
				$count = sizeof($feeDetails);
				$balance = '';
				foreach($feeDetails as $fees)
				{
					$balance = $balance + $fees['balance'];				
				}
				$due_blnc = $balance - $feeDetails[$count-1]['balance'];
				
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
					'CodigoMensaje' => '102',
					'DescripcionMensaje' => 'Invoice Not Valid',
					);
					$message = 'Dear Admin<br/>There was no pending fee detail found for the invoice number - '.$inNum.' Please check.<br/><br/>Thanks,<br/>Banco Popular.';
					$subject = 'No Fee Detail Found';
					$this->_sendEmail($subject, $message);
				}
		}else{
			$invoiceDetails = array(
				'CodigoMensaje' => '102',
				'DescripcionMensaje' => 'Invoice not Valid',
				);
		}
		header('Content-type: application/xml');
		$xml = new SimpleXMLElement('<DetalleConsulta  version="1.0"/>');
		$this->_toxml($xml, $invoiceDetails);
		print $xml->asXML();
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
		$authDetails = $this->_verifyCredentials($uname, $passwd);
		if(!empty($authDetails))
		{
			$this->_callFedena($inNum, $transactionId, $amount, $paymentDate);
			//$this->_callBookstore($inNum, $transactionId, $amount, $paymentDate);			
		}else{
				$invoiceDetails = array(
				'CodigoMensaje' => '101',
				'DescripcionMensaje' => 'Authentication Failed',
				);
				header('Content-type: application/xml');
				$xml = new SimpleXMLElement('<DetalleConsulta  version="1.0"/>');
				$this->_toxml($xml, $invoiceDetails);
				print $xml->asXML();
		}

	}
	//update fee transaction details in fedena as well as LMS
	public function _callFedena($inNum, $transactionId, $amount, $paymentDate)
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
						'CodigoMensaje' => '103',
						'DescripcionMensaje' => 'Amount is greater than invoice amount',
						);	
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
					$app = 'fedena';
					$lms_txn_id = $this->search->getMaxLMSTxnId();
					$lms_txn_id = $lms_txn_id[0]['lms_txn_id'] + 1;
					if($this->search->updateLMS($inNum, $transactionId, $originalAmount, $paymentDate, $app, '',$StudentID, $lms_txn_id))
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
									'CodigoMensaje' => '104',
									'DescripcionMensaje' => 'There is some problem in fee updation',
									);	
					}
				}
			}else{
				$updateDetails = array(
					'CodigoMensaje' => '102',
					'DescripcionMensaje' => 'Invoice not valid',
					);
				$message = 'Dear Admin<br/>There was no pending fee detail found for the invoice number - '.$inNum.' Please check.<br/><br/>Thanks,<br/>Banco Popular.';
				$subject = 'No Fee Detail Found';
				$this->_sendEmail($subject, $message);
			}
		}else{
				$updateDetails = array(
					'CodigoMensaje' => '102',
					'DescripcionMensaje' => 'Invoice not valid',
					);
		}		
		header('Content-type: application/xml');
		$xml = new SimpleXMLElement('<DetalleReciboPago version="1.0"/>');
		$this->_toxml($xml, $updateDetails);
		print $xml->asXML();
	}
	//update fee transaction details in Bookstore as well as LMS
	public function _callBookstore($inNum, $transactionId, $amount, $paymentDate)
	{
		if($this->search->updateBookstoreOrderDetails($inNum, $transactionId, $amount, $paymentDate))
		{
			$app = 'bookstore';
			$customers = $this->search->getCustomerId($inNum);
			$customer_id = $customers['0']['id_customer'];
			$lms_txn_id = $this->search->getMaxLMSTxnId();
			$lms_txn_id = $lms_txn_id[0]['lms_txn_id'] + 1;
			$this->search->updateLMS($inNum, $transactionId, $amount, $paymentDate, $app, $customer_id, '', $lms_txn_id);
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
			'CodigoMensaje' => '104',
			'DescripcionMensaje' => 'Order Details Not Updated',
			);	
		}
		header('Content-type: application/xml');
		$xml = new SimpleXMLElement('<DetalleReciboPago version="1.0"/>');
		$this->_toxml($xml, $updateDetails);
		print $xml->asXML();
	}
	//Validating the transactions
	public function ConciliarPagos()
	{
		$uname    = $this->input->get('psUsuario');
		$passwd    = $this->input->get('psPassword');
		$canal    = $this->input->get('psCanal');
		$authDetails = $this->_verifyCredentials($uname, $passwd);
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
				}				
			}else{
					$invoiceDetails = array(
					'CodigoMensaje' => '105',
					'DescripcionMensaje' => 'Transaction not validated successfully',
					);
			}
		}else{
				$invoiceDetails = array(
				'CodigoMensaje' => '101',
				'DescripcionMensaje' => 'Authentication Failed',
				);				
		}
		header('Content-type: application/xml');
		$xml = new SimpleXMLElement('<DetalleConsulta  version="1.0"/>');
		$this->_toxml($xml, $invoiceDetails);
		print $xml->asXML();
	}
	public function _sendEmail($subject, $message)
	{
		$to = 'deepali.kakkar@ibtechnology.com';
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
		// More headers
		$headers .= 'From:Banco Popular <yourbank@yopmail.com>' . "\r\n";
		mail($to,$subject,$message,$headers);
	}
}
?>