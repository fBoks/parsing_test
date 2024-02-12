<?php

function getReceivingBeginDate(string $url): string
{
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_ENCODING, '');

    curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest',]);

    $tenderPage = curl_exec($curl);

    $pq = phpQuery::newDocument($tenderPage);

    $receivingBeginDate = $pq->find('div[data-field-name="Fields.RequestReceivingBeginDate"]')->text();

    curl_close($curl);

    return strval($receivingBeginDate);
}

function getDocuments(string $mainUrl, string $tenderUrl): array
{
    $documents = array();

    $curl = curl_init($tenderUrl);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_ENCODING, '');

    $tenderPage = curl_exec($curl);

    $pq = phpQuery::newDocument($tenderPage);

    foreach ($pq->find('.doc-group-content') as $key => $value) {
        $elem = pq($value)->find('.file-download-link');

        $fileLink = strval($elem->attr('href'));
        $fileName = strval($elem->text());

        $documents[$key]['Name'] = trim($fileName);
        $documents[$key]['Link'] = trim($mainUrl . $fileLink);
    }

    curl_close($curl);

    return $documents;
}

function writeTendersToDB(array $tenders, $conn)
{
    foreach ($tenders as $tender) {
        $tendersSql = "
            INSERT INTO tenders (tender_number, customer_name, tender_view_url) 
            VALUES (
                '{$tender['TenderNumber']}',
                '{$tender['CustomerName']}',
                '{$tender['TenderViewUrl']}'
            );
            SELECT SCOPE_IDENTITY()
        ";

        $insertedTenderResult = executeQuery($conn, $tendersSql);

        sqlsrv_next_result($insertedTenderResult); 
        sqlsrv_fetch($insertedTenderResult);
        $currentTenderId = sqlsrv_get_field($insertedTenderResult, 0);

        if (count($tender['Documents']) > 0) {
            foreach ($tender['Documents'] as $document) {
                $documentsSql = "INSERT INTO documents (name, link, id_tender) 
                    VALUES (
                        CAST('{$document['Name']}' as text),
                        CAST('{$document['Link']}' as text),
                        CAST('{$currentTenderId}' as int)
                    );
                ";

                executeQuery($conn, $documentsSql);

                // $currentDocumentIdSql = "SELECT id FROM documents WHERE link = CAST('{$document['Link']}' as text)";
                // $currentDocumentId = executeQuery($conn, $currentDocumentIdSql);

                // $tendersDocumentsSql = "INSERT INTO tenders_documents (id_tender, id_document) 
                //     VALUES (
                //         CAST('{$currentTenderId}' as int),
                //         CAST('{$currentDocumentId}' as int)
                //     );
                // ";

                // executeQuery($conn, $tendersDocumentsSql);
            }
        }
    }
}

function executeQuery($conn, string $sql)
{
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        echo "Ошибка при записи в базу данных\n";
        if (($errors = sqlsrv_errors()) != null) {
            foreach ($errors as $error) {
                echo "SQLSTATE: " . $error['SQLSTATE'] . "<br />";
                echo "Код: " . $error['code'] . "<br />";
                echo "Сообщение: " . $error['message'] . "<br />";
            }
        }
    } else {
        return $stmt;
    }
}
