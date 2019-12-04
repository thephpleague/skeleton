<?php
define('COL_DESCRIPTION', 0);
define('COL_HELP', 1);
define('COL_DEFAULT', 2);

$fields = [
    'author_name' =>            ['Your name',             '',                                                ''],
    'author_github_username' => ['Your GitHub username',  '<username> in https://github.com/username',       ''],
    'author_email' =>           ['Your email address',    '',                                                ''],
    'author_twitter' =>         ['Your twitter username', '',                                                '@{author_github_username}'],
    'author_website' =>         ['Your website',          '',                                                'https://github.com/{author_github_username}'],

    'package_vendor' =>         ['Package vendor',        '<vendor> in https://github.com/vendor/package',   '{author_github_username}'],
    'package_name' =>           ['Package name',          '<package> in https://github.com/vendor/package',  ''],
    'package_description' =>    ['Package very short description',   '',                                     ''],

    'psr4_namespace' =>         ['PSR-4 namespace',       'usually, Vendor\\Package',                        '{package_vendor}\\{package_name}'],
];

$values = [];

$replacements = [
    ':vendor\\\\:package_name\\\\' => function () use(&$values) { return str_replace('\\', '\\\\', $values['psr4_namespace']) . '\\\\'; },
    ':author_name'                 => function () use(&$values) { return $values['author_name']; },
    ':author_username'             => function () use(&$values) { return $values['author_github_username']; },
    ':author_website'              => function () use(&$values) { return $values['author_website'] ?: ('https://github.com/' . $values['author_github_username']); },
    ':author_email'                => function () use(&$values) { return $values['author_email'] ?: ($values['author_github_username'] . '@example.com'); },
    ':vendor'                      => function () use(&$values) { return $values['package_vendor']; },
    ':package_name'                => function () use(&$values) { return $values['package_name']; },
    ':package_description'         => function () use(&$values) { return $values['package_description']; },
    'League\\Skeleton'             => function () use(&$values) { return $values['psr4_namespace']; },
];

function read_from_console ($prompt) {
    if ( function_exists('readline') ) {
        $line = trim(readline($prompt));
        if (!empty($line)) {
            readline_add_history($line);
        }
    } else {
        echo $prompt;
        $line = trim(fgets(STDIN));
    }
    return $line;
}

function interpolate($text, $values)
{
    if (!preg_match_all('/\{(\w+)\}/', $text, $m)) {
        return $text;
    }
    foreach ($m[0] as $k => $str) {
        $f = $m[1][$k];
        $text = str_replace($str, $values[$f], $text);
    }
    return $text;
}

$modify = 'n';
do {
    if ($modify == 'q') {
        exit;
    }

    $values = [];

    echo "----------------------------------------------------------------------\n";
    echo "Please, provide the following information:\n";
    echo "----------------------------------------------------------------------\n";
    foreach ($fields as $f => $field) {
        $default = isset($field[COL_DEFAULT]) ? interpolate($field[COL_DEFAULT], $values): '';
        $prompt = sprintf(
            '%s%s%s: ',
            $field[COL_DESCRIPTION],
            $field[COL_HELP] ? ' (' . $field[COL_HELP] . ')': '',
            $field[COL_DEFAULT] !== '' ? ' [' . $default . ']': ''
        );
        $values[$f] = read_from_console($prompt);
        if (empty($values[$f])) {
            $values[$f] = $default;
        }
    }
    echo "\n";

    echo "----------------------------------------------------------------------\n";
    echo "Please, check that everything is correct:\n";
    echo "----------------------------------------------------------------------\n";
    foreach ($fields as $f => $field) {
        echo $field[COL_DESCRIPTION] . ": $values[$f]\n";
    }
    echo "\n";
} while (($modify = strtolower(read_from_console('Modify files with these values? [y/N/q] '))) != 'y');
echo "\n";

$files = array_merge(
    glob(__DIR__ . '/*.md'),
    glob(__DIR__ . '/*.xml.dist'),
    glob(__DIR__ . '/composer.json'),
    glob(__DIR__ . '/src/*.php'),
    glob(__DIR__ . '/tests/*.php')
);
foreach ($files as $f) {
    $contents = file_get_contents($f);
    foreach ($replacements as $str => $func) {
        $contents = str_replace($str, $func(), $contents);
    }
    file_put_contents($f, $contents);
}

echo "Done.\n";
echo "Now you should remove the file '" . basename(__FILE__) . "'.\n";
