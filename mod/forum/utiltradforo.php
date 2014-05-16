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
 * El fichero utiltradforo.php contiene la rutina ejecutatraductor, la cual realiza el proceso de traducción utilizando el googleapi
 * 
 *  Pre: 
 *  Post: 
 *****/
require_once('pivote.php');
require_once('googleTranslateCurl.php');

function ejecutaTraductor($des, $strTraducir, $gak)
/***
 * Pre:  $des, cadena que contiene dos caracteres que representan el lenguaje destino de traducción
 *       $strTraducir, cadena que contiene el texto a traducir
 *       $gak,  google api key
 * 
 * Post: $resultado, cadena que contiene la traducción realizada por el google api
 *****/
{
	$strTotal = "";

	$objTrad = new TradAux();
	$objPivote = new Pivote();

	$strTotal = "";
	$strTraducir = nl2br($strTraducir);
	$objPivote->asigStrTotal($strTraducir);
	$objPivote->asigIncremento(1300);
	$objPivote->construirVecPib();
	$objTrad->asigGoogleApiKey($gak);
	$objVec = $objPivote->infoVecPiv();

	$ori = $objTrad->detectarIdioma(substr($strTraducir, $objVec[0]+1, $objVec[1]-$objVec[0]));

	for($i=1;$i<count($objVec);$i++)
	{
		$ini = $objVec[$i-1];
		$fin = $objVec[$i];
		$tam = $fin - $ini;
		
		$tmpString  = substr($strTraducir, $ini+1, $tam);
		$tmpString  = $objTrad->traducir($tmpString, $des, $ori);
		$strTotal  .=  $tmpString;
	}

	return $strTotal;
}

function idioma($text, $gak)
{

    $strTotal = "";

	$objTrad = new TradAux();
	$objPivote = new Pivote();

	$strTotal = "";
	$strTraducir = nl2br($text);
	$objPivote->asigStrTotal($strTraducir);
	$objPivote->asigIncremento(1300);
	$objPivote->construirVecPib();
	$objTrad->asigGoogleApiKey($gak);
	$objVec = $objPivote->infoVecPiv();

	$ori = $objTrad->detectarIdioma(substr($strTraducir, $objVec[0]+1, $objVec[1]-$objVec[0]));

 return $ori;
}


function ejeTraductor($des, $strTraducir, $mak)
/***
 * Pre:  $des, cadena que contiene dos caracteres que representan el lenguaje destino de traducción
 *       $strTraducir, cadena que contiene el texto a traducir
 *       $gak,  google api key
 * 
 * Post: $resultado, cadena que contiene la traducción realizada por el google api
 *****/
{
	$strTotal = "";

	$objTrad = new TradAux();
	$objPivote = new Pivote();

	$strTotal = "";
	$strTraducir = nl2br($strTraducir);
	$objPivote->asigStrTotal($strTraducir);
	$objPivote->asigIncremento(1300);
	$objPivote->construirVecPib();
	$objTrad->asigMicrosoftApiKey($mak);
	$objVec = $objPivote->infoVecPiv();

	$ori = $objTrad->detectarIdioma(substr($strTraducir, $objVec[0]+1, $objVec[1]-$objVec[0]));

	for($i=1;$i<count($objVec);$i++)
	{
		$ini = $objVec[$i-1];
		$fin = $objVec[$i];
		$tam = $fin - $ini;
		
		$tmpString  = substr($strTraducir, $ini+1, $tam);
		$tmpString  = $objTrad->traduce($tmpString, $des, $ori);
		$strTotal  .=  $tmpString;
	}

	return $strTotal;
}


?>
