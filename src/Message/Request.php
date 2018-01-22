<?php

namespace Omnipay\Eupago\Message;

use Omnipay\Common\Message\AbstractRequest;
use SoapClient;
use SoapFault;
use Exception;

/**
 * Eupago Request
 */
class Request extends AbstractRequest {


    public function getApiKey() {
        return $this->getParameter('apiKey');
    }

    public function setApiKey($value) {
        return $this->setParameter('apiKey', $value);
    }

    public function getData() {
        return $this->getParameters();
    }

    public function isValid() {
        $valor = $this->getAmount();
        $moeda = $this->getCurrency();
        $nota_de_encomenda = $this->getTransactionId();

        if ($valor > 0 && ($moeda == '€' || $moeda == 'EUR') && $nota_de_encomenda) {
            return true;
        } else {
            if ($valor <= 0) {
                exit('Error: Invalid amount value... must be greater than 0');
            } else if ($moeda != '€' && $moeda != 'EUR') {
                exit('Error: Invalid currency, Eupago only supports values: € or EUR ');
            } elseif (!$nota_de_encomenda) {
                exit('Error: TransactionId field is missing!');
            } else {
                exit('Sorry, there was an error... Please comfirm if you have the all required fields');
            }
            return false;
        }
    }

    public function getUrl() {
        $parts = explode('-', $this->getApiKey());

        if ($parts[0] === 'demo') {
            $url = 'http://replica.eupago.pt/replica.eupagov1.wsdl';
        } else {
            $url = 'http://eupago.pt/eupagov1.wsdl';
        }

        return $url;
    }

    public function sendData($data) {
        if (!$this->isValid()) {
            return $this->response = new Response($this, "erro: confirme se preencheu todos os dados corretamente");
        }

        $arraydados = array(
            'chave' => $this->getApiKey(),
            'valor' => $this->getAmount(),
            'id' => $this->getTransactionId() // cada canal tem a sua chave
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
            $result = $client->gerarReferenciaMB($arraydados);
        } catch (SoapFault $sf) {
            throw new Exception($sf->getMessage(), $sf->getCode());
        }

        return $this->response = new Response($this, $result);
    }

}
