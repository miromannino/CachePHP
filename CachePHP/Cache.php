<?php
/**
 * CachePHP 
 * This work is licensed under the Creative Commons Attribution 2.5 Italy License.
 * Author: Miro Mannino
 * */

/*
 * 
 * //descrizione setCacheFile
 * //descrizione extendValidity
 * 
 * Un file di cache dipende da un tempo di vita predefinito che fa in modo
 * da non lasciare sulla cache sporcizia per molto tempo (predefinito è 1 settimana).
 * Il controllo che viene fatto per rimuovere i file più vecchi del tempo di vita
 * viene svolto dal garbage collector ogni tanto (predefinito un giorno).
 * 
 * Il garbage collector può comunque essere chiamato quando si vuole
 * ed eliminare i file più vecchi di ttl tempo specificato al momento della
 * chiamata della funzione.
 * 
 * Una volta che è richiesto un file questo file può non essere più valido
 * perchè ha superato il timeToInvalidate. Il timeToInvalidate non è un opzione
 * statica ma viene scelta chiamando la funzione setTimeToInvalidate
 *  
 * Il file può anche dipendere da altri file oltre che dal timeToInvalidate
 * e quindi in questo viene fatto l'ulteriore verifica che il file sia
 * più recente dei file da cui dipende. La lista dei file viene scelta
 * chiamando la funzione setDependFile
 * 
 */

define('CachePHP_FileKeyPattern', '/^[0-9a-zA-Z-_]{1,50}$/');
define('CachePHP_SubSectionPattern', '/^([0-9a-zA_Z-_]{1,20}\/)*([a-zA_Z-_.]{1,20})$/');

/**
 * Tempo di vita dei file in cache, superato il tempo di vita il
 * file viene eliminato
 * se è 0 tutti i files vengono lasciati sulla cache
 */
define('CachePHP_TTL', 604800); //una settimana

/**
 * Periodo che il garbage collector deve aspettare per effettuare un 
 * controllo. Viene fatto un controllo sulla data di modifica della
 * cartella di cache e se supera il tempo stabilito esegue una 
 * scansione. Vengono eliminati tutti i file più vecchi di timeToLive
 * se è 0 il garbage collector non viene mai eseguito
 */
define('CachePHP_GCTime', 86400); //una giorno

class CachePHP_Cache {
	
	/**
	 * Cartella dove vengono salvati i file di cache. Deve avere impostati
	 * i permessi di scrittura altrimenti causa errori.
	 */
	private $cacheFolder;
	
	/**
	 * Sottosezione della cache
	 * In questo modo si può specificare di non salvare il file di cache
	 * nella cartella principale della cache ma in una sottocartella,
	 * in questo modo per grosse quantità di files può essere più efficiente
	 * separare in categorie e quindi sottosezioni i file in cache.
	 * Predefinito è '' e quindi tutti i file di cache vengono salvati
	 * nella cartella princpiale
	 * deve essere una path di questo tipo nome1/nome2/nome3/.../nomeN
	 */
	private $subSection = null;
	
	/**
	 * Nome univoco che viene attribuito al file di cache.
	 * Se dobbiamo ad esepmio cachare una pagina welcome.php possiamo ad
	 * esempio usare come cacheFileKey "welcome", se invece abbiamo una pagina
	 * getChapter.php al quale gli si dice il capitolo da visionare con
	 * una variabile get allora la cacheFileKey non potrà essere solamente
	 * "chapter" ma ad esempio "chapter3" per indicare il terzo capitolo
	 */
	private $cacheFileKey = null;
	
	private $cacheFilePath = null;
	private $cacheFileFolderPath = null;
	
	/**
	 * Tempo per la quale il file rimane valido
	 * Il tempo è un valore che varia fra 0 e timeToLive.
	 * Nel caso sia 0 il file è sempre valido e quindi non viene mai eliminato
	 * Nel caso sia un valore maggiore di timeToLive il valore viene settato a 0
	 * */
	private $timeToInvalidate = 0;
	
	/**
	 * I file da cui dipende il file di cache. Se è null vuol dire che non
	 * dipende da nessun file.
	 */
	private $dependFile = null;
	
	/*----------------------------------------------------------------*/
	
	public function CachePHP_Cache($cacheFolder){
		$cf = realpath($cacheFolder);
		if (!file_exists($cf) | !is_dir($cf)) throw new Exception('cacheFolder path not valid');
		$this->cacheFolder = preg_replace('/\/$/', '', $cf);
		if ((time() - filemtime($this->cacheFolder)) > CachePHP_GCTime) $this->gc();
	}
	
	public function setCacheFile($key, $subSection = null){
		if (!is_string($key) || !preg_match(CachePHP_FileKeyPattern, $key)){
			throw new Exception('key is not a valid name');
		}
		if ($subSection != null){
			if (!is_string($subSection) || !preg_match(CachePHP_SubSectionPattern, $subSection)){
				throw new Exception('subSection is not a valid name');
			}
		}
		$this->cacheFileKey = $key;
		$this->subSection = $subSection;
		$this->cacheFileFolderPath = $this->cacheFolder;
		if ($subSection != null) $this->cacheFileFolderPath .= '/' . $subSection;
		$this->cacheFilePath = $this->cacheFileFolderPath . '/' . $key;
		$this->timeToInvalidate = 0;
		$this->dependFile = null;
		return true;
	}
	
	public function setTimeToInvalidate($tti){
		if ($tti < 0) throw new Exception('timeToInvalidate must be a valid time number');
		$this->timeToInvalidate = $tti;
	}
	
	public function setDependFile($arrFile = null){
		if ($arrFile != null && !is_array($arrFile)) throw new Exception('array required');
		$this->dependFile = $arrFile;
	}
	
	public function get(&$content){
		$content = '';
		
		if ($this->cacheFilePath == null) throw new Exception('cacheFile is not set');
		
		clearstatcache();
		
		if (!file_exists($this->cacheFilePath)) return false;
		
		$mtime = filemtime($this->cacheFilePath);
		
		if ($this->timeToInvalidate > 0){
			if (time() - $mtime >= $this->timeToInvalidate) return false;
		}
		
		if ($this->dependFile != null){
			foreach ($this->dependFile as $f){
				if ($mtime < @filemtime($f)) return false;
			}
		}
		
		$ris = @file_get_contents($this->cacheFilePath);
		
		if ($ris === false){
			throw new Exception('cannot read file');
			return false;
		}
		
		$content = $ris;
		return true;
	}
	
	public function put($content){
		if ($this->cacheFilePath == null) throw new Exception('cacheFile is not set');
		
		if (!file_exists($this->cacheFileFolderPath)){
			if (! @mkdir($this->cacheFileFolderPath)) throw new Exception('cannot create subsection folder');
			if (!@chmod($this->cacheFileFolderPath, 0777)) throw new Exception('cannot change subsection folder mode');
		}
		
		return (@file_put_contents($this->cacheFilePath, $content, LOCK_EX)) ? true : false;
	}
	
	public function beginOutput(){
		ob_start();
		ob_implicit_flush(false);
	}
	
	public function endOutput(){
		$o = ob_get_clean();
		$this->put($o);
		echo($o);
	}
	
	public function extendValidity(){
		if ($this->cacheFilePath == null) throw new Exception('cacheFile is not set');
		if (!file_exists($this->cacheFilePath))	return false; //Il file non è più in cache perchè magari è stato già cancellato, non è un errore
		return @touch($this->cacheFilePath);
	}
	
	public function gc($ttl = CachePHP_TTL){
		if ($ttl < 0) throw new Exception('timeToLive must be a valid time number');
		if (!@touch($this->cacheFolder)) throw new Exception('cannot change the cache folder mtime');
		clearstatcache();
		$this->gc_folder($this->cacheFolder, $ttl);
	}
	
	private function gc_folder($dir, $ttl){
		$t = time();
		if ($d = opendir($dir)){
			while (($fn = readdir($d)) !== false){
				if ($fn != '.' && $fn != '..'){
					$f = $dir . '/' . $fn;
					if (is_dir($f)){
						$this->gc_folder($f, $ttl);
						if ($this->numFiles($f) == 0) @rmdir($f);
					}else{
						if ($t - filemtime($f) > $ttl) @unlink($f);
					}
				}
			}
			closedir($d);
		}
	}
	
	private function numFiles($dir) {
		$n = 0;
		if ($d = opendir($dir)){
			while (($fn = readdir($d)) !== false){
				if ($fn != '.' && $fn != '..') $n++;
			}
			closedir($d);
		}
		return $n;
	}

	public function setShowErrorMessages($v){
		$this->showErrorMessages = ($v) ? true : false;
	}
	
	private function error($str){
		if ($this->showErrorMessages) echo('<b>CachePHP Error:</b> ' . $str . '<br/>');
	}
	
	
}

?>
