<?php

//////////////////////////////////

// Granular Bot                 //

// source :                     //

// by : ichrome.fahdi@gmail.com //

//////////////////////////////////

// telegram bot configuration

define('BOT_TOKEN', '392790976:AAHMRbtzIiWVsRjEXJ1OYqd4_fq2ra_fQbI');

define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// configuration to get json

ini_set("allow_url_fopen", 1);

function getParamToday() {

    // add value from current month
    if (strpos(date(M), "Jun") === 0) {
        $month = 5;
    } else if (strpos(date(M), "Jul") === 0) {
        $month = 34;
    } else {
        $month = 0;
    }

    // add letter for column name
    $ascii = 64 + intval(date(j) + 2) + $month;

    if ($ascii > 142) {
        $ascii -= 78;
        $paramLetter1 = 'C';
    } else if ($ascii > 116) {
        $ascii -= 52;
        $paramLetter1 = 'B';
    } else if ($ascii > 90) {
        $ascii -= 26;
        $paramLetter1 = 'A';
    } else {
        $paramLetter1 = 'A';
    }
    $paramLetter2 = chr($ascii);
    return $paramLetter1 . '' . $paramLetter2;
}

function apiRequestWebhook($method, $parameters) {
    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }
    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;
    }
    $parameters["method"] = $method;
    header("Content-Type: application/json");
    echo json_encode($parameters);
    return true;
}

function exec_curl_request($handle) {
    $response = curl_exec($handle);
    if ($response === false) {
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        error_log("Curl returned error $errno: $error\n");
        curl_close($handle);
        return false;
    }
    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
    curl_close($handle);
    if ($http_code >= 500) {
        // do not want to DDOS server if something goes wrong
        sleep(10);
        return false;
    } else if ($http_code != 200) {
        $response = json_decode($response, true);
        error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
        if ($http_code == 401) {
            throw new Exception('Invalid access token provided');
        }
        return false;
    } else {
        $response = json_decode($response, true);
        if (isset($response['description'])) {
            error_log("Request was successfull: {$response['description']}\n");
        }
        $response = $response['result'];
    }
    return $response;
}

function apiRequest($method, $parameters) {
    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }
    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;
    }
    foreach ($parameters as $key => &$val) {
        // encoding to JSON array parameters, for example reply_markup
        if (!is_numeric($val) && !is_string($val)) {
            $val = json_encode($val);
        }
    }
    $url = API_URL . $method . '?' . http_build_query($parameters);
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    return exec_curl_request($handle);
}

function apiRequestJson($method, $parameters) {
    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }
    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;
    }
    $parameters["method"] = $method;
    $handle = curl_init(API_URL);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    return exec_curl_request($handle);
}

function processMessage($message) {
    // process incoming message
    $message_id = $message['message_id'];
    $chat_id = $message['chat']['id'];
    // set current time based on timezone
    date_default_timezone_set("Asia/Jakarta");
    $date = date("j M o");
    if (isset($message['text'])) {
        // incoming text message
        $text = $message['text'];
        $help = "Berikut ini adalah beberapa perintah yang tersedia di Granular Bot:\n/halo : Sapa bot granular!.\n/mapping : Menampilkan progres mapping STO saat ini.\n/provision : Menampilkan status provisioning hingga saat ini.\n/dailyprovision : Menampilkan status provisioning hari ini.\n/progrespt2 : Menampilkan progres PT 2.\n/progres : Menampilkan semua progres.\n/help : Menampilkan perintah yang tersedia.";

        if (strpos($text, "/start") === 0) {
            apiRequestJson("sendMessage", array('chat_id' => $chat_id,
                "text" => "Semangat Pagi, Pagi, Pagi !\nPilih /help untuk list perintah.",
                'reply_markup' => array(
                    'resize_keyboard' => true)));

        } else if (strpos($text, "/help") === 0) {
            apiRequestJson("sendMessage", array('chat_id' => $chat_id,
                "text" => $help,
                'reply_markup' => array(
                    'resize_keyboard' => true)));

        } else if (strpos($text, "/halo") === 0) {
            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Halo juga!'));

        } else if (strpos($text, "/mapping") === 0) {
            //get Json
            $json = file_get_contents('https://sheets.googleapis.com/v4/spreadsheets/1e7yDS4yR1XsOqpPl64aGKZrVKJhp0zWTxjFMT6uNE1Y/values:batchGet?ranges=DAILY%20REPORT%20ALL%20PT!C17:C30&ranges=DAILY%20REPORT%20ALL%20PT!D17:D30&ranges=DAILY%20REPORT%20ALL%20PT!E17:E30&key=AIzaSyCzIEN8xpbDYsYE5PNF4qOlyYfVw68uJ0I');
            $obj = json_decode($json);

            //get data each city and total
            $bloraData = $obj->valueRanges[0]->values;
            $demakData = $obj->valueRanges[1]->values;
            //$allData = $obj->valueRanges[2]->values;
            $length = count($bloraData);
            for ($i = 0; $i < $length; $i++) {
                //formatting json
                $blora[$i] = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($bloraData[$i]), ENT_NOQUOTES));
                $demak[$i] = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($demakData[$i]), ENT_NOQUOTES));
                //$all[$i] = str_replace(array('[', ']', ' ','"'), '', htmlspecialchars(json_encode($allData[$i]), ENT_NOQUOTES));
            }
            $blora[13] = number_format((($blora[13] - $blora[12]) / $blora[13]) * 100, 0) . '%';
            $demak[13] = number_format((($demak[13] - $demak[12]) / $demak[13]) * 100, 0) . '%';
            $result =
                <<<MARKER
Berikut adalah status mapping hingga hari ini ( $date ) :

BLORA
Jumlah PT1 : $blora[0] pelanggan
Jumlah PT2 : $blora[3] pelanggan
Jumlah PT2 (2 core) : $blora[6] pelanggan
Jumlah PT3 : $blora[9] pelanggan
Unmapping : $blora[12] pelanggan
Progress Mapping : $blora[13]

DEMAK
Jumlah PT1 : $demak[0] pelanggan
Jumlah PT2 : $demak[3] pelanggan
Jumlah PT2 (2 core) : $demak[6] pelanggan
Jumlah PT3 : $demak[9] pelanggan
Unmapping : $demak[12] pelanggan
Progress Mapping : $demak[13]
MARKER;
            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $result));
        } else if (strpos($text, "/progrespt2") === 0) {

            //get Json
            $json = file_get_contents('https://sheets.googleapis.com/v4/spreadsheets/1e7yDS4yR1XsOqpPl64aGKZrVKJhp0zWTxjFMT6uNE1Y/values:batchGet?ranges=DAILY%20REPORT%20ALL%20PT!C68:C71&ranges=DAILY%20REPORT%20ALL%20PT!D68:D71&ranges=Pivot%20blora!D2&ranges=Pivot%20demak!D2&key=AIzaSyCzIEN8xpbDYsYE5PNF4qOlyYfVw68uJ0I');
            $obj = json_decode($json);

            $bloraPT2 = $obj->valueRanges[0]->values;
            $demakPT2 = $obj->valueRanges[1]->values;
            $bloraODPLive = $obj->valueRanges[2]->values;
            $demakODPLive = $obj->valueRanges[3]->values;
            $length = count($bloraPT2);
            for ($i = 0; $i < $length; $i++) {
                //formatting json
                $bloraPT2[$i] = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($bloraPT2[$i]), ENT_NOQUOTES));
                $demakPT2[$i] = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($demakPT2[$i]), ENT_NOQUOTES));
                //$all[$i] = str_replace(array('[', ']', ' ','"'), '', htmlspecialchars(json_encode($allData[$i]), ENT_NOQUOTES));
            }
            $bloraODPLive = formattingJSON($bloraODPLive);
            $demakODPLive = formattingJSON($demakODPLive);
            $result =
                <<<MARKER
Berikut adalah status PT2 hingga hari ini ( $date ) :

BLORA
Inprogress : $bloraPT2[0] pelanggan
Go Live : $bloraODPLive ODP
Unprogress : $bloraPT2[2] pelanggan
Inventory : $bloraPT2[3] pelanggan

DEMAK
Inprogress : $demakPT2[0] pelanggan
Go Live : $demakODPLive ODP
Unprogress : $demakPT2[2] pelanggan
Inventory : $demakPT2[3] pelanggan

MARKER;

            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $result));

        } else if (strpos($text, "/progres") === 0) {

            $paramToday = getParamToday();

            //get column from date for PS pogress
            $datenow = intval(date(j));
            if ($datenow <= 30 && $datenow >= 26) {
                $datenow -= 9;
            } else if ($datenow <= 24 && $datenow >= 19) {
                $datenow -= 8;
            } else if ($datenow <= 12 && $datenow >= 17) {
                $datenow -= 7;
            } else {
                $datenow -= 6;
            }
            $paramToday2 = chr($datenow + 64);

            //get Json
            $json = file_get_contents('https://sheets.googleapis.com/v4/spreadsheets/1e7yDS4yR1XsOqpPl64aGKZrVKJhp0zWTxjFMT6uNE1Y/values:batchGet?ranges=DAILY%20REPORT%20ALL%20PT!C17:C30&ranges=DAILY%20REPORT%20ALL%20PT!D17:D30&ranges=DAILY%20REPORT%20ALL%20PT!E17:E30&ranges=DAILY%20REPORT%20ALL%20PT!D34&ranges=DAILY%20REPORT%20ALL%20PT!I34&ranges=DAILY%20REPORT%20ALL%20PT!F34&ranges=DAILY%20REPORT%20ALL%20PT!D38&ranges=DAILY%20REPORT%20ALL%20PT!I38&ranges=DAILY%20REPORT%20ALL%20PT!F38&ranges=DAILY%20REPORT%20ALL%20PT!C68:C73&ranges=DAILY%20REPORT%20ALL%20PT!D68:D73&ranges=DAILY%20REPORT%20ALL%20PT!H34&ranges=DAILY%20REPORT%20ALL%20PT!H38&ranges=Pivot%20blora!D2&ranges=Pivot%20demak!D2&ranges=DAILY%20REPORT%20ALL%20PT!' . $paramToday . '7&ranges=DAILY%20REPORT%20ALL%20PT!' . $paramToday . '14&ranges=DAILY%20REPORT%20ALL%20PT!' . $paramToday2 . '78&ranges=DAILY%20REPORT%20ALL%20PT!' . $paramToday2 . '84&key=AIzaSyCzIEN8xpbDYsYE5PNF4qOlyYfVw68uJ0I');

            $obj = json_decode($json);

            //get data each city and total
            $bloraData = $obj->valueRanges[0]->values;
            $demakData = $obj->valueRanges[1]->values;
            //$allData = $obj->valueRanges[2]->values;
            //get data each status
            // FO = fallout
            $bloraPI = $obj->valueRanges[3]->values;
            $bloraPS = $obj->valueRanges[4]->values;
            $bloraFO = $obj->valueRanges[5]->values;
            $demakPI = $obj->valueRanges[6]->values;
            $demakPS = $obj->valueRanges[7]->values;
            $demakFO = $obj->valueRanges[8]->values;
            $bloraPT2 = $obj->valueRanges[9]->values;
            $demakPT2 = $obj->valueRanges[10]->values;
            $bloraTotal = $obj->valueRanges[11]->values;
            $demakTotal = $obj->valueRanges[12]->values;
            $bloraODPLive = $obj->valueRanges[13]->values;
            $demakODPLive = $obj->valueRanges[14]->values;
            $bloraDailyPS = $obj->valueRanges[15]->values;
            $demakDailyPS = $obj->valueRanges[16]->values;
            $bloraDailyTarget = $obj->valueRanges[17]->values;
            $demakDailyTarget = $obj->valueRanges[18]->values;

            $length = count($bloraData);
            for ($i = 0; $i < $length; $i++) {
                //formatting json
                $blora[$i] = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($bloraData[$i]), ENT_NOQUOTES));
                $demak[$i] = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($demakData[$i]), ENT_NOQUOTES));
                //$all[$i] = str_replace(array('[', ']', ' ','"'), '', htmlspecialchars(json_encode($allData[$i]), ENT_NOQUOTES));
            }
            $length = count($bloraPT2);
            for ($i = 0; $i < $length; $i++) {
                //formatting json
                $bloraPT2[$i] = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($bloraPT2[$i]), ENT_NOQUOTES));
                $demakPT2[$i] = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($demakPT2[$i]), ENT_NOQUOTES));
                //$all[$i] = str_replace(array('[', ']', ' ','"'), '', htmlspecialchars(json_encode($allData[$i]), ENT_NOQUOTES));
            }

            //formatting json
            $bloraPI = formattingJSON($bloraPI);
            $bloraPS = formattingJSON($bloraPS);
            $bloraFO = formattingJSON($bloraFO);
            $bloraTotal = formattingJSON($bloraTotal);
            $bloraODPLive = formattingJSON($bloraODPLive);
            $demakPI = formattingJSON($demakPI);
            $demakPS = formattingJSON($demakPS);
            $demakFO = formattingJSON($demakFO);
            $demakTotal = formattingJSON($demakTotal);
            $demakODPLive = formattingJSON($demakODPLive);
            $bloraDailyPS = formattingJSON($bloraDailyPS);
            $demakDailyPS = formattingJSON($demakDailyPS);
            $bloraDailyTarget = formattingJSON($bloraDailyTarget);
            $demakDailyTarget = formattingJSON($demakDailyTarget);
            $bloraTotalMapping = $blora[0] + $blora[3] + $blora[6] + $blora[9];
            $demakTotalMapping = $demak[0] + $demak[3] + $demak[6] + $demak[9];

            $blora[13] = number_format((($blora[13] - $blora[12]) / $blora[13]) * 100, 0) . '%';
            $demak[13] = number_format((($demak[13] - $demak[12]) / $demak[13]) * 100, 0) . '%';
            $bloraTargetPercentage = number_format((($bloraDailyTarget[13] - $bloraDailyPS[12]) / $bloraDailyTarget[13]) * 100, 0) . '%';
            $demakTargetPercentage = number_format((($demakDailyTargetdemakDailyTarget[13] - $demakDailyPS[12]) / $demakDailyTarget[13]) * 100, 0) . '%';
            $result =
                <<<MARKER
Berikut adalah progres migrasi hingga hari ini ( $date ) :

BLORA
Jumlah pelanggan : 2372
Jumlah PT1 : $blora[0] pelanggan
Jumlah PT2 : $blora[3] pelanggan
Jumlah PT2 (2 core) : $blora[6] pelanggan
Jumlah PT3 : $blora[9] pelanggan
Total Mapping : $bloraTotalMapping
Unmapping : $blora[12] pelanggan
Progress Mapping : $blora[13]

Jumlah Fallout : $bloraFO pelanggan
Jumlah PI : $bloraPI pelanggan
Jumlah PS : $bloraPS pelanggan
Progres PS PT1 : $bloraPT2[4]
Total input data : $bloraTotal

Inprogress : $bloraPT2[0] pelanggan
Go Live : $bloraODPLive ODP
Unprogress : $bloraPT2[2] pelanggan
Inventory : $bloraPT2[3] pelanggan
Progres Go Live PT2 : $bloraPT2[5]

Target Mapping hari ini : $bloraDailyPS / $bloraDailyTarget
Persentase target Mapping hari ini : $bloraTargetPercentage

DEMAK
Jumlah pelanggan : 1697
Jumlah PT1 : $demak[0] pelanggan
Jumlah PT2 : $demak[3] pelanggan
Jumlah PT2 (2 core) : $demak[6] pelanggan
Jumlah PT3 : $demak[9] pelanggan
Total Mapping : $demakTotalMapping
Unmapping : $demak[12] pelanggan
Progress Mapping : $demak[13]

Jumlah Fallout : $demakFO pelanggan
Jumlah PI : $demakPI pelanggan
Jumlah PS : $demakPS pelanggan
Progres PS PT1 : $demakPT2[4]
Total input data : $demakTotal

Inprogress : $demakPT2[0] pelanggan
Go Live : $demakODPLive ODP
Unprogress : $demakPT2[2] pelanggan
Inventory : $demakPT2[3] pelanggan
Progres Go Live PT2 : $demakPT2[5]

Target Mapping hari ini : $demakDailyPS / $demakDailyTarget
Persentase target Mapping hari ini : $demakTargetPercentage
MARKER;
            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $result));

        } else if (strpos($text, "/dailyprovision") === 0) {

            $paramToday = getParamToday();

            $json = file_get_contents('https://sheets.googleapis.com/v4/spreadsheets/1e7yDS4yR1XsOqpPl64aGKZrVKJhp0zWTxjFMT6uNE1Y/values:batchGet?ranges=DAILY%20REPORT%20ALL%20PT!' . $paramToday . '58:' . $paramToday . '60&ranges=DAILY%20REPORT%20ALL%20PT!' . $paramToday . '63:' . $paramToday . '658&key=AIzaSyCzIEN8xpbDYsYE5PNF4qOlyYfVw68uJ0I');

            $obj = json_decode($json);

            $bloraData = $obj->valueRanges[0]->values;

            $demakData = $obj->valueRanges[1]->values;

            for ($i = 0; $i < count($bloraData); $i++) {

                //formatting json

                $blora[$i] = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($bloraData[$i]), ENT_NOQUOTES));

                $demak[$i] = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($demakData[$i]), ENT_NOQUOTES));

            }

            $result =

                <<<MARKER

Berikut adalah status provisioning untuk hari ini ( $date ) :



BLORA

Jumlah PI : $blora[0] pelanggan

Jumlah PS : $blora[1] pelanggan

Jumlah Fallout : $blora[2] pelanggan



DEMAK

Jumlah PI : $demak[0] pelanggan

Jumlah PS : $demak[1] pelanggan

Jumlah Fallout : $demak[2] pelanggan

MARKER;

            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $result));

        } else if (strpos($text, "/provision") === 0) {

            //get Json

            $json = file_get_contents('https://sheets.googleapis.com/v4/spreadsheets/1e7yDS4yR1XsOqpPl64aGKZrVKJhp0zWTxjFMT6uNE1Y/values:batchGet?ranges=DAILY%20REPORT%20ALL%20PT!D34&ranges=DAILY%20REPORT%20ALL%20PT!I34&ranges=DAILY%20REPORT%20ALL%20PT!F34&ranges=DAILY%20REPORT%20ALL%20PT!D38&ranges=DAILY%20REPORT%20ALL%20PT!I38&ranges=DAILY%20REPORT%20ALL%20PT!F38&key=AIzaSyCzIEN8xpbDYsYE5PNF4qOlyYfVw68uJ0I');

            $obj = json_decode($json);

            //get data each status

            // FO = fallout

            $bloraPI = $obj->valueRanges[0]->values;

            $bloraPS = $obj->valueRanges[1]->values;

            $bloraFO = $obj->valueRanges[2]->values;

            $demakPI = $obj->valueRanges[3]->values;

            $demakPS = $obj->valueRanges[4]->values;

            $demakFO = $obj->valueRanges[5]->values;

            //formatting json

            $bloraPI = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($bloraPI), ENT_NOQUOTES));

            $bloraPS = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($bloraPS), ENT_NOQUOTES));

            $bloraFO = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($bloraFO), ENT_NOQUOTES));

            $demakPI = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($demakPI), ENT_NOQUOTES));

            $demakPS = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($demakPS), ENT_NOQUOTES));

            $demakFO = str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($demakFO), ENT_NOQUOTES));

            $result =

                <<<MARKER

Berikut adalah status provisioning hingga hari ini ( $date ) :



BLORA

Jumlah Fallout : $bloraFO pelanggan

Jumlah PI : $bloraPI pelanggan

Jumlah PS : $bloraPS pelanggan



DEMAK

Jumlah Fallout : $demakFO pelanggan

Jumlah PI : $demakPI pelanggan

Jumlah PS : $demakPS pelanggan

MARKER;

            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $result));

        } else {

            apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => 'Perintah tidak aktif.'));

        }

    } else {

        apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));

    }

}

//define('WEBHOOK_URL', 'https://www.aegis.web.id/granularphp/server.php');

if (php_sapi_name() == 'cli') {

    // if run from console, set or delete webhook

    apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));

    exit;

}

$content = file_get_contents("php://input");

$update = json_decode($content, true);

if (!$update) {

    // receive wrong update, must not happen

    exit;

}

if (isset($update["message"])) {

    processMessage($update["message"]);

}

function formattingJSON($message) {

    return str_replace(array('[', ']', ' ', '"'), '', htmlspecialchars(json_encode($message), ENT_NOQUOTES));

}

?>