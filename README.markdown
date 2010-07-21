CachePHP
===========

Guide generali
--------------

### Istanziare la classe `achePHP_Cache($cacheFolder [, $doGCCheck = true])`

La classe per essere istanziata ha bisogno di un percorso che specifica il path della cartella usata per la cache. Tale cartella deve essere scrivibile.

	$s = new CachePHP_Cache('cachefolder');

Si può fare in modo che al momento dell'istanziazione della classe non venga fatto il controllo che stabilisce se eseguire o meno il garbage collector settando a `false` il secondo argomento. In questo modo, nel caso ci siano più istanze della classe nella stessa pagina, si può velocizzare la creazione della classe stessa.

### Specificare il file di cache

Ogni dato viene salvato su un **file di cache** che risiede dentro la cartella cachefolder. Tali file hanno un nome che viene specificato con un'opportuna funzione `setFileCache`.

#### `setCacheFile($key [, $subsection = '' ] [, $clear = true])`

 Il **`key`** rappresenta il nome che viene attribuito al file di cache. Se dobbiamo ad esempio salvate una pagina welcome.php possiamo usare come key "welcome", se invece abbiamo una pagina getChapter.php al quale gli si dice il capitolo da visionare _(ad esempio con una variabile get)_ allora la key non potrà essere solamente "chapter" ma ad esempio ("chapter" . $numeroCapitolo). 

La **`subsection`** è un percorso del tipo nome1/nome2/.../nomeN. In questo modo si può specificare di non salvare il file di cache nella cartella principale della cache ma in una sottocartella in modo che sia possibile separare le diverse tipologie di file ed avere quindi anche la libertà di poter chiamare con la stessa key due file che si trovano in sezioni diverse.

**Clear** serve per specificare se deve resettare le impostazioni di dipendenze e deadLine e invalid. Predefinito è true e quindi il setCacheFile setta a 0 il deadline e mette a null le dipendeze e a false il flag invalid

Esempi:

	$s->setCacheFile('welcome'); //L'output verrà salvato in cacheFolder/welcome

	$numeroCapitolo = 3;
	$s->setCacheFile("chapter" . $numeroCapitolo); //L'output verrà salvato in cacheFolder/chapter3

	$s->setCacheFile('welcome', 'pag/html'); //L'output verrà salvato in cacheFolder/pag/html/welcome
	
E' possibile usando la stessa classe specificare un file usando questa funzione lavorarci e successivamente specificarne un altro.

### Validità di un file

#### deadline

Un file può avere un deadline, cioè avere un certo tempo di vita. Si pensi ad esempio ad una pagina che mostra le visite, essa dovrà essere aggiornata ogni volta che arriva un nuovo visitatore mentre è più efficente ad esempio che la pagina viene ricalcolata solo ogni ora. Un file di cache quindi può non essere più valido perchè ha superato il deadLine. Il deadLine viene scelto chiamando la funzione `setDeadLine($t)` dove `$t` è espresso in secondi. Un tempo 0 specifica che il file non ha deadline.

Esempio:

	$s->setCacheFile('welcome');
	$s->setDeadLine(3600); 	//Nel caso welcome sia più vecchio di un ora viene generato un cache miss

#### dependances

Il file può anche dipendere da alcuni file e quindi in questo caso viene fatta l'ulteriore verifica che il file sia più recente dei file da cui dipende.
Possiamo pensare ad esempio ad un menù che viene descritto con un file xml e trasformato con un file xsl, tale menù non ha quindi una scadenza temporale ma può rimanere sulla cache se i file xml e xsl non cambiano. La lista dei file viene modificata dalle funzioni **`setDependance`**, **`addDependance`** e **`removeDependance`**. Tutte queste funzioni accettano sia un array di stringhe che una stringa. `setDependance` inoltre accetta anche null nel caso si voglia svuotare la lista.

Esempio:

	$s->setDependance('prova1.txt'); 
	//Dependance: ('prova1.txt')
	$s->addDependance('prova2.txt');
	//Dependance: ('prova1.txt', 'prova2.txt')
	$s->addDependance('prova1.txt');
	//Dependance: ('prova1.txt', 'prova2.txt')
	$s->addDependance(array('prova3.txt', 'prova4.txt', 'prova1.txt'));
	//Dependance: ('prova1.txt', 'prova2.txt', 'prova3.txt', 'prova4.txt')
	$s->removeDependance('prova1.txt');
	//Dependance: ('prova2.txt', 'prova3.txt', 'prova4.txt')
	$s->removeDependance(array('prova1.txt', 'prova3.txt'));
	//Dependance: ('prova2.txt', 'prova4.txt')
	$s->addDependance(array('prova1.txt', 'prova3.txt', 'prova4.txt'));
	/* Dependance: ('prova2.txt', 'prova4.txt', 'prova1.txt', 'prova3.txt') */

#### invalid Flag

Nel caso in cui l'invalidazione non dipendesse da un file o dal tempo è possibile specificare se il file è invalido usando la funzione **`setInvalid($v)`** che nel caso `$v` sia true fa in modo che tutte le volte che viene richiesto il file di cache si generi un cache miss e quindi in pratica fa in modo la get fallisca.

Esempio:

	if ($_GET['rigenera'] == true) $s->setInvalid(true);
	$s->get($cont); //la get restituirà sicuramente false

### Get & Put

Una volta specificato il controllo tipico che viene fatto è: "se il file esiste ed è valido restituiscilo perchè altrimenti dovrò ricalcolarmi l'output".
Quest'operazione viene fatta dalla **`get($content)`** che restituisce `true` se il file esiste in cache ed è valido o `false` nel caso non esista o non sia più valido. `$content` viene passata per riferimento, quindi nel caso in cui get restituisce `true` dentro $content c'è la stringa che contiene il file in cache. Nel caso in cui restituisce `false` `$content` non viene modificata.

Nel caso la `get` restituisca `false` bisogna ricalcolarsi quindi l'output, una volta calcolato bisogna salvarlo con la funzione **`put($content)`**. Questa funzione salva nel file di cache specificato dalla key (e subsection) la stringa `$content`.

Esempio:

	$s = new CachePHP_Cache('cachefolder');
	$s->setCacheFile('welcome');
	$s->setDeadLine(3600); 	//welcome fa vedere i visitatori, allora aggiorniamola solo ogni ora
	$s->setDependance('welcome.html'); //nel caso comunque cambi welcome aggiorniamo subito
	if($s->get($con)){
		//cache hit
		echo($con);
	}else{
		//cache miss
		$out = trasformaHTML('welcome.html', "{ valorecontatore = '$numVisite' }");
		$s->put($out);
		echo($out);
	}

### Output

#### beginOutput & endOutput

Nel caso non si voglia mettere tutto il contenuto dell'output su una variabile ma si voglia fare come al solito usando liberamente gli echo il put diventa debole, in questo caso esitono le funzioni **`beginOutput()`** e **`endOutput()`**. Naturalmente si capisce bene già dal nome cosa fanno.

Nell'esempio di prima l'ultima parte diventa:
	
	if($s->get($con)){
		echo($con);
	}else{
		$s->beginOutput();
		echo trasformaHTML('welcome.html', "{ valorecontatore = '$numVisite' }");
		$s->endOutput();
	}

beginOutput non fa altro che impedire che gli echo vengano eseguiti e di conseguenza endOutput si ritrova nel buffer tutto l'output che può prendere, salvare e stampare.

#### printOrBegin

Rimane scomodo ripetere la prima parte dell'if che sostanzialmente è solamente un istruzione che stampa il contenuto ricevuto dalla `get`. Esiste quindi un'ulteriore funzione **`printOrBegin()`** che stampa il file in cache se esiste ed è valido e restituisce `false`, oppure non sampa niente e restituisce `true`. Questo permette di compattare il codice, l'esempio di prima diventa infatti:

	if($s->printOrBegin()){
		echo trasformaHTML('welcome.html', "{ valorecontatore = '$numVisite' }");
		$s->endOutput();
	}


### Garbage Collector

Un file di cache dipende da un tempo di vita predefinito che fa in modo da non lasciare sulla cache sporcizia per molto tempo _(predefinito è 1 settimana)_. Il controllo che viene fatto per rimuovere i file più vecchi del tempo di vita viene svolto dal garbage collector ogni tanto _(predefinito dopo un giorno)_. Il controllo per capire se è passato sufficiente tempo per eseguire il garbage collector viene svolto al momento della creazione della classe CachePHP_Cache. Il garbage collector può comunque essere chiamato quando si vuole usando la funzione `gc($ttl)` ed eliminare quindi i file più vecchi di `ttl` tempo.

Esempi:

	$s->setCacheFile('welcome'); //se nessuno visualizza il sito da più di una settimana viene eseguito il gc

	$s->gc(3600); //svuota tutti i file più vecchi di un ora;

	$s->gc(0); //svuota tutta la cache

