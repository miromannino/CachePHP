<?php
	require('../../lib/CachePHP/Cache.php');
	$s = new CachePHP_Cache('testcache');
	$s->setKey('miro/ciao');
	$s->setDeadLine(5);
	
	/*$s->setDependance('prova1.txt');
	$s->addDependance('prova2.txt');
	$s->addDependance('prova1.txt');
	$s->addDependance(array('prova3.txt', 'prova4.txt', 'prova1.txt'));
	$s->removeDependance('prova1.txt');
	$s->removeDependance(array('prova1.txt', 'prova3.txt'));
	$s->addDependance(array('prova1.txt', 'prova3.txt', 'prova4.txt'));
	*/
	
	//$s->setInvalid(true);
	
	/* Dependance: ('prova2.txt', 'prova4.txt', 'prova1.txt', 'prova3.txt') */
	
	/*
	if($s->get($c)){
		echo 'cache hit!<br/>';
		echo $c;
	}else{
		echo 'cache miss!<br/>';
		$t = time() . ' ' . file_get_contents('prova.txt');
		$s->put($t);
		echo $t;
	}
	*/
	
	if($s->get($c)){
		echo 'cache hit!<br/>';
		echo $c;
	}else{
		echo 'cache miss!<br/>';
		$s->beginOutput();
			echo time();
		$s->endOutput();
	}

/*

	if($s->printOrBegin()){
		echo time();
		$s->endOutput();
	}?>
*/
