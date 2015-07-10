# Wolf-Search-Engine
**Create your own custom search engine, easily and quickly!**

## What is WSE?
WSE permits you to create customized search engines in a few lines.

* It can be used for all types of data you can search inside a database.
* It is scalable made and object-oriented.
* It is protected against **SQL injections**

## How does WSE works?
WSE functions with a **weighted rule** system:
* You have to define the rules of your engine, the ones that will make the engine interested or not to an element
* Each rule is associated with a weight/importance indice you define; the most important it is to match the rule, the heavier you define its weight.
* All elements have a weight, which is nil if it does not match any rule
* Each element that matches the rule sees the weight of this rule added to it
* Finally, elements are sorted by descending weight; so, the return is an Array ordered by relevance.

## How to use WSE?

You will need three simple steps before you can perform a search:
### 1. Instantiate
First, create an instance of WSE using a PDO object:
```php
$db = new PDO('mysql:host=localhost;dbname=db','user','password');
$engine = new WSE($db);
```

### 2. Set rules
Set the rules of your engine and the impact its gonna have on the search (weight).
At list one rule must be added to the engine.
Several methods exists that allow you adding a rule:

#### 2.1. The addCustom Method
The basic way of setting a rule is to use `WSE::addCustom($query, $table, $weight=1)` method.

*Exemple: assuming the current word is "captain132":*
```php
$engine->addCustom('SELECT * FROM `:table` WHERE `nick`=":rowval"', 'users', 10);
//Will perform
$db->query('SELECT * FROM `users` WHERE `nick`="captain132"');
```
Note that addCustom will **always and strictly** replace *:table* by `$table`'s value, and *:rowval* by the current word's value; no quotes are added.

#### 2.2. The addEquals Method
The addEquals method (`WSE::addEquals($row, $table, $weight=1)`) will consider a match only if the current word's value is exactly the same as the `$row` value into `$table`.

*Exemple: assuming the current word is "house":*
```php
$engine->addEquals('type', 'housing', 10);
//Is the same as
$engine->addCustom('SELECT * FROM `:table` WHERE `type`=":rowval"', 'housing', 10);
```

#### 2.3. The addLikes Method
The addLikes method (`WSE::addLikes($row, $table, $weight=1, $notequal=true)`) will consider a match if the current word's value is contained inside the `$row` value into `$table`.
Note that if `$notequal` is set to true (default), addLike will ignore the case where the current word's value is exactly the same as `$row`, which is recommended unless the engine has no *addEquals* like rules.

*Exemple: assuming the current word is "pi":*
```php
$engine->addLikes('word', 'dictionnary', 10);
//Is the same as
$engine->addCustom('SELECT * FROM `:table` WHERE `word` LIKE %":rowval"%', 'dictionary', 10);
//And it works for "pirate","apiarist",etc..., but not for "pi"
```

### 3. Filter
It is possible to add filters to the search engine.
These filters act to the input string of the search function before any database interaction.

#### 3.1. Using Filters
You can add a filter using `WSE::addFilter($filter)` method.
In the version 1.0.0, WSE only provides you one filter: **the whitespace filter**
You'll find this filter as `$engine->filter_whitespaces`.

#### 3.2. Adding Custom Filters
You can add a custom filter sending to `WSE::addFilter($filter)` a function that take as first argument the input string and as second argument the separator that will be used for cutting the filtered query string into words.
It has to return the filtered string.

*Exemple: here, we are adding a filter that replace all "a" chars by the separator*
```php
$engine->addFilter(function($input, $separator){
	return str_replace('a', $separator, $input);
});
```

### Search
Now your engine is set, you can search whatever you want.
To search, use the method `WSE::search($str, $separator)`, with `$str` the query string and `$separator` the chars that are gonna be used for cutting the query into words.
This method can be called several times, it won't change the rules or the filters.

*Notes:*
* `WSE::search($str, $separator)` return a multidimensional array ordered by pertinence which contains table lines arrays; The name of the table it comes from is stored at the index `_wse_table_`.
* You can access the execution time using the method `double WSE::getLastExecutionTime()` (time in seconds)
