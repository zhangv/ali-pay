<?php
/**
 * @license MIT
 * @author zhangv
 */

namespace zhangv\alipay\util;
class Parser{
	private $RESPONSE_SUFFIX = "_response";

	private $ERROR_RESPONSE = "error_response";

	private $SIGN_NODE_NAME = "sign";

	public function __construct(){
	}

	public function parseResponse($resp,$request,$format){
		if ("json" == $format) {

			$respObject = json_decode($resp);
			if (null !== $respObject) {
				$respWellFormed = true;
				$signData = $this->parserJSONSignData($request, $resp, $respObject);
			}
		} else if ("xml" == $format) {

			$respObject = @ simplexml_load_string($resp);
			if (false !== $respObject) {
				$respWellFormed = true;

				$signData = $this->parserXMLSignData($request, $resp);
			}
		}
	}

	function parserJSONSignData($request, $responseContent, $responseJSON) {

		$signData = new SignData();

		$signData->sign = $this->parserJSONSign($responseJSON);
		$signData->signSourceData = $this->parserJSONSignSource($request, $responseContent);


		return $signData;

	}

	function parserJSONSignSource($request, $responseContent) {

		$rootNodeName = str_replace(".", "_", $request) . $this->RESPONSE_SUFFIX;

		$rootIndex = strpos($responseContent, $rootNodeName);
		$errorIndex = strpos($responseContent, $this->ERROR_RESPONSE);


		if ($rootIndex > 0) {

			return $this->parserJSONSource($responseContent, $rootNodeName, $rootIndex);
		} else if ($errorIndex > 0) {

			return $this->parserJSONSource($responseContent, $this->ERROR_RESPONSE, $errorIndex);
		} else {

			return null;
		}


	}

	function parserJSONSource($responseContent, $nodeName, $nodeIndex) {
		$signDataStartIndex = $nodeIndex + strlen($nodeName) + 2;
		$signIndex = strpos($responseContent, "\"" . $this->SIGN_NODE_NAME . "\"");
		// 签名前-逗号
		$signDataEndIndex = $signIndex - 1;
		$indexLen = $signDataEndIndex - $signDataStartIndex;
		if ($indexLen < 0) {

			return null;
		}

		return substr($responseContent, $signDataStartIndex, $indexLen);

	}

	function parserJSONSign($responseJSon) {

		return $responseJSon->sign;
	}

	function parserXMLSignData($request, $responseContent) {


		$signData = new SignData();

		$signData->sign = $this->parserXMLSign($responseContent);
		$signData->signSourceData = $this->parserXMLSignSource($request, $responseContent);


		return $signData;


	}

	function parserXMLSignSource($request, $responseContent) {
		$rootNodeName = str_replace(".", "_", $request) . $this->RESPONSE_SUFFIX;


		$rootIndex = strpos($responseContent, $rootNodeName);
		$errorIndex = strpos($responseContent, $this->ERROR_RESPONSE);
		//		$this->echoDebug("<br/>rootNodeName:" . $rootNodeName);
		//		$this->echoDebug("<br/> responseContent:<xmp>" . $responseContent . "</xmp>");


		if ($rootIndex > 0) {

			return $this->parserXMLSource($responseContent, $rootNodeName, $rootIndex);
		} else if ($errorIndex > 0) {

			return $this->parserXMLSource($responseContent, $this->ERROR_RESPONSE, $errorIndex);
		} else {

			return null;
		}


	}

	function parserXMLSource($responseContent, $nodeName, $nodeIndex) {
		$signDataStartIndex = $nodeIndex + strlen($nodeName) + 1;
		$signIndex = strpos($responseContent, "<" . $this->SIGN_NODE_NAME . ">");
		// 签名前-逗号
		$signDataEndIndex = $signIndex - 1;
		$indexLen = $signDataEndIndex - $signDataStartIndex + 1;

		if ($indexLen < 0) {
			return null;
		}


		return substr($responseContent, $signDataStartIndex, $indexLen);


	}

	function parserXMLSign($responseContent) {
		$signNodeName = "<" . $this->SIGN_NODE_NAME . ">";
		$signEndNodeName = "</" . $this->SIGN_NODE_NAME . ">";

		$indexOfSignNode = strpos($responseContent, $signNodeName);
		$indexOfSignEndNode = strpos($responseContent, $signEndNodeName);


		if ($indexOfSignNode < 0 || $indexOfSignEndNode < 0) {
			return null;
		}

		$nodeIndex = ($indexOfSignNode + strlen($signNodeName));

		$indexLen = $indexOfSignEndNode - $nodeIndex;

		if ($indexLen < 0) {
			return null;
		}

		// 签名
		return substr($responseContent, $nodeIndex, $indexLen);

	}
}