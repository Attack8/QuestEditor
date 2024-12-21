<?php

if (!array_key_exists('token', $_POST)) {
  die('No token present');
}

$token = $_POST['token'];

function removeDir(string $dir): void {
  $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
  $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
  foreach($files as $file) {
      if ($file->isDir()){
          rmdir($file->getPathname());
      } else {
          unlink($file->getPathname());
      }
  }
  rmdir($dir);
}

function copyDirectory($source, $destination) {
  if (!is_dir($destination)) {
     mkdir($destination, 0755, true);
  }
  $files = scandir($source);
  foreach ($files as $file) {
     if ($file !== '.' && $file !== '..') {
        $sourceFile = $source . '/' . $file;
        $destinationFile = $destination . '/' . $file;
        if (is_dir($sourceFile)) {
           copyDirectory($sourceFile, $destinationFile);
        } else {
           copy($sourceFile, $destinationFile);
        }
     }
  }
}


$url = "https://api.github.com/repos/Attack8/eightclasses/releases";
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
  "Authorization: Bearer " . $token,
  "X-GitHub-Api-Version: 2022-11-28",
  "Accept: application/vnd.github+json",
  "User-Agent: Attack8"
));

$res = curl_exec($ch);
curl_close($ch);

$resj = json_decode($res);
$resu = json_decode(json_encode($resj[0]), true);
$url2 = $resu["zipball_url"];
$version = explode('/', $url2);
$version = $version[array_key_last($version)];
$ch2 = curl_init();

curl_setopt($ch2, CURLOPT_URL, $url2);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch2, CURLOPT_HEADER, 1);
curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
  "Authorization: Bearer " . $token,
  "X-GitHub-Api-Version: 2022-11-28",
  "User-Agent: Attack8"
));

if(curl_error($ch2)) {
  echo(curl_error($ch2));
}

$res2 = curl_exec($ch2);
curl_close($ch2);

$url3 = explode("location: ", $res2);

$url3 = explode("x-github", $url3[1]);
$url3 = $url3[0];
$url3 = substr_replace($url3, "", -1);
$url3 = substr_replace($url3, "", -1);
$ch3 = curl_init();

curl_setopt($ch3, CURLOPT_URL, $url3);
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch3, CURLOPT_HEADER, FALSE);
curl_setopt($ch3, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($ch3, CURLOPT_HTTPHEADER, array(
  "Authorization: Bearer $token",
  "X-GitHub-Api-Version: 2022-11-28",
  "User-Agent: Attack8"
));

if(curl_error($ch3)) {
  echo(curl_error($ch3));
}

$res3 = curl_exec($ch3);
curl_close($ch3);

$path = 'response.zip';
if (!is_dir('response')) {
  mkdir('response');
}

file_put_contents($path, $res3);

$zip = new ZipArchive;
if ($zip->open($path) === true) {
    $zip->extractTo('response');
    $zip->close();
}

$from_dir = "response/" . scandir('response')[2] . "/" . $version;
$dest_dir = $version . "/";

if (is_dir($dest_dir)) {
  removeDir($dest_dir);
}

sleep(20);

copyDirectory($from_dir, $dest_dir);

sleep(20);

removeDir('response');
unlink('response.zip');

$new_index = "<!DOCTYPE html><?php header('Location:". $version . "/');";

$index = fopen("index.php", "w");
fwrite($index, $new_index);
fclose($index);

echo("done");