<?php
	require("../CachePHP/Cache.php");
	$s = new CachePHP_Cache("testcache");
	if ($s == null) echo "df";
	$s->setCacheFile('ciao', 'miro');
	$s->setTimeToInvalidate(0);
	$s->setDependFile(array('prova1.txt', 'prova2.txt'));
	
	/*
	$v = 'ciao';
	$n = rand(100,10000);
	for ($i=0; $i<$n; $i++){
		$v .= rand(100,1000);
	}
	
	$s->put($v);
	$s->get($c);
	
	echo 'test coerenza: ' . (($v == $c) ? 'true' : 'false') . '<br/>';
	*/
	/*
	if($s->get($c)){
		echo 'cache hit!<br/>';
		echo $c;
	}else{
		echo 'cache miss!<br/>';
		$t = time() . ' ' . file_get_contents('prova.txt');
		echo $t;
		$s->put($t);
	}
	*/
	if($s->get($c)){
		echo 'cache hit!<br/>';
		echo $c;
	}else{
		echo 'cache miss!<br/>';
		$s->beginOutput();
		$t = time();
		echo $t;
		$s->endOutput();
	}
	
	$s->gc(10);
	
	

?>
