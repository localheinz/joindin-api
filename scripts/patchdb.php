<?php
/**
 *
 * Sets up the joindin database and runs the
 * patches for poor people running Windows who
 * can't persuade patchdb.sh to work without
 * monkeying around getting grep to work.
 *
 *
 * Usage:
 * php -f patchdb.php -t "c:\wamp\path\to\joindin-api\db\  -d joindindatabasename -u mysqluser -p mysqlpassword -i initialise and seed db
 *
 */

$options = getopt("t:d:u:p:i::");




// Some sanity checks:
if (!array_key_exists('u', $options) || $options['u'] == "") {
    echo "Please provide a mysql user name";
    exit;
}
if (!array_key_exists('p', $options)) {
    $options['p'] = "";
}
if (!array_key_exists('t', $options) || $options['t'] == "") {
    echo "Please provide the directory that contains joind.in db updates";
    exit;
}
if (!array_key_exists('d', $options)) {
    $options['d'] = 'joindin';
}

if (!is_dir($options['t'])) {
    echo "The patch directory doesn't appear to exist";
    exit;
}



///////////////////////////////////////////////
// Test connecting to mysql
///////////////////////////////////////////////
$hasMysql = @exec('mysql --version');
if (!$hasMysql) {
    echo "Could not find mysql executable, sorry.";
    exit;
}


$baseMysqlCmd = "mysql -u{$options['u']} " 
    . ($options['p'] ? "-p{$options['p']} " : "")
    . "{$options['d']}";



//////////////////////////////////////////////
// Initialise db
//////////////////////////////////////////////
if (array_key_exists('i', $options)) {

    if (!file_exists($options['t'] . '/init_db.sql')) {
        echo "Couldn't find the init_db.sql file to initialise db";
        exit;
    }
    

    echo "Initialising DB";
    exec($baseMysqlCmd . " < " . $options['t'] . '/init_db.sql');
    echo " ... done\n";
}


/////////////////////////////////////////////
// Do some patching
/////////////////////////////////////////////

// First, look through the directory for patch123.sql files
// and get all the {123} numbers, so we can run them all 
// in order.
if ($dh = opendir($options['t'])) {
    while (($file = readdir($dh)) !== false) {
        
        preg_match("/patch([\d]+)\.sql/", $file, $matches);
        if ($matches && array_key_exists(1, $matches)) {
            $matchedNums[] = (int)$matches[1];
        }

    }
    closedir($dh);
}


// Now we've got them, run the patches
sort($matchedNums);

echo "Applying patches... ";

foreach ($matchedNums as $patchNum) {
    echo $patchNum . ", ";
    exec($baseMysqlCmd . " < " . $options['t'] . '/patch' . $patchNum . '.sql');
}

echo "\nAll done\n";







