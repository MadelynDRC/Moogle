#!/usr/bin/php
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

    Diseño Gráfico Interfaz de Administración:
		Maria del Rosario Ortiz
	
	Colaboradores
		- Ingeniero Jeffrey Borbón Sanabria  (Administrador de la Red Colnodo en las primeras versiones del proyecto)
		-
		-
		-

    Bogotá - Colombia - 2011
 */
?>
<?php
/***
 * El fichero listo.php contiene la funcionalidad principal del proyecto list-o, donde se realiza todo el proceso de tradución y se
 * declara una instancia de la clase Traductor cuyos métodos realizan la contrucción del archivo de correo resultante que contiene
 * el mensaje a ser enviado a determinada(s) lista(s) de correo.
 * 
 *  Pre: 	$argv[0] -> Nombre del fichero php
 * 		$argv[1] -> arreglo que contiene el nombre de la lista
 *		$argv[2] -> Tipo de lista, lista simple(ls) o concatenada (lc)
 *		
 * 		Si la lista es concatenada
 *
 *		$argv[1] -> nombre de lista segida de un guión "-" y dos caracteres que identifican un idioma
 *              $argv[2] -> tipo de lista en este caso "lc"
 *		$argv[3] -> nombre de la lista
 *		$argv[4] -> dos caracteres que indican el idioma original
 *		$argv[5] -> dos caracteres que indican el idioma al cual se va a traducir
 *  Post: 
 * 		Crea un archivo de correo el cual luego es procesado por una receta de procmail
 *****/
require_once('pivote.php');
require_once('googleTranslateCurl.php');
require_once('babelfish.class.php');
require_once('json.php');
require_once('adjuntos.php');
require_once("conDb.php");

$objTraductor = new Traductor();
$nombreLista = $argv[1];
$tipoLista   = $argv[2];

if($tipoLista == "lc")
{
	$vecLista = $objTraductor->infoListaNombre($nombreLista);
	$vecConf  = $objTraductor->infoConfGeneral();

	$idiomaO = $objTraductor->infoCodIdioma($vecLista[0]['io']);
	$idioma1 = $objTraductor->infoCodIdioma($vecLista[0]['i1']);
	$idioma2 = $objTraductor->infoCodIdioma($vecLista[0]['i2']);

	$objTraductor->asigMotorTraduccion($vecLista[0]['mt']);

	$objTraductor->asigGoogleApiKey($vecConf[0]['key']);

	$objTraductor->asigDirAdjuntos("{$vecLista[0]['ra']}/{$vecLista[0]['nb']}/temp/");
	$objTraductor->asigFileMail("{$vecLista[0]['ra']}/{$vecLista[0]['nb']}/{$vecConf[0]['cnf']}");
	$objTraductor->asigPathMail("{$vecLista[0]['ra']}/{$vecLista[0]['nb']}/");
	$objTraductor->asigTempMail("{$vecConf[0]['cnf']}");

	$objTraductor->asigTagFinal($vecLista[0]['dl']);
	$objTraductor->asigPosEncabezado($vecLista[0]['pe']);

	$objTraductor->asigEncabezado($vecLista[0]['et']);

	$objTraductor->asigIOriginal(sprintf("%s", $idiomaO[0]['ci']));
	$objTraductor->asigITraduUno(sprintf("%s", $idioma1[0]['ci']));
	$objTraductor->asigITraduDos(sprintf("%s", $idioma2[0]['ci']));

	$objTraductor->leerMail();
	$objTraductor->extraeOrigenDestino();
	$objTraductor->separarMensaje("text/plain");
	$objTraductor->eliminarAdjuntos();
	$objTraductor->separarAdjuntos("attachment");
	$objTraductor->construyeResultado($tipoLista, $vecConf[0]['key']);

	$objTraductor->escribirMailDisco("{$vecLista[0]['ra']}/{$vecLista[0]['nb']}/{$vecConf[0]['cnf']}");
	$objTraductor->crearMultipartMixed();
	$objTraductor->anexarAdjuntos();
	$objTraductor->finalizarCorreo();
}
elseif($tipoLista == "ls")
{
	$nomLstOrig = $argv[3];
	$idioma0    = $argv[4];
	$idioma1    = $argv[5];

        $vecLista = $objTraductor->infoListaNombre($nomLstOrig);
	$vecConf  = $objTraductor->infoConfGeneral();

	$objTraductor->asigDirAdjuntos("{$vecLista[0]['ra']}/{$nomLstOrig}-{$idioma1}/temp/");
	$objTraductor->asigFileMail("{$vecLista[0]['ra']}/{$nomLstOrig}-{$idioma1}/{$vecConf[0]['cnf']}");
	$objTraductor->asigPathMail("{$vecLista[0]['ra']}/{$nomLstOrig}-{$idioma1}/");
	$objTraductor->asigTempMail("{$vecConf[0]['cnf']}");

	$objTraductor->asigTagFinal($vecLista[0]['dl']);
	$objTraductor->asigPosEncabezado($vecLista[0]['pe']);

	$objTraductor->asigEncabezado($vecLista[0]['et']);

	$objTraductor->asigIOriginal(sprintf("%s", $idioma0));
	$objTraductor->asigITraduUno(sprintf("%s", $idioma1));

        $objTraductor->asigMotorTraduccion($vecLista[0]['mt']);

 	$objTraductor->asigGoogleApiKey($vecConf[0]['key']);

	$objTraductor->leerMail();
	$objTraductor->extraeOrigenDestino();
	$objTraductor->separarMensaje("text/plain");
	$objTraductor->eliminarAdjuntos();
	$objTraductor->separarAdjuntos("attachment");
	$objTraductor->construyeResultado($tipoLista, $vecConf[0]['key']);

	$objTraductor->escribirMailDisco("{$vecLista[0]['ra']}/{$nomLstOrig}-{$idioma1}/{$vecConf[0]['cnf']}");
	$objTraductor->crearMultipartMixed();
	$objTraductor->anexarAdjuntos();
	$objTraductor->finalizarCorreo();
}


class Traductor
/***
 * La clase Traductor es la piedra angular del sistema List-o, contiene los métodos que realizan el proceso de traducción utilizando el 
 * google api (o babelfish), descompone el mensaje y lo arma de nuevo (incluyendo adjuntos) con las traducciones para su posterior envío.
 *****/
{
	protected $dirAdjuntos;      //ruta directorio donde se guardan los archivos a ser tratados como adjuntos
	protected $fileMailStr;      //Cadena que contiene el correo entrante a ser procesado
	protected $fileMail;         //Cadena que contiene la ruta y nombre de archivo del correo inicial recibido para ser procesado
	protected $pathMail;         //Ruta donde se encuentra $tempMail o el archivo temporal de correo
	protected $tempMail;         //Nombre de archivo temporal de correo (definido en la interfaz)
	protected $mime;             //Toma el recurso mime del correo entrante  
	protected $struct;           //Toma una matriz de nombres de la sección dada por un determinado mime
	protected $mensajeTxt;       //Texto que contiene el mensaje text/plain del correo entrante
	protected $mensajeHtml;      //Texto que contiene el mensaje text/html del correo entrante
	protected $To;
	protected $From;
	protected $Subject;
	protected $msgResultante;    //Texto final con los tags inicio y fin, encabezado mensaje original y traducciones
	protected $tagInicio;        //Cadena de caracteres que se coloca al principio del mensaje original y sus traducciones
	protected $tagFinal;         //Cadena de que se coloca al final de mensaje original y sus traducciones
	protected $encabezado;       //Arreglo que se ubica entre $tagInicio y el mensaje final o entre el mensaje final y $tagFinal
	protected $lstAdjuntos;      //Arreglo que contendrá instancias de la clase adjunto
	protected $boundary;         //Es una cadena que usualmente es aleatoria y se utiliza para separar las partes del mensaje 
	protected $iOriginal;        //Cadena de dos caracteres que define el idioma base del mensaje 
	protected $iTraduUno;        //Cadena de dos caracteres que define el primer idioma de traducción
	protected $iTraduDos;        //Cadena de dos caracteres que define el segundo idioma de traducción
	protected $posEncabezado;    //atributo que decide la posición de $encabezado
	protected $motorTraduccion;  //Motor encargado de realizar la traducción, babel fish o google
	protected $googleApiKey;     
	
	function __construct() 
	/***
	 * Pre:
	 * Post: Se inicializan msgResultante como cadena vacía, y lstAdjuntos como un arreglo.
	 *****/
	{
		$this->lstAdjuntos = array();
		$this->msgResultante = "";
	}	

	function escribirMailDisco($file)
	/***
	 * Pre:  $file, ruta completa de archivo donde se debe escribir el mensaje resultante
	 * Post: en $file se escribe la cadena con el texto de correo final que será luego procesado por procmail
	 *****/
	{
		$str  = "{$this->To} \n";
		$str .= "{$this->From} \n";
		$str .= "{$this->Subject} \n";
		$str .= "Content-Type: text/plain; charset=iso-8859-1 \n";
		$str .= "Content-Transfer-Encoding: 8bit \n\n";
		$str .= $this->msgResultante;
		
		file_put_contents($file, $this->msgResultante);	
	}

	function construyeResultado($tipoLst, $gk)
	/***
	 * Pre:  $tipoLst -> tipo de lista, simple o concatenada
	 *	 $gk      -> googleApiKey
	 * Post: $this->msgResultante, conserva el mensaje codificado en text/plain del mesaje original con dos traducciones, tags inicio, fin y un 
	 *       encabezado, si es un hilo de mensajes traduce solamente lo que esté antes del tagInicio, para evitar un bucle no deseable de 
	 *       traducciones.
	 *****/
	{
		$this->tagInicio = $this->tagFinal; 

	        $mensajeO = "Mensaje Original";
		$mensaje1 = "Traducción";
		$mensaje2 = "Traducción";

		$strEncabArriba = "";
		$strEncabAbajo  = "";
		
		if($this->posEncabezado == "ARRIBA")
			$strEncabArriba = utf8_decode($this->encabezado)."\n\n";
		else
			$strEncabAbajo  = "\n\n".utf8_decode($this->encabezado);

	        if($tipoLst == "lc")
		{
		   	if(strrpos($this->mensajeTxt, $this->tagInicio) !== false)
		   	{
				$posIni = strpos($this->mensajeTxt, $this->tagInicio);

				$strSiTr = substr($this->mensajeTxt, 0, $posIni);
				$strNoTr = substr($this->mensajeTxt, $posIni);

				if($this->motorTraduccion == "GOOGLE")
				{
                                	$resUno   = $this->ejecutaTraductor($this->iOriginal, $this->iTraduUno, utf8_encode($strSiTr), $this->motorTraduccion);
					$resDos   = $this->ejecutaTraductor($this->iOriginal, $this->iTraduDos, utf8_encode($strSiTr), $this->motorTraduccion);
					$mensajeO = utf8_decode($mensajeO);
					$mensaje1 = utf8_decode($mensaje1);
					$mensaje2 = utf8_decode($mensaje2);
					$resUno   = utf8_decode($resUno);
					$resDos   = utf8_decode($resDos);
				}
				elseif($this->motorTraduccion == "BABELF")
				{
                                       $resUno   = $this->ejecutaTraductor($this->iOriginal, $this->iTraduUno, $strSiTr, $this->motorTraduccion);
				       $resDos   = $this->ejecutaTraductor($this->iOriginal, $this->iTraduDos, $strSiTr, $this->motorTraduccion);
			               $mensajeO = $this->ejecutaTraductor("es", $this->iOriginal, utf8_decode($mensajeO), $this->motorTraduccion);
			               $mensaje1 = $this->ejecutaTraductor("es", $this->iTraduUno, utf8_decode($mensaje1), $this->motorTraduccion);
			               $mensaje2 = $this->ejecutaTraductor("es", $this->iTraduDos, utf8_decode($mensaje2), $this->motorTraduccion);
				}

                        	$traduc  = utf8_decode($this->tagInicio);
				$traduc .= "\n\n";
				$traduc .= $strEncabArriba;
				$traduc .= $mensajeO;
				$traduc .= "\n\n";
				$traduc .= $strSiTr;
				$traduc .= "\n\n";
				$traduc .= $mensaje1;
				$traduc .= "\n\n";
				$traduc .= $resUno;
				$traduc .= "\n\n";
				$traduc .= $mensaje2;
				$traduc .= "\n\n";
				$traduc .= $resDos;
				$traduc .= "\n\n";
				$traduc .= str_replace("&gt;", ">", $strNoTr);
				$traduc .= $strEncabAbajo;
				$traduc .= "\n";
				$traduc .= utf8_decode($this->tagFinal);
			
				$this->msgResultante = str_replace("&gt;", ">", $traduc);
			}
			else
			{
                                if($this->motorTraduccion == "GOOGLE")
				{
                                 	$resFr    = $this->ejecutaTraductor($this->iOriginal, $this->iTraduUno, utf8_encode($this->mensajeTxt), $this->motorTraduccion);
				 	$resEn    = $this->ejecutaTraductor($this->iOriginal, $this->iTraduDos, utf8_encode($this->mensajeTxt), $this->motorTraduccion);
              				$mensajeO = $this->ejecutaTraductor("es", $this->iOriginal, $mensajeO, $this->motorTraduccion);
	                	       	$mensaje1 = $this->ejecutaTraductor("es", $this->iTraduUno, $mensaje1, $this->motorTraduccion);
	      		                $mensaje2 = $this->ejecutaTraductor("es", $this->iTraduDos, $mensaje2, $this->motorTraduccion);


					$mensajeO  = utf8_decode($mensajeO);
					$mensaje1  = utf8_decode($mensaje1);
					$mensaje2  = utf8_decode($mensaje2);
					$resEn     = utf8_decode($resEn);
					$resFr     = utf8_decode($resFr);
				}
				elseif($this->motorTraduccion == "BABELF")
				{
                                 	$resFr    = $this->ejecutaTraductor($this->iOriginal, $this->iTraduUno, $this->mensajeTxt, $this->motorTraduccion);
				 	$resEn    = $this->ejecutaTraductor($this->iOriginal, $this->iTraduDos, $this->mensajeTxt, $this->motorTraduccion);
              				$mensajeO = $this->ejecutaTraductor("es", $this->iOriginal, utf8_decode($mensajeO), $this->motorTraduccion);
	                       		$mensaje1 = $this->ejecutaTraductor("es", $this->iTraduUno, utf8_decode($mensaje1), $this->motorTraduccion);
	                       		$mensaje2 = $this->ejecutaTraductor("es", $this->iTraduDos, utf8_decode($mensaje2), $this->motorTraduccion);
				}

				$traduc  = utf8_decode($this->tagInicio);
				$traduc .= "\n\n";
				$traduc .= $strEncabArriba;
				$traduc .= $mensajeO;
				$traduc .= "\n\n";
				$traduc .= $this->mensajeTxt;
				$traduc .= "\n\n";
				$traduc .= $mensaje1;
				$traduc .= "\n\n";
				$traduc .= $resFr;
				$traduc .= "\n\n";
				$traduc .= $mensaje2;
				$traduc .= "\n\n";
				$traduc .= $resEn;
				$traduc .= $strEncabAbajo;
				$traduc .= "\n";
				$traduc .= utf8_decode($this->tagFinal);

				$this->msgResultante = $traduc;
			}	
		}
		elseif($tipoLst == "ls")
		{
			if(strrpos($this->mensajeTxt, $this->tagInicio) !== false)
		   	{
				$posIni = strpos($this->mensajeTxt, $this->tagInicio);

				$strSiTr = substr($this->mensajeTxt, 0, $posIni);
				$strNoTr = substr($this->mensajeTxt, $posIni);

				if($this->motorTraduccion == "GOOGLE")
				{
				 	$resUno   = $this->ejecutaTraductor($this->iOriginal, $this->iTraduUno, utf8_encode($strSiTr), $this->motorTraduccion);
					$mensaje1 = utf8_decode($mensaje1);
					$resUno   = utf8_decode($resUno);
				}
				elseif($this->motorTraduccion == "BABELF")
				{
					$resUno   = $this->ejecutaTraductor($this->iOriginal, $this->iTraduUno, $strSiTr, $this->motorTraduccion);
					$mensaje1 = $this->ejecutaTraductor("es", $this->iTraduUno, utf8_decode($mensaje1), $this->motorTraduccion);
				}

                        	$traduc  = utf8_decode($this->tagInicio);
				$traduc .= "\n\n";
				$traduc .= $strEncabArriba;
				$traduc .= $mensaje1;
				$traduc .= "\n\n";
				$traduc .= $resUno;
				$traduc .= "\n\n";
				$traduc .= str_replace("&gt;", ">", $strNoTr);
				$traduc .= $strEncabAbajo;
				$traduc .= "\n";
				$traduc .= utf8_decode($this->tagFinal);
			
				$this->msgResultante = str_replace("&gt;", ">", $traduc);
			}
			else
			{
				if($this->motorTraduccion == "GOOGLE")
				{
					$resFr    = $this->ejecutaTraductor($this->iOriginal, $this->iTraduUno, utf8_encode($this->mensajeTxt), $this->motorTraduccion);
					$resFr    = utf8_decode($resFr);
					$mensaje1 = utf8_decode($mensaje1);

				}
				elseif($this->motorTraduccion == "BABELF")
				{
					$resFr = $this->ejecutaTraductor($this->iOriginal, $this->iTraduUno, $this->mensajeTxt, $this->motorTraduccion);
					$mensaje1 = $this->ejecutaTraductor("es", $this->iTraduUno, utf8_decode($mensaje1), $this->motorTraduccion);
				}
				
				$traduc  = utf8_decode($this->tagInicio);
				$traduc .= "\n\n";
				$traduc .= $strEncabArriba;
				$traduc .= $mensaje1;
				$traduc .= "\n\n";
				$traduc .= $resFr;
				$traduc .= "\n\n";
				$traduc .= $strEncabAbajo;
				$traduc .= "\n";
				$traduc .= utf8_decode($this->tagFinal);

				$this->msgResultante = $traduc;
			}
		}
	}
	
	function ejecutaTraductor($ori, $des, $strTraducir, $motor)
	/***
	 * Pre:  $ori, cadena que contiene dos caracteres que representan el leguanje original
 	 *       $des, cadena que contiene dos caracteres que representan el lenguaje destino de traducción
 	 *       $strTraducir, cadena que contiene el texto a traducir
	 *       $motor, motor de traducción: google o bablefish
 	 * 
 	 * Post: $resultado, cadena (sin caracteres html) que contiene la traducción realizada por el google api
 	 *****/
	{
		$strTotal = "";

		if($motor == "GOOGLE")
		{
			$objTrad = new TradAux();
			$objPivote = new Pivote();

			$strTotal = "";
			$strTraducir = nl2br($strTraducir);
			$objPivote->asigStrTotal($strTraducir);
			$objPivote->asigIncremento(1300);
			$objPivote->construirVecPib();
			$objTrad->asigGoogleApiKey($this->googleApiKey);
			$objVec = $objPivote->infoVecPiv();

			for($i=1;$i<count($objVec);$i++)
			{
				$ini = $objVec[$i-1];
		      		$fin = $objVec[$i];
		        	$tam = $fin - $ini;
			    
				$tmpString  = substr($strTraducir, $ini+1, $tam);
				$tmpString  = $objTrad->traducir($tmpString, $des, $ori);
				$strTotal  .=  $tmpString;
			}

			$strTotal = html_entity_decode($strTotal, ENT_QUOTES);
			$strTotal = str_replace("<br />", "\n", $strTotal);
			$strTotal = str_replace("&#39;", "'", $strTotal); 	
		}
		elseif($motor=="BABELF")
		{
			$objTrad   = new babelfish(NULL, NULL, '/<div\s+id="result">\s*<div[^>]*>(.*)<\/div><\/div>/Uis');
			$objPivote = new Pivote();
			$strTotal = "";
			$strTraducir = nl2br($strTraducir);
			$objPivote->asigStrTotal($strTraducir);
			$objPivote->asigIncremento(100);
			$objPivote->construirVecPib();
			$objVec = $objPivote->infoVecPiv();
			$tmpOri = $objTrad->infoStrLanguage($ori);
			$tmpDes = $objTrad->infoStrLanguage($des);


			for($i=1;$i<count($objVec);$i++)
			{
				$limInf 	= $i-1;
				$limSup 	= $i;
				$ini 		= $objVec[$limInf];
				$fin 		= $objVec[$limSup];
				$tam    	= $fin - $ini;
				$tmpString  	= substr($strTraducir, $ini+1, $tam);

				if($ori != $des)
					$strTotal .= $objTrad->translate($tmpString, $tmpOri, $tmpDes);
				else
				        $strTotal .= $tmpString;
			}
		}
		
		return $strTotal;
	}

	function leerMail() 
	/***
	 * Pre: 
 	 * Post: $Mime toma el recurso mime del correo, $struct toma una matriz de nombres de la sección dada por $mime
 	 *       $fileMailStr queda con toda la cadena del correo entrante para procesarse
 	 *****/
	{
		$this->mime = mailparse_msg_parse_file($this->fileMail);	
		$this->struct = mailparse_msg_get_structure($this->mime);
		$this->fileMailStr = file_get_contents($this->fileMail);
		

	}

	function separarMensaje($contentType)
	/***
	 * Pre:  $contentType, cadena de texto igual a "text/plain"
	 * Post: $this->mensajeTxt, contenido principal del correo, cadena a ser traducida
	 * 		 $$this->mensajeHtml, contenido principal del correo, cuando es texto html
	 *****/
	{
		foreach($this->struct as $st) 
		{
			$seccion = mailparse_msg_get_part($this->mime, $st);
			$info    = mailparse_msg_get_part_data($seccion);

			ob_start();
			mailparse_msg_extract_part_file($seccion, $this->fileMail);
			$contents = ob_get_contents();
			ob_end_clean();

			if($info['content-type'] == $contentType)
			{
 				if($contentType == "text/plain")
				{
					if($info['charset'] == "utf-8" || $info['charset'] == "UTF-8")
					{
						$this->mensajeTxt = utf8_decode($contents);
						//$this->mensajeTxt = $contents;
					}
					else if($info['charset'] == "ISO-8859-1" || $info['charset'] == "iso-8859-1")
						$this->mensajeTxt = $contents;
				}
				elseif($contentType == "text/html")
					$this->mensajeHtml = $contents;
				
				break;
			}
			
		}
	}
	
	function separarAdjuntos($contentDisposition)
	/***
	 * Pre:  $contentDisposition, cadena igual a "attachment"
	 * Post: lstAdjuntos[], array de objetos los cuales son instancias de la clase adjunto. cada uno de estos subíndices representan, en orden,
	 *       los attachments del correo entrante, se construyen los atributos de el adjunto con las rutas absolutas del correo final,  del
	 *       adjunto y nombre de archivo adjunto.
	 *****/
	{
		foreach($this->struct as $st) 
		{
			$seccion = mailparse_msg_get_part($this->mime, $st);
			$info    = mailparse_msg_get_part_data($seccion);

			ob_start();
			mailparse_msg_extract_part_file($seccion, $this->fileMail);
			$contents = ob_get_contents();
			ob_end_clean();
			
			if(isset($info['content-disposition']))
			{
				if($info['content-disposition'] == $contentDisposition)
				{
					file_put_contents($this->dirAdjuntos.$info['disposition-filename'], $contents);
					
					$objAdjunto = new Adjunto();
					
					$objAdjunto->asigRutaAdjuntos($this->dirAdjuntos);
					$objAdjunto->asigRutaCorreoPrincipal($this->pathMail);
					$objAdjunto->asigCorreoPrincipal($this->tempMail);
					$objAdjunto->asigNombre($info['disposition-filename']);
					$objAdjunto->asigRuta($this->dirAdjuntos);
					$objAdjunto->asigContentType($info['content-type']);
					$objAdjunto->crearAdjunto();
					
					$this->lstAdjuntos[] = serialize($objAdjunto);
				}
			}
		}
	}
	
	function anexarAdjuntos()
	/***
	 * Pre:
	 * Post:  recorre el vector de adjuntos y anexa cada uno de estos al archivo que contiene el cuerpo del mensaje el cual 
	 *        posee las traducciones (cuerpo principal del mensaje final).
	 *****/
	{
		for($i=0;$i<count($this->lstAdjuntos);$i++)
		{
			$objAdjunto = new Adjunto();
			
			$objAdjunto = unserialize($this->lstAdjuntos[$i]);
			$objAdjunto->anexarAdjunto($this->boundary);
		}
	}
	
	function eliminarAdjuntos()
	/***
	 * Pre:
 	 * Post:  $this->boundary, cadena obtenida del bloque principal del correo resultante que será utilizada para separar cada uno de los adjuntos
 	 *        y el cuerpo del mensaje.
 	 *        El archivo tmp.mm1 contiene el cuerpo principal del nuevo mensaje.
 	 *****/
	{
		if ($gestor = opendir($this->dirAdjuntos)) 
		{
			while (false !== ($archivo = readdir($gestor))) 
			{
				//echo "$archivo\n";
				//unlink($archivo);
			}
 
			closedir($gestor);
		}
	}
	
	function crearMultipartMixed()
	/***
	 * Pre:
	 * Post:  $this->boundary, cadena obtenida del bloque principal del correo resultante que será utilizada para separar cada uno de los adjuntos
	 *        y el cuerpo del mensaje.
	 *        El archivo tmp.mm1 contiene el cuerpo principal del nuevo mensaje.
	 *****/
	{
		$tmpmm  = "{$this->pathMail}/tmp.mm";
		$tmpmm1 = "{$this->pathMail}/tmp.mm1";
		$origen = "{$this->fileMail}";

		$cmd1 = "makemime -m \"multipart/mixed\" -a \"Mime-Version: 1.0\" -o {$tmpmm} {$origen}";
		$cmd2 = "sed '\$d' {$tmpmm} > {$tmpmm1}";
		$cmd3 = "sed -n '2p' {$tmpmm}";
		$salida1 = exec($cmd1);
		$salida2 = exec($cmd2);
		$salida3 = exec($cmd3);
		
		$this->boundary = $this->obtenerBoundary($salida3);
	}
	
	function obtenerBoundary($linea)
	/***
	 * Pre:   $linea, cadena de texto que contiene el boundary.
	 * 
 	 * Post:  $res, únicamente el boundary.
 	 *****/
	{
		$res    = "";
		$buscar = 'boundary="=';
		$pos    = strpos($linea, $buscar);

		if ($pos !== false)
		{
			$tam  = strlen("boundary=\"");
			$inic = $tam-$pos;
			$tam  = strlen($linea);
			$res  = substr($linea, $inic+1, -1);
		} 
		
		return $res;
	}
	
	function finalizarCorreo()
	/***
	 * Pre:   
	 * Post:  se construye el archivo de correo que contiene el mensaje final (traducciones), posibles attach y se le anexa el boundary 
	 *        de finalizacion de mensaje.
	 *****/
	{
		$tmpmm1   = "{$this->pathMail}/tmp.mm1";
		$correo   = "{$this->fileMail}";
		$boundary = "--=".$this->boundary."--";
		
		$cmd1 = "cat {$tmpmm1} > {$correo}";
		$salida1 = exec($cmd1);
	}
	
	function extraeOrigenDestino()
	{
		$this->To   = exec("reformail -X To: < {$this->fileMail}");
		$this->From = exec("reformail -X From: < {$this->fileMail}");
		$this->Subject = $this->filtroLatin1(exec("reformail -X Subject: < {$this->fileMail}"));
	}
	
	function filtroLatin1($str)
	{
		$valor = "";
		$pos = strpos($str, "=?ISO-8859-1?Q?");
		
		if($pos !== false)
		{
			$valor = str_replace("=F3", "ó", $str);
			$valor = str_replace("=E1", "á", $valor);
			$valor = str_replace("=E9", "é", $valor);
			
			$valor = str_replace("=?ISO-8859-1?Q?", "", $valor);
			$valor = str_replace("?= <", "<", $valor);
			$valor = str_replace("?=<", "<", $valor);
			
			
			$valor = utf8_decode($valor);
		}
		else
		{
			$valor = $str;
		}
		
		return $valor;
	}
	
	function infoListaNombre($nombre)
	/***
	 * Pre:  $nombre			Cadena que contiene el nombre de una lista
 	 * Post: $valor.			Array, Registro retornado información del idioma
 	 *		 $valor[$cont]['io']	Idioma original de la lista
 	 *		 $valor[$cont]['i1']	Primer Idioma de traducción de la lista
 	 *		 $valor[$cont]['i2']	Segundo Idioma de traducción de la lista
	 *		 $valor[$cont]['et']	Encabezado de traducción
 	 *		 $valor[$cont]['ra']	ruta de almacenamiento en el servidor de mailman
 	 *		 $valor[$cont]['nb']	Nombre de la lista
 	 *		 $valor[$cont]['tl']	Tipo de lista: Simple o concatenada
 	 *		 $valor[$cont]['mt']	Motor utilizado para la traducción:  google o BabeleFish
 	 * 		 $cont. 		En el array resultante solo hay una fila, de modo que $cont asume solamente el valor de cero
 	 ****/
	{	
		$valor = array();
	
		$con = conectarDb();
	
		if (!$con) 
		{
			die('No se pudo Conectar' . mysql_error());
		}
		else
		{
			$query = sprintf(
	    "SELECT id_lista, nombre_lista, idioma_original, idioma_traduccion_1, idioma_traduccion_2, encabezado_traduccion, ruta_almacenamiento_lista, delimitador_lista,
	    	    posicion_encabezado, motor_traduccion
	     FROM lista 
	     WHERE nombre_lista = '%s'",
		 mysql_real_escape_string($nombre, $con)
		);
			

			$result = mysql_query($query); 			
				
			$cont = 0;
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)) 
			{
				$valor[$cont]['io']	=  $row['idioma_original'];
				$valor[$cont]['i1']	=  $row['idioma_traduccion_1'];
				$valor[$cont]['i2']	=  $row['idioma_traduccion_2'];
				$valor[$cont]['et']	=  $row['encabezado_traduccion'];
				$valor[$cont]['ra']	=  $row['ruta_almacenamiento_lista'];
				$valor[$cont]['nb']	=  $row['nombre_lista'];
				$valor[$cont]['dl']	=  $row['delimitador_lista'];
				$valor[$cont]['id']	=  $row['id_lista'];
				$valor[$cont]['pe']	=  $row['posicion_encabezado'];
				$valor[$cont]['mt']	=  $row['motor_traduccion'];
				
				$cont++;
			}
      
			mysql_close($con);
		}
		return $valor; 
	}

        function infoConfGeneral()
	/***
	 * Pre:   
	 * Post:  $valor,  registro que contiene un select de dos campos con el unico registro que pose la entidad configuracion_general
	 *****/
        {
                $valor = array();

                $con = conectarDb();

                if (!$con)
                {
                        die('No se pudo Conectar' . mysql_error());
                }
                else
                {
                        $query = "SELECT nombre_archivo_tmp, api_key_google
                                  FROM configuracion_general";

                        $result = mysql_query($query);

                        $cont = 0;
                        while($row = mysql_fetch_array($result, MYSQL_ASSOC))
                        {
                                $valor[$cont]['key'] =  $row['api_key_google'];
                                $valor[$cont]['cnf'] =  $row['nombre_archivo_tmp'];

                                $cont++;
                        }

                        mysql_close($con);
                }
                return $valor;
        }

	function infoCodIdioma($idIdioma)
	/***
	 * Pre:   $idIdioma, numero entero el cual es una clave primaria en la tabla idiomas_traduccion
 	 * 
 	 * Post:  $valor,  registro que contiene un unico valor de tipo cadena de dos caracteres que representa un idioma específico
 	 *****/
	{
		$valor = array();
            	$con = conectarDb();

            	if (!$con)
            	{
			die('No se pudo Conectar' . mysql_error());
            	}
            	else
            	{
			$query = sprintf("SELECT codigo_idioma
					  FROM idiomas_traduccion
					  WHERE id_idioma = %d",
					  mysql_real_escape_string($idIdioma, $con)
					);

			$result = mysql_query($query);

			$cont = 0;
			while($row = mysql_fetch_array($result, MYSQL_ASSOC))
			{
				$valor[$cont]['ci'] = $row['codigo_idioma'];
				$cont++;
			}
			mysql_close($con);
		}
		return $valor;
	}
	
	/***
	 * Métodos constructores
	 *****/

	function asigDirAdjuntos($str)
	{
		$this->dirAdjuntos = $str;
	}

	function asigFileMail($str)
	{
		$this->fileMail = $str;
	}
	
	function asigPathMail($str)
	{
		$this->pathMail = $str;
	}
	
	function asigTempMail($str)
	{
		$this->tempMail = $str;
	}

	function asigTagInicio($str)
	{
		$this->tagInicio = $str;
	}
	
	function asigTagFinal($str)
	{
		$this->tagFinal = $str;
	}

	function asigIOriginal($str)
	{
		$this->iOriginal = $str;
	}
	
	function asigITraduUno($str)
	{
		$this->iTraduUno = $str;
	}
	
	function asigITraduDos($str)
	{
		$this->iTraduDos = $str;
	}

	function asigEncabezado($str)
	{
		$this->encabezado = $str;
	}

	function asigPosEncabezado($str)
	{
		$this->posEncabezado = $str;
	}

	function asigMotorTraduccion($str)
	{
		$this->motorTraduccion = $str;
	}

	function asigGoogleApiKey($str)
	{
		$this->googleApiKey = $str;
	}

	/***
	 * Métodos analizadores
	 *****/

	function infoMensajeTxt()
	{
		return $this->mensajeTxt;
	}

	function infoMensajeHtml()
	{
		return $this->mensajeHtml;
	}
	
	function infoTo()
	{
		return $this->To;
	}
	
	function infoFrom()
	{
		return $this->From;
	}
	
	function infoSubject()
	{
		return $this->Subject;
	}
	
	function InfoMsgResultante()
	{
		return $this->msgResultante;
	}	
}
?>
