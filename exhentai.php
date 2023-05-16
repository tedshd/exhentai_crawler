<?php

require 'header.php';

$input = $argv[1];

echo $input . "\n";

function htmlContentGet($url)
{
  global $HEADER;

  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => $HEADER,
  ));

  $response = curl_exec($curl);

  curl_close($curl);
  return $response;
}

function getPageInfo($url)
{
  global $title, $linkArray;
  $response_html = htmlContentGet($url);

  // echo $response_html . "\n";

  $dom = new DOMDocument();
  $dom->loadHTML($response_html, LIBXML_NOERROR);

  $documentElement = $dom->documentElement;

  $xpath = new DOMXpath($dom);
  $name = $xpath->query('//*[@id="gj"]');

  foreach ($name as $item) {
    $title = $item->textContent;
    echo $title . "\n";
  }

  if (empty($title)) {
    $name = $xpath->query('//*[@id="gn"]');

    foreach ($name as $item) {
      $title = $item->textContent;
      echo $title . "\n";
    }
  }

  $alink = $documentElement->getElementsByTagName('a');

  foreach ($alink as $item) {
    $link = $item->getAttribute('href');
    // echo $link . "\n";
    if (strpos($link, 'https://exhentai.org/s') !== false) {
      array_push($linkArray, $link);
    }
  }
}

function mkDirectory()
{
  global $title, $folder;
  $title = str_replace("/", "／", $title);
  $title = str_replace(";", "_", $title);

  $file_name = str_replace(" ", "\\ ", $title);
  $file_name = str_replace("(", "\\(", $file_name);
  $file_name = str_replace(")", "\\)", $file_name);
  $file_name = str_replace("'", "\\'", $file_name);
  $file_name = str_replace("&", "\\&", $file_name);

  exec('mkdir ' . $folder);
  exec('cd ' . $folder . ' && mkdir ' . $file_name);
}

function getImageInfo()
{
  global $linkArray, $imgSrcArray;
  $dom = new DOMDocument();
  for ($i = 0; $i < sizeof($linkArray); $i++) {
    $response_html_image_page = htmlContentGet($linkArray[$i]);

    $dom->loadHTML($response_html_image_page, LIBXML_NOERROR);
    $xpath = new DOMXpath($dom);
    $img = $xpath->query('//*[@id="img"]');

    $imgSrc = '';
    foreach ($img as $item) {
      $imgSrc = $item->getAttribute('src');
      echo $imgSrc . "\n";
    }

    $img_name = $xpath->query('//*[@id="i2"]/div[2]');

    $text = time() . '.jpg';
    foreach ($img_name as $item) {
      $text = $item->textContent;
      echo $text . "\n";
    }

    array_push($imgSrcArray, [
      'name' => trim(explode('::', $text)[0]),
      'url' => $imgSrc
    ]);

    sleep(1);
  }
}

// BEGIN

$urlArray = explode(',', $input);
$title = "==NOT FOUND==";
$linkArray = [];
$imgSrcArray = [];
$folder = 'EXHEANTI';

for ($n = 0; $n < sizeof($urlArray); $n++) {
  if (empty($urlArray[$n])) {
    continue;
  }
  $linkArray = [];
  $imgSrcArray = [];

  echo $urlArray[$n] . "\n";
  getPageInfo($urlArray[$n]);
  mkDirectory();
  var_dump($linkArray);
  getImageInfo();
  var_dump($imgSrcArray);
  for ($z = 0; $z < sizeof($imgSrcArray); $z++) {
    var_dump($imgSrcArray[$z]);
    file_put_contents(__DIR__ . '/'. $folder . '/' . $title . '/' . $imgSrcArray[$z]['name'], file_get_contents($imgSrcArray[$z]['url']));
  }
}
