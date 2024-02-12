<?php

error_reporting(E_ALL ^ E_DEPRECATED);

require_once __DIR__ . '/phpQuery-onefile.php';
require_once __DIR__ . '/methods.php';
require_once __DIR__ . '/db.php';

$mainUrl = 'https://tender.rusal.ru';

// получение всех тендеров по фильтру
$curl = curl_init('https://tender.rusal.ru/Tenders/Load');

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_ENCODING, '');

curl_setopt($curl, CURLOPT_POSTFIELDS, 'limit=10&offset=0&total=9761&sortAsc=false&sortColumn=EntityNumber&MultiString=&__AllowedTenderConfigCodes=&IntervalRequestReceivingBeginDate.BeginDate=&IntervalRequestReceivingBeginDate.EndDate=&IntervalRequestReceivingEndDate.BeginDate=&IntervalRequestReceivingEndDate.EndDate=&IntervalBidReceivingBeginDate.BeginDate=&IntervalBidReceivingBeginDate.EndDate=&ClassifiersFieldData.SiteSectionType=bef4c544-ba45-49b9-8e91-85d9483ff2f6&ClassifiersFieldData.ClassifiersFieldData.__SECRET_DO_NOT_USE_OR_YOU_WILL_BE_FIRED=&OrganizerData='); // данные для запроса (здесь же и данные фильтра)

$out = curl_exec($curl);

$rows = json_decode($out, true)['Rows'];

$tenders = array();

// получение данных каждого тендера
foreach ($rows as $key => $value) {
    $tenders[$key]['TenderNumber'] = trim($value['TenderNumber']);
    $tenders[$key]['CustomerName'] = trim($value['CustomerName']);

    $tenderViewUrl = $mainUrl . trim($value['TenderViewUrl']);
    $tenders[$key]['TenderViewUrl'] = $tenderViewUrl;

    $receivingBeginDate = getReceivingBeginDate($tenderViewUrl);
    $documents = getDocuments($mainUrl, $tenderViewUrl);

    $tenders[$key]['ReceivingBeginDate'] = trim($receivingBeginDate);
    $tenders[$key]['Documents'] = $documents;
}

echo '<pre>';
print_r($tenders);
echo '</pre>';

curl_close($curl);

writeTendersToDB($tenders, $conn);

sqlsrv_close($conn);