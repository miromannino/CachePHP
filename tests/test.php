<?php
	require("../CachePHP/Cache.php");
	$s = new CachePHP_Cache("testcache");
	if ($s == null) echo "df";
	$s->setCacheFile('ciao', 'miro');
	$s->setDeadLine(0);
	
	$s->setDependance('prova1.txt');
	$s->addDependance('prova2.txt');
	$s->addDependance('prova1.txt');
	$s->addDependance(array('prova3.txt', 'prova4.txt', 'prova1.txt'));
	$s->removeDependance('prova1.txt');
	$s->removeDependance(array('prova1.txt', 'prova3.txt'));
	$s->addDependance(array('prova1.txt', 'prova3.txt', 'prova4.txt'));
	
	/* Now the array is: ([0] => prova2.txt, [1] => prova4.txt, [2] => prova1.txt, [3] => prova3.txt) */
	
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

?>
