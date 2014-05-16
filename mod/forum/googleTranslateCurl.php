<?php
/*  Este programa es software libre: usted puede redistribuirlo y/o
    modificarlo bajo los términos de la Licencia Pública General GNU publicada
    por la Fundación para el Software Libre, ya sea la versión 3
    de la Licencia, o cualquier versión posterior.

    Este programa se distribuye con la esperanza de que sea útil, pero
    SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
    MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
    Consulte los detalles de la Licencia Pública General GNU para obtener
    una información más detallada.

    Debería haber recibido una copia de la Licencia Pública General GNU
    junto a este programa.
    En caso contrario, consulte <http://www.gnu.org/licenses/>.
   
    POR FAVOR CONSERVE ESTA NOTA SI EDITA ESTE ARCHIVO

    Desarrollado por:
    Colnodo para Funredes con el apoyo de l'Institut de la Francophonie numérique, Organisation Internationale de la Francophonie.

    Lider de Desarrollo:
		Juan Guillermo Ossa Sánchez
	
    Administrador de la Red Colnodo:
		Andrés Morantes Hernández

    Bogotá - Colombia - 2011
 */
?>
<?php
/***
 * El fichero googleTranslateCurl.php posee la clase con los métodos que realizan la peticion via curl a los metodos de traducción y detección 
 * de idiomas de google
 * 
 *  Pre: 
 *  Post: 
 *****/
require_once('json.php');

class TradAux
{
	protected $googleApiKey;       //api key de google, clave que proporciona google
    
	function detectarIdioma($text)
    /***
     * Pre:   $text      Texto con un mensaje en cualquier idioma
     * Post:  Retorna una cadena de texto con el identificador del idioma que lo representa, por ej.  si el idioma es español: retorna "es"
     *****/
	{
		$text		= urlencode("'".$text."'");
		//urlencode($text);		
		$urlTrad 	= "https://www.googleapis.com/language/translate/v2/detect?key={$this->googleApiKey}&q={$text}";
		
		$trans = @file_get_contents($urlTrad);

		$objJson = new Services_JSON();
		$json = $objJson->decode($trans); 
		$arrResp = $this->objeto2array($json);
		
		return $arrResp['data']['detections'][0][0]['language'];
	}
	
	function traducir($text, $destLang, $srcLang)
    /***
     * Pre:   $text      Texto a ser traducido
	 *        $srcLang   Cadena de dos caracteres que contiene el codigo de idioma del texto a ser traducido
	 *        $destLang  Cadena de dos caracteres que contiene el codigo del idioma al cuar sera traducioo $text
     * Post:  Retorna una cadena de texto con el texto traducido a $destLang
     *****/
	{ 
		$text		= urlencode($text);		
		$destLang 	= urlencode($destLang);
		$srcLang 	= urlencode($srcLang);
		//$urlTrad 	= "http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q={$text}&key={$this->googleApiKey}&langpair={$srcLang}|{$destLang}";
					  
		// https://www.googleapis.com/language/translate/v2?key=INSERT-YOUR-KEY&source=en&target=de&q=Hello%20world
		$urlTrad = "https://www.googleapis.com/language/translate/v2?key={$this->googleApiKey}&source={$srcLang}&target={$destLang}&q={$text}";
		
		$trans = @file_get_contents($urlTrad);

		$objJson = new Services_JSON();
		$json = $objJson->decode($trans); 
		$arrResp = $this->objeto2array($json);
	
		return $arrResp['data']['translations'][0]['translatedText'];

	}

	
	
	function objeto2array($objeto)
    /***
	 * Pre:   $objeto    Un objetpo de php
	 * Post:  Retorna un array, el cual contie todos los atributos del objeto
	 *****/
	{
		if(is_array($objeto) || is_object($objeto))
		{
			$arreglo = array();
			foreach($objeto as $clave => $valor)
			{
				$arreglo[$clave] = $this->objeto2array($valor);
			}
			
			return $arreglo;
		}
		return $objeto;
	}

    function asigGoogleApiKey($str)
    {
		$this->googleApiKey = $str;
    }
    
}
?>
