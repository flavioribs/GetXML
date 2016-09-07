<?php

/**
 * Classe para pegar XML usando a plataforma fsist.com.br.
 * @author Whatyson Neves Bueno <whatyson@promasters.net.br>
 * @copyright ProMasters Sistemas Web (promasters.net.br)
 */
class GetXML {

	/**
	 * Atributo que guarda o local onde encontra-se os cookies da sessão.
	 * @var cookie_file
	 */
	private $cookie_file;

	/**
	 * Atributo que guarda a última requisição do cURL.
	 * @var curl
	 */
	private $curl;

	/**
	 * Atributo que guarda o usuário simulado no acesso ao sistema.
	 * @var usuario
	 */
	private $usuario;

	/**
	 * Inicia a requisição ao sistema fsist.com.br
	 * @param boolean $primeiro, verdadeiro para primeiro acesso, falso para os seguintes.
	 */
	public function __construct($primeiro = true) {
		$this->cookie_file = dirname(__FILE__).DIRECTORY_SEPARATOR."fsist.txt";
		@unlink($this->cookie_file);
		if($primeiro) {
			$this->usuario = rand(1000, 9999).rand(1000, 9999);
			setcookie("UsuarioID", $this->usuario);
			$this->getCookie();
		} else {
			$this->usuario = $_COOKIE["UsuarioID"];
		}
	}

	/**
	 * Método que gera URL randomica aos servidores do sistema.
	 * @return string
	 */
	private function geraURL() {
		$server[] = "www";
		$server[] = "server2";
		$server[] = "server3";
		return "https://".$server[rand(0,2)].".fsist.com.br/";
	}

	/**
	 * Método que retorna parâmetro necessário na URL.
	 * @return string
	 */
	private static function geraCOM() {
		return substr(md5(uniqid()), 0, rand(11, 12));
	}

	/**
	 * Método que retorna parâmetro necessário na URL.
	 * @return string
	 */
	private static function geraR() {
		return substr(rand(1000, 9999).rand(1000, 9999).rand(1000, 9999), 2, 9);
	}

	/**
	 * Método que inicia acesso ao sistema.
	 */
	private function getCookie() {
		$url = "https://www.fsist.com.br/";
		$this->cURL($url);
		$html = $this->getCURL();
		preg_match("/(PriPlugin\/pri\.css\?.{5,10}?)(\"|')/i", $html, $m);
		$requests[] = $url.$m[1];
		preg_match("/(PriPlugin\/pri\.js\?.{5,10}?)(\"|')/i", $html, $m);
		$requests[] = $url.$m[1];
		preg_match("/(PriPlugin\/img\.css\?.{3,10}?)(\"|')/i", $html, $m);
		$requests[] = $url.$m[1];
		$requests[] = "https://server2.fsist.com.br/baixarxml.ashx?m=WEB&t=teste&r=".$this->geraR();
		$requests[] = "https://server3.fsist.com.br/baixarxml.ashx?m=WEB&t=teste&r=".$this->geraR();
		foreach($requests as $value) {
			$this->cURL($value, $url);
		}
	}

	/**
	 * Método para obter PNG do captcha necessário para a consulta.
	 * @return string
	 * @uses Inserir conteúdo de retorno dentro de atributo src de uma tag img.
	 */
	public function getCatcha() {
		$url = "baixarxml.ashx?m=WEB&UsuarioID=".$this->usuario."&cte=0&pub=11&com=".$this->geraCOM()."&t=captcha&chave=&r=".$this->geraR();
		$ref = "https://www.fsist.com.br/";
		$this->cURL($this->geraURL().$url, $ref);
		return "data:image/png;base64,".base64_encode($this->getCURL());
	}

	/**
	 * Método para envio dos dados recebidos e verificação do retorno.
	 * @param string $chave
	 * @param string $captcha
	 * @return array
	 */
	public function sendCatcha($chave, $captcha) {
		$url = "baixarxml.ashx?m=WEB&UsuarioID=".$this->usuario."&cte=0&pub=11&com=".$this->geraCOM()."&t=consulta&chave=".$chave."&captcha=".$captcha;
		$ref = "https://www.fsist.com.br/";
		$this->cURL($ref.$url, $ref);
		$a = $this->getCURL();
		if(preg_match("/OK/i", $a)) {
			$retorno["status"] = true;
		} elseif(preg_match("/Chave incorreta/i", $a)) {
			$retorno["status"] = false;
			$retorno["motivo"] = "Dígito verificador inválido";
		} elseif(preg_match("/Código da Imagem inválido/i", $a)) {
			$retorno["status"] = false;
			$retorno["motivo"] = "Captcha inválido";
		} else {
			$retorno["status"] = false;
			$retorno["motivo"] = "Erro não catalogado";
			$retorno["return"] = $a;
		}
		return $retorno;
	}

	/**
	 * Método que pega o XML físico, retorna como string.
	 * @param string $chave
	 * @return string
	 */
	public function getXML($chave) {
		$url = "baixarxml.ashx?m=WEB&UsuarioID=".$this->usuario."&cte=0&pub=11&com=".$this->geraCOM()."&t=xmlsemcert&chave=".$chave;
		$ref = "https://www.fsist.com.br/";
		$this->cURL($ref.$url, $ref);
		return $this->getCURL();
	}

	/**
	 * Método cURL para simular acesso ao sistema.
	 * @param string $url
	 * @param string $ref
	 * @param array $post
	 */
	private function cURL($url, $ref = "", $post = array()) {
		$cURL = curl_init();

		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_HEADER] = false;
		$options[CURLOPT_USERAGENT] = "cURL ".PHP_VERSION." (ProMasters)";
		$options[CURLOPT_SSL_VERIFYPEER] = false;
		$options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_AUTOREFERER] = true;
		$options[CURLOPT_RETURNTRANSFER] = true;

		// cookies
		$options[CURLOPT_COOKIE] = true;
		$options[CURLOPT_COOKIESESSION] = true;
		$options[CURLOPT_COOKIEFILE] = $this->cookie_file;
		$options[CURLOPT_COOKIEJAR] = $this->cookie_file;

		if(!empty($ref)) {
			$options[CURLOPT_REFERER] = $ref;
		}

		if(!empty($post)) {
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $post;
		}

		curl_setopt_array($cURL, $options);
		$this->curl = curl_exec($cURL);
		curl_close($cURL);
	}

	/**
	 * Método que retorna último conteúdo solicitado.
	 * @return string
	 */
	public function getCURL() {
		return $this->curl;
	}
}

if(array_key_exists("step", $_REQUEST)) {
	if($_REQUEST["step"] == "1") {
		$a = new GetXML;
		$retorno["status"] = true;
		$retorno["imagem"] = $a->getCatcha();
	} elseif($_REQUEST["step"] == "2") {
		$a = new GetXML(false);
		$send = $a->sendCatcha($_REQUEST["chave"], $_REQUEST["captcha"]);
		if($send["status"]) {
			$retorno["status"] = true;
			$retorno["xml"] = $a->getXML($_REQUEST["chave"]);
		} else {
			$retorno["status"] = false;
			$retorno["motivo"] = $send["motivo"];
		}
	} else {
		$retorno["status"] = false;
		$retorno["motivo"] = "Etapa não identificada";
	}
} else {
	$retorno["status"] = false;
	$retorno["motivo"] = "Etapa não identificada";
}

header("Content-Type: application/json; Charset=UTF-8");
echo json_encode($retorno);

?>