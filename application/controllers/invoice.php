<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Invoice extends CI_Controller {

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
	public function _searchBookstore($inNum){
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
	public function _searchFedena($inNum){
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
}
?>