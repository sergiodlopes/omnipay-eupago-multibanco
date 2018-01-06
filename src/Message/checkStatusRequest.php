<?php

namespace Omnipay\Eupago\Message;

use Omnipay\Common\Message\AbstractRequest;
use SoapClient;
use SoapFault;
use Exception;

/**
 * Eupago - Multibanco checkStatusRequest
 */
class checkStatusRequest extends AbstractRequest {

    public function getApiKey() {
        return $this->getParameter('apiKey');
    }

    public function setApiKey($value) {
        return $this->setParameter('apiKey', $value);
    }

    public function getTransactionReference() {
        return $this->getParameter('transactionReference');
    }

    public function getData() {
        return $this->getParameters();
    }

    public function getUrl() {
        $explode = explode ('-' , $this->getApiKey());
        if ($explode[0] = "demo") {
            $url = 'http://replica.eupago.pt/replica.eupagov1.wsdl';
        } else {
            $url = 'http://eupago.pt/eupagov1.wsdl';
        }
        return $url;
    }

    public function sendData($data) {
        $arraydados = array(
            "chave" => $this->getApiKey(),
            "referencia" => $this->getTransactionReference()
        );

        $url = $this->getUrl();

        // SOAP 1.2 client
        $params = array(
            'encoding' => 'UTF-8',
            'cache_wsdl' => WSDL_CACHE_NONE,
            'soap_version' => SOAP_1_2,
            'keep_alive' => false,
            'connection_timeout' => 180,
			'stream_context' => stream_context_create(array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false, 
					'allow_self_signed' => true
				)
			))
		);

        try {
            $client = new SoapClient($url, $params);
            $result = $client->informacaoReferencia($arraydados);
        } catch (SoapFault $sf) {
            throw new Exception($sf->getMessage(), $sf->getCode());
        }

        return $this->response = new checkStatusResponse($this, $result);
    }

}
