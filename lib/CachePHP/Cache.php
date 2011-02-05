<?php
/**
 * CachePHP 
 * This work is licensed under the Creative Commons Attribution 2.5 Italy License.
 * Author: Miro Mannino
 * */

define('CachePHP_FileKeyPattern', '/^([a-zA-Z0-9-_\.]\/?)+$/');

/**
 * Key lifetime
 * if 0 all keys are left on the cache
 */
define('CachePHP_TTL', 604800); //a week

/**
 * Time that the garbage collector has to wait to make a control.
 * A check is done when the modification date of cache folder exceeds
 * the allotted time. GC deletes all files older than TTL.
 * if it is 0 if the garbage collector is never executed
 */
define('CachePHP_GCTime', 86400); //una giorno

class CachePHP_Cache {
	
	/**
	 * Cache folder
	 */
	private $cacheFolder;
	
	/**
	 * Unique name of the file in the cache folder (key)
	 */
	private $cacheKey = null;
	
	/**
	 * Time for which the file remains valid
	 * Time is a value ranging between 0 and timeToLive.
	 * If the file is 0 is always valid and therefore will never be deleted
	 * If there is a value greater than timeToLive the value is set to 0
	 * */
	private $deadLine = 0;
	
	/**
	 * The file to which the cache file depends. If it is null means that no
	 * depend on any file.
	 */
	private $dependance = null;
	
	/**
	 * If set to true the file is always declared invalid
	 * It is useful to concatenate events such as the fact that
	 * a file is invalid if another page generated a cache miss.
	 * */
	private $invalid = false;
	
	/*----------------------------------------------------------------*/
	
	public function CachePHP_Cache($cacheFolder){
		$cf = realpath($cacheFolder);
		if(!file_exists($cf) | !is_dir($cf)) throw new Exception('CachePHP: cacheFolder path not valid');
		$this->cacheFolder = preg_replace('@/+$@', '', $cf);
		if((time() - filemtime($this->cacheFolder)) > CachePHP_GCTime) $this->gc();
	}
	
	public function setKey($key, $clear = true){
		$key = preg_replace(array('/^\/+/','/\/+$/','/\/{2,}/'), array('','','/'), $key);
		if(!preg_match(CachePHP_FileKeyPattern, $key)) throw new Exception('CachePHP: key is not a valid name');
		$this->cacheKey = $this->cacheFolder . '/' . $key;
		if($clear){
			$this->dependance = null;
			$this->deadLine = 0;
			$this->invalid = false;
		}
		return true;
	}
	
	public function setDeadLine($tti){
		if($tti < 0) throw new Exception('CachePHP: deadLine must be a valid time number');
		$this->deadLine = $tti;
	}
	
	public function setInvalid($v){
		$this->invalid = ($v) ? true : false;
	}
	
	public function setDependance($dep){
		if($dep == null) $this->dependance = null;
		else if(is_array($dep)) $this->dependance = $dep;
		else if(is_string($dep)) $this->dependance = array($dep);
		else throw new Exception('CachePHP: only array, string or null is permitted');
	}
	
	public function addDependance($dep){
		if(is_array($dep)){
			if($this->dependance == null){
				$this->dependance = $dep;
			}else{
				$this->dependance = array_unique(array_merge($this->dependance,$dep));
			}
		}else if(is_string($dep)){
			if($this->dependance == null) $this->dependance = array($dep);
			else if(array_search($dep, $this->dependance) === false) array_push($this->dependance, $dep);
		}else{
			throw new Exception('CachePHP: only string or array is permitted');
		}
	}
	
	public function removeDependance($dep){
		if(is_array($dep)){
			if($this->dependance != null){
				$this->dependance = array_diff($this->dependance, $dep);
			}
		}else if(is_string($dep)){
			if($this->dependance != null){
				$this->dependance = array_diff($this->dependance, array($dep));
			}
		}else{
			throw new Exception('CachePHP: only string or array is permitted');
		}
	}
	
	public function get(&$content){
		if($this->cacheKey == null) throw new Exception('CachePHP: cacheKey is not set');
		
		clearstatcache();
		
		if($this->invalid) return false;
		
		if(!file_exists($this->cacheKey)) return false;
		
		$mtime = filemtime($this->cacheKey);
		
		if($this->deadLine > 0){
			if(time() - $mtime >= $this->deadLine) return false;
		}
		
		if($this->dependance != null){
			foreach ($this->dependance as $f){
				if($mtime < @filemtime($f)) return false;
			}
		}
		
		$ris = @file_get_contents($this->cacheKey);
		
		if($ris === false) throw new Exception('CachePHP: cannot read file');
		
		$content = $ris;
		return true;
	}
	
	public function put($content){
		if($this->cacheKey == null) throw new Exception('CachePHP: cacheFile is not set');
		
		$folder = dirname($this->cacheKey);
		if(!file_exists($folder)){
			if(! @mkdir($folder, 0777, true)) throw new Exception('CachePHP: cannot write to cache folder. Key: ' . $this->cacheKey);
		}
		
		if(FALSE === @file_put_contents($this->cacheKey, $content, LOCK_EX)) throw new Exception('CachePHP: cannot write to cache folder. Key: ' . $this->cacheKey);
	}
	
	public function printOrBegin(){
		if($this->get($c)){
			echo($c);
			return false;
		}else{
			ob_start();
			ob_implicit_flush(false);
			return true;
		}
	}

	public function beginOutput(){
		ob_start();
		ob_implicit_flush(false);
	}
	
	public function endOutput(){
		$o = ob_get_flush();
		$this->put($o);
	}
	
	public function extendValidity(){
		if($this->cacheKey == null) throw new Exception('CachePHP: cacheFile is not set');
		if(file_exists($this->cacheKey)){
			if(! @touch($this->cacheFilePath)) throw new Exception('CachePHP: can\'t touch files in the cache folder');;
		}
	}
	
	public function gc($ttl = CachePHP_TTL){
		if($ttl < 0) throw new Exception('CachePHP: timeToLive must be a valid time number');
		if(!@touch($this->cacheFolder)) throw new Exception('CachePHP: can\'t touch files in the cache folder');;
		clearstatcache();
		$this->gc_folder($this->cacheFolder, $ttl);
	}
	
	private function gc_folder($dir, $ttl){
		$t = time();
		if($d = opendir($dir)){
			while (($fn = readdir($d)) !== false){
				if($fn != '.' && $fn != '..'){
					$f = $dir . '/' . $fn;
					if(is_dir($f)){
						$this->gc_folder($f, $ttl);
						if($this->numFiles($f) == 0) @rmdir($f);
					}else{
						if($t - filemtime($f) > $ttl) @unlink($f);
					}
				}
			}
			closedir($d);
		}
	}
	
	private function numFiles($dir) {
		$n = 0;
		if($d = opendir($dir)){
			while (($fn = readdir($d)) !== false){
				if($fn != '.' && $fn != '..') $n++;
			}
			closedir($d);
		}
		return $n;
	}
	
}

?>
