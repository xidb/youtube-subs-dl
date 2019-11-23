<?php

set_time_limit(0);
ini_set ('max_execution_time',  0);

define('DIR', dirname(__FILE__) . '/');

require(DIR . 'vendor/autoload.php');
require(DIR . 'util.php');

use Symfony\Component\Process\Process;
use \Gbuckingham89\YouTubeRSSParser\Parser;

$lastFile = DIR . 'last.txt';

if (!file_exists($lastFile)) {
    output('Setting last check 3 days ago');
    file_put_contents($lastFile, time() - (3 * 86400));
}

$lastCheck = (int) file_get_contents($lastFile);
$lastCheckRelative = toRelativeTime($lastCheck + (20 * 60));
output("Last updated {$lastCheckRelative}");
$newCheck = time() - (20 * 60);

$subsFile = DIR . 'subscriptions.xml';

if (!file_exists($subsFile)) {
    output('subscribtions.xml missing. Download it here https://www.youtube.com/subscription_manager?action_takeout=1');
    die();
}

$maybeYdl = glob(DIR . 'youtube-dl*');
$ydl = !empty($maybeYdl) ? $maybeYdl[0] : 'youtube-dl';
$ydlArgs = ['--no-playlist', '--write-sub', '--sub-lang', 'en,en-US,en-GB,ru,ru-RU', '--embed-sub'];
$cmd = array_combine(range(1, count($ydlArgs)), array_values($ydlArgs));
$cmd[0] = $ydl;
$cmdLengthWithArgs = count($cmd);
ksort($cmd);

$subs = @simplexml_load_file($subsFile)->body->outline->outline;
$subsCount = $subs->count();

if (!$subs || $subsCount < 1) {
    output('subscribtions.xml is not in the right format');
    die();
} else {
    output("Updating {$subsCount} channels");
}

$somethingFound = false;
$atLeastOneParsed = false;
$hasError = false;

foreach ($subs as $sub) {
    $title = $sub->attributes()->title->__toString();
    $url = $sub->attributes()->xmlUrl->__toString();

    try {
        $parser = new Parser();
        $channel = $parser->loadUrl($url);
    } catch (Exception $e) {
        output("Error parsing {$title} channel");
        continue;
    }

    $atLeastOneParsed = true;

    $videos = $channel->toArray()['videos'];
    $toDl = [];

    foreach ($videos as $video) {
        $publishedAt = strtotime($video['published_at']);
        if ($publishedAt > $lastCheck) {
            $toDl[$publishedAt] = $video;
        }
    }

    $toDlCount = count($toDl);
    if ($toDlCount < 1) {
        continue;
    }

    $somethingFound = true;

    $s = $toDlCount === 1 ? '' : 's';

    output("Found {$toDlCount} new video{$s} from {$title}");

    ksort($toDl);

    foreach ($toDl as $toDlVideo) {
        // Remove last dl video id from cmd, if present
        if (count($cmd) > $cmdLengthWithArgs) {
            array_pop($cmd);
        }

        // Do not download if id passed in arguments
        if (in_array($toDlVideo['id'], $argv)) {
            output("Skipping \"{$toDlVideo['title']}\" as requested");
            continue;
        }

        if ($toDlVideo['id'][0] === '-' && $cmd[array_key_last($cmd)] !== '--') {
            array_push($cmd, '--');
        }

        array_push($cmd, $toDlVideo['id']);

        output("Downloading \"{$toDlVideo['title']}\"");
        echo PHP_EOL;

        $process = new Process($cmd);
        $process->setTimeout(3600);

        $process->run(function($type, $buffer) {
            echo $buffer;
        });

        if (!$process->isSuccessful()) {
            $hasError = true;
            break 2;
        }
    }
}

if (!$somethingFound) {
    output('Nothing found');
}

if ($atLeastOneParsed && !$hasError) {
    file_put_contents($lastFile, $newCheck);
}
