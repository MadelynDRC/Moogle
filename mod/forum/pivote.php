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
 * El fichero pivote.php contiene la clase Pivote que se encarga de generar un vector de pivotes, los cuales son números enteros que representan posiciones en un 
 * arreglo de caracteres
 * 
 *  Pre: 
 *  Post: 
 *****/
class Pivote
{
	protected $vecPiv;       //toma el rol de vector
	protected $strTotal;     //de tipo cadena
	protected $incremento;   //de tipo entero

	public function __construct()
	{
		$this->vecPiv     = array();
		$this->strTotal   = "";
		$this->incremento = 0;
		$this->vecPiv[]   = -1;
	}

	function asigStrTotal($str)
	{
		$this->strTotal = $str;
	}

	function asigIncremento($valor)
	{
		$this->incremento = $valor;
	}

	function infoVecPiv()
	{
		return $this->vecPiv;
	}

	function construirVecPib()
	/***
	 *  Metodo que parte una cadena cada determinado número de caracteres. donde encuentra un punto antes o depués de cumplirse el limite de dicho numero considera este 
	 *  punto como un pivote y lo guarda en vecPiv[].
	 *  
	 *  Pre: 
	 *  Post: 
	 *****/
	{
		$punto		= ".";
		$tmpVal1	= 0;
		$tmpVal2	= 0;
		$posPivote	= 0;
		$bandera	= 0;
		$i			= 0;
		$n			= 0;
		$posPivote  = $this->incremento;
	
		if($this->incremento < strlen($this->strTotal))
		{
			while($bandera == 0)
			{
				if($posPivote < strlen($this->strTotal))
				{
					$i = $posPivote;

					if(substr($this->strTotal, $i, 1) == $punto)
					{
						$this->vecPiv[] = $i;
						$posPivote = $i + $this->incremento;
					}
					else
					{
						$tmpVal2 = $this->obtenerPosPivAt($i-1);

						if($tmpVal2 > $tmpVal1)
						{
							$this->vecPiv[] = $tmpVal2;
							$posPivote = $tmpVal2 + $this->incremento;
						}
						else
						{
						   $tmpVal2 = $this->obtenerPosPivAd($i-1);
						   $posPivote = $tmpVal2 + $this->incremento;

						   if($tmpVal2 == 0)
							  $bandera = 1;
						}

						if($posPivote >= strlen($this->strTotal))
							$bandera = 1;

						$tmpVal1 = $tmpVal2;
					}
				}
			}
			$this->vecPiv[] = strlen($this->strTotal) - 1;
		}
		else
		{
			$this->vecPiv[] = strlen($this->strTotal) - 1;
		}
    }

	function obtenerPosPivAt($i)
	/***
	 *  Metodo para obtener la posición del pivote hacia atrás del número el cual es el el límite de partición de la cadena 
	 *  
	 *  Pre:   $i. subíndice de la cadena donde esta el límite momentáneo de partición
	 *  Post:  $valor.  posición en la cual se encontró la primera ocurrencia del caracter punto mirando haca atras de $i
	 *****/
	{
		$valor   = 0;
		$objChar = "";
		$objStr1 = ".";
		$objStr2 = "";

		for($cont=$i;$cont>0;$cont--)
		{
			$objChar = substr($this->strTotal, $cont, 1);
			$objStr2 = $objChar;

			if(strcmp($objStr1, $objStr2) == 0)
			{
				$valor = $cont;
				break;
			}
		}

		return $valor;
	}

	function obtenerPosPivAd($i)
	/***
	 *  Método para obtener la posición del pivote hacia adelante del número el cual es el el límite de partición de la cadena 
	 *  
	 *  Pre:   $i. subíndice de la cadena donde esta el límite momentáneo de partición
	 *  Post:  $valor.  posición en la cual se encontró la primera ocurrencia del caracter punto mirando haca adelante de $i
	 *****/
	{
		$valor = 0;
		$objChar;
		$objStr1 = ".";
		$objStr2 = "";

		for($cont=$i;$cont<strlen($this->strTotal);$cont++)
		{
			$objChar = substr($this->strTotal, $cont, 1);
			$objStr2 = $objChar;

			if(strcmp($objStr1, $objStr2))
			{
				$valor = $cont;
				break;
			}
		}

		return $valor;
	}
}
?>