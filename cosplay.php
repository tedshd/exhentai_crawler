<?php

$input = $argv[1];

echo $input . "\n";

$HEADER = [
  'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
  'Accept-Encoding: gzip, deflate, br',
  'Accept-Language: zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7,he-IL;q=0.6,he;q=0.5,tr-TR;q=0.4,tr;q=0.3,pt-BR;q=0.2,pt;q=0.1,es-AR;q=0.1,es;q=0.1,az-AZ;q=0.1,az;q=0.1,ja-JP;q=0.1,ja;q=0.1,th-TH;q=0.1,th;q=0.1',
  'Cache-Control: no-cache',
  'Connection: keep-alive',
  'Cookie: ipb_member_id=973819; ipb_pass_hash=56e195344311b69e74b28dbbe5ed6cd8; yay=louder; igneous=b60e6320d; sk=k7ddx9btdak2g057z45e5ooqbev7; yay=louder',
  'Host: exhentai.org',
  'Pragma: no-cache',
  // 'Referer: https://exhentai.org/?f_search=chin&f_cats=1021&next=2363731',
  'sec-ch-ua: "Chromium";v="106", "Google Chrome";v="106", "Not;A=Brand";v="99"',
  'sec-ch-ua-mobile: ?0',
  'sec-ch-ua-platform: "macOS"',
  'Sec-Fetch-Dest: document',
  'Sec-Fetch-Mode: navigate',
  'Sec-Fetch-Site: same-origin',
  'Sec-Fetch-User: ?1',
  'Upgrade-Insecure-Requests: 1',
  'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36'
];

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
  $title = str_replace("|", "-", $title);

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
$folder = 'COSPLAY';

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
    file_put_contents(__DIR__ . "/" . $folder . "/" . $title . "/" . $imgSrcArray[$z]['name'], file_get_contents($imgSrcArray[$z]['url']));
  }
}
