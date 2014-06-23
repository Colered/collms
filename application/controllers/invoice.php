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
			/* If - present in invoice number, then search in fedena otherwise search in bookstore */
			if(strstr($inNum, '-'))
			{
				$this->_searchFedena($inNum);
			}
			else{
				$this->_searchBookstore($inNum);
			}
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
	/* Below function will search the invoice number in Fedena. If found return the details otherwise error*/
	public function _searchBookstore($inNum)
	{
		$orderDetails = $this->search->getBookstoreInvoiceDetails($inNum);
		//print"<pre>";print_r($orderDetails);die;
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
			'Moneda' => 'USD'
			);			
		}else{
			$invoiceDetails = array(
			'CodigoMensaje' => '101',
			'DescripcionMensaje' => 'Invoice Not Valid',
			);
		}
		//print"<pre>";print_r($invoiceDetails);die;
		header('Content-type: application/xml');
		$xml = new SimpleXMLElement('<DetalleConsulta  version="1.0"/>');
		$this->_toxml($xml, $invoiceDetails);
		print $xml->asXML();		
	}
	/* Below function will search the invoice number in Fedena. If found return the details otherwise error*/
	public function _searchFedena($inNum)
	{
		$invoiceArray = explode("-", $inNum);
		$StudentID = $invoiceArray['0'];
		$FeeCollectionID = $invoiceArray['1'];
		$feeDetails = $this->search->getFedenaInvoiceDetails($StudentID, $FeeCollectionID);
		if(!empty($feeDetails) && sizeof($feeDetails) == 1){
			$invoiceDetails = array(
			'CodigoMensaje' => '100',
			'DescripcionMensaje' => 'Invoice Found',
			'NumeroReferencia' => $inNum,
			'TipoReferencia' => 'Fee Submission',
			'IDDocumento' => $feeDetails[0]['admission_no'],
			'NombreCliente' => $feeDetails[0]['first_name'].' '.$feeDetails[0]['middle_name'].' '.$feeDetails[0]['last_name'],
			'MontoImpuesto' => '0.00',
			'MontoTotal' => $feeDetails[0]['balance'],
			'Moneda' => 'USD',
			'Fecha' => $feeDetails[0]['due_date']
			);			
		}else{
			$invoiceDetails = array(
			'CodigoMensaje' => '101',
			'DescripcionMensaje' => 'Invoice Not Valid',
			);
		}
		//print"<pre>";print_r($feeDetails);die;
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
			/* If - present in invoice number, then search in fedena otherwise search in bookstore */
			if(strstr($inNum, '-'))
			{
				$this->_callFedena($inNum, $transactionId, $amount, $paymentDate);
			}
			else{
				$this->_callBookstore($inNum, $transactionId, $amount, $paymentDate);
			}
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
	public function _callFedena($inNum, $transactionId, $amount, $paymentDate)
	{
		$invoiceArray = explode("-", $inNum);
		$StudentID = $invoiceArray['0'];
		$FeeCollectionID = $invoiceArray['1'];
		$feeDetails = $this->search->getFeeAmount($StudentID, $FeeCollectionID);
		$balance = $feeDetails['0']['balance'];
		$finance_id = $feeDetails['0']['id'];
		$school_id = $feeDetails['0']['school_id'];
		if($amount < $balance)
		{
			$isPaid = '0';
			$due_amt = round($balance - $amount,2);
			$title = 'Receipt No. (partial) F'.$finance_id;
		}
		else
		{
			$isPaid = '1';
			$due_amt = '0.00';
			$title = 'Receipt No. F'.$finance_id;
		}
		$receipts = $this->search->getMaxReceiptNo('FinanceFee');
		$receipt_no = $receipts['0']['receipt_no'];
		$receipt_no = $receipt_no + 1;
		if($this->search->updateFedenaFeeDetails($StudentID, $FeeCollectionID, $transactionId, $amount, $paymentDate, $inNum, $isPaid, $due_amt, $title, $finance_id, $school_id, $receipt_no))
		{
			$app = 'fedena';
			$this->search->updateLMS($inNum, $transactionId, $amount, $paymentDate, $app, '',$StudentID, $FeeCollectionID);
			$updateDetails = array(
			'CodigoMensaje' => '100',
			'DescripcionMensaje' => 'Fee Details Updated',
			'NumeroReferencia' => $inNum,
			'NumeroAutorizacion' => '',
			'IDTransaccionBanco' => $transactionId,
			'ValorPagado' => $amount,
			'Moneda' => 'USD',			
			);	
		}else{
			$updateDetails = array(
			'CodigoMensaje' => '101',
			'DescripcionMensaje' => 'Fee Details Not Updated',
			);	
		}
		header('Content-type: application/xml');
		$xml = new SimpleXMLElement('<DetalleReciboPago version="1.0"/>');
		$this->_toxml($xml, $updateDetails);
		print $xml->asXML();


	}
	public function _callBookstore($inNum, $transactionId, $amount, $paymentDate)
	{
		if($this->search->updateBookstoreOrderDetails($inNum, $transactionId, $amount, $paymentDate))
		{
			$app = 'bookstore';
			$customers = $this->search->getCustomerId($inNum);
			$customer_id = $customers['0']['id_customer'];
			$this->search->updateLMS($inNum, $transactionId, $amount, $paymentDate, $app, $customer_id, '', '');
			$updateDetails = array(
			'CodigoMensaje' => '100',
			'DescripcionMensaje' => 'Order Details Updated',
			'NumeroReferencia' => $inNum,
			'NumeroAutorizacion' => '',
			'IDTransaccionBanco' => $transactionId,
			'ValorPagado' => $amount,
			'Moneda' => 'USD',			
			);	
		}else{
			$updateDetails = array(
			'CodigoMensaje' => '101',
			'DescripcionMensaje' => 'Order Details Not Updated',
			);	
		}
		header('Content-type: application/xml');
		$xml = new SimpleXMLElement('<DetalleReciboPago version="1.0"/>');
		$this->_toxml($xml, $updateDetails);
		print $xml->asXML();
	}
	public function ConciliarPagos()
	{
		$uname    = $this->input->get('psUsuario');
		$passwd    = $this->input->get('psPassword');
		$canal    = $this->input->get('psCanal');
		//$simple    = $this->input->get('psXMLDatos');
		$authDetails = $this->_verifyCredentials($uname, $passwd);
		if(!empty($authDetails))
		{
			$xmlstring = '<DetalleConsulta> 
			<IDTransaccion>123456</IDTransaccion> 
			<FechaTransaccion>2014-06-23 15:30:48.000000</FechaTransaccion> 
			<Valor>12.75</Valor> 
			<Referencia>1638-1</Referencia> 
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
					$txn = array(
					'CodigoMensaje' => '100',
					'DescripcionMensaje' => 'Transaction validated successfully',
					);
				}
				else
				{
					$txn = array(
					'CodigoMensaje' => '101',
					'DescripcionMensaje' => 'Transaction not validated successfully',
					);
				}
				header('Content-type: application/xml');
				$xml = new SimpleXMLElement('<DetalleReciboPago version="1.0"/>');
				$this->_toxml($xml, $txn);
				print $xml->asXML();

				

			}
			

			
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
}
?>