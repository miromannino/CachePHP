CachePHP
===========

Guide generali
--------------

### Istantiate the class `CachePHP_Cache($cacheFolder [, $doGCCheck = true])`

The class to be instantiated, it needs a path that specifies the path to the folder used for the cache.
This folder must be writable.

	$s = new CachePHP_Cache('cachefolder');

### Specify the key

The key is that file that is saved in the cache folder. This key is specified by the **`setKey`** function.
The key may just be a name or a path. Intuitively it should be a parameter in the constructor of the class but
was chosen to implement it in this way in order to use the same instance of the class to work on different files
each time without having to re-instantiate a new class again.

#### `setKey($key [, $clear = true])`

The key is the name that is assigned to the file located in the cache and must be unique in some way to avoid
causing collisions. If we for example save a page welcome.php we can use as a key "welcome", but if we have a
page that shows a particular chapter getChapter.php then the key can not be only "chapter", but it should also
include the chapter number. The key can still be a path, so you can separate the different types of files saved
in different sections.

Clear parameter is used to specify whether you need to reset the settings of dependency, deadline and invalid.
The default value is true and by default the deadline is set to 0, addictions to null and invalid flag to false.

Example:

	$s = new CachePHP_Cache('/path/to/cacheFolder');

	$s->setKey('welcome'); //Output will be saved to '/path/to/cacheFolder/welcome'

	$chapterNumber = 3;
	$s->setKey("chapter" . $chapterNumber); //Output will be saved to '/path/to/cacheFolder/chapter3'

	$s->setKey('html/welcome'); //Output will be saved to '/path/to/cacheFolder/html/welcome'

### Key validity

#### deadline

A key may have a deadline, that have a certain life time. An example is a page that shows visits,
it can be updated whenever a new visitor arrives while it is more efficient for example, that the
page is recalculated once every hour. A key can then be no longer valid because the deadline has passed.
The deadline is chosen by calling the function `setDeadLine($t)` where `$t` is in seconds.
Value 0 specifies that the file has no deadline.

Example:

	$s->setKey('welcome');
	$s->setDeadLine(3600); 	//If welcome is older than one hour will generate a cache miss

#### dependances

The key may depend on some files and then the cache makes sure that the key is the latest of the file on which it depends.
For example, a menu that is described in an xml file and transformed with a xsl file does not depend on time but may remain
on the cache until you change the files on which it depends. The file list is changed by the functions **`setDependance`**, 
**`addDependance`**, **`removeDependance`**. All these functions accept either a string or an array of strings.
**`SetDependance`** also accepts null if you want to empty the list.

Example:

	$s->setDependance('test1.txt'); 

	//Dependance: ('test1.txt')

	$s->addDependance('test2.txt');

	//Dependance: ('test1.txt', 'test2.txt')

	$s->addDependance('test1.txt');

	//Dependance: ('test1.txt', 'test2.txt')

	$s->addDependance(array('test3.txt', 'test4.txt', 'test1.txt'));

	//Dependance: ('test1.txt', 'test2.txt', 'test3.txt', 'test4.txt')

	$s->removeDependance('test1.txt');

	//Dependance: ('test2.txt', 'test3.txt', 'test4.txt')

	$s->removeDependance(array('test1.txt', 'test3.txt'));

	//Dependance: ('test2.txt', 'test4.txt')

	$s->addDependance(array('test1.txt', 'test3.txt', 'test4.txt'));

	/* Dependance: ('test2.txt', 'test4.txt', 'test1.txt', 'test3.txt') */

#### Invalid Flag

In the event that the invalidation is not due to a file or from the time you can specify
whether the key is no longer valid by using the function `** setInvalid ($ v )`**. If `$ v` is true
requires a key will generate a cache miss.

Example:

	if ($_GET['regenerate'] == true) $s->setInvalid(true);
	$s->get($cont); //get always return false

### Get & Put

After you specify the dependencies of the key, the cache does this check: if the file exists and
is valid return it because otherwise you will have to recalculate the output."
This operation is done by **`get($content)`** that returns `true` if the key exists
in cache and is valid or `false` in the case does not exist or is no longer valid.
`$content` is passed by reference, so if get returns `true` in `$content` is the string that contains
the contents of the key. In the event that returns `false` then `$content` is not changed.

If the `get` return `false` must re-calculate the output, once calculated can be saved with the
function **`put($content)`**. This function saves the key that has the value of the string `$content`.

Example:

	$s = new CachePHP_Cache('cachefolder');
	$s->setKey('welcome');
	$s->setDeadLine(3600); //welcome page is valid only for an hour
	$s->setDependance('welcome.html'); //if welcome.html change the welcome page will be changed
	if($s->get($con)){
		//cache hit
		echo($con);
	}else{
		//cache miss
		$out = transformHTML('welcome.html', array('visitors' => $numVis));
		$s->put($out);
		echo($out);
	}

### Output

#### beginOutput & endOutput

If you do not want to put all the contents output to a variable, but you want freely use the echo function,
the `put` function becomes weak, in this case the functions **`beginOutput()`** and **`endOutput()`** will help you.
Of course you already understand very well from the name what they do.

The above example becomes:
	
	if($s->get($con)){
		//cache hit
		echo($con);
	}else{
		//cache miss
		$s->beginOutput();
			echo transformHTML('welcome.html', array('visitors' => $numVis));
		$s->endOutput();
	}

beginOutput divert the output to a temporary buffer and thereby endOutput print this buffer as a normal `echo` function.

#### printOrBegin

Remains inconvenient to repeat the first part of the `if` that is basically just a statement that print the
contents with `get` function. So there is an additional function **`printOrBegin()`** that prints the contents
if it exists and is valid by returning `false`, or doesn't print anything by returning `true`.
This allows a new simple construct, the example above now can be written:

	if($s->printOrBegin()){
		echo transformHTML('welcome.html', array('visitors' => $numVis));
		$s->endOutput();
	}


### Garbage Collector

A key depends on a default life time so as not to leave dirt on the cache folder with keys
that are not used for a long time _(default is 1 week)_. The garbage collector are executed 
sometimes _(default after a day)_. The check to see if enough time has passed to run the garbage
collector is done when you istantiate the class. The garbage collector can also be called
when you want using the function **`gc($ttl)`** that delete the keys older than `ttl` time.

Example:

	$s->gc(3600); //delete all keys older than an hour;

	$s->gc(0); //delete all keys in cache

