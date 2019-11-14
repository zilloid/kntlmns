<?php
$tokenBot    = "1026577663:AAH0QxDMqZ_AGb8AM4YJhYhqfqr7aNrIvDM";
$tokenGopay  = "2e931c19-9fa8-4c4f-bf67-142fd649b5ba";
$pinGopay    = "232323";

function sendRequest($token, $method, $postdata = null) {
    $url = "https://api.telegram.org/bot$token/$method";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.120 Safari/537.36");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if($postdata) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-type: application/x-www-form-urlencodedrn'
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    }
    $exec = curl_exec($ch);
    return json_decode($exec);
    curl_close($ch);
}

function sendMessage($chat_id, $message_id, $text) {
    global $tokenBot;
    $data = array(
        'chat_id' => $chat_id,
        'text' => $text,
        'reply_to_message_id' => $message_id
    );
    $data = http_build_query($data);
    return sendRequest($tokenBot, "sendMessage", $data);
}

function saveFile($filename, $string) {
    file_put_contents($filename, $string."\n", FILE_APPEND | LOCK_EX);
}

function curl($url, $fields = null, $headers = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($fields !== null) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    }
    if ($headers !== null) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $result   = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return array(
        $result,
        $httpcode
    );
}

$file_name = "telegram-logs-update-id.txt";
$file_open = file_get_contents($file_name);
while(true) {
    $pool = sendRequest($tokenBot, "getUpdates");
    foreach($pool->result as $result) {
        $update_id = $result->update_id;
        $file_open = file_get_contents($file_name);
        $file_expl = explode(PHP_EOL, $file_open);
        if(!in_array($update_id, $file_expl)) {
            saveFile($file_name, $update_id);
            $from = $result->message->from->username;
            $text = $result->message->text;
            $chat_id = $result->message->chat->id;
            $message_id = $result->message->message_id;
            echo "@$from => $text\n";
            
            if(preg_match('#/#', $text) > 0) {
                $cm = explode("/", $text);
                $mc = explode(" ", $cm[1]);
                if($mc[0] == "gopaysender") {
                    $headers = array();
                    $headers[] = 'Content-Type: application/json';
                    $headers[] = 'X-AppVersion: 3.27.0';
                    $headers[] = "X-Uniqueid: ac94e5d0e7f3f".rand(111,999);
                    $headers[] = 'X-Location: -6.405821,106.064193';
                    $headers[] = 'Authorization: Bearer '.$tokenGopay;
                    $nomor_hp = $mc[1];
                    $first = substr($nomor_hp, 0, 1);
                    $getqrid = curl('https://api.gojekapi.com/wallet/qr-code?phone_number=%2B'.str_replace($first, '', $nomor_hp).'', null, $headers);
                    $jsqrid = json_decode($getqrid[0]);
                    $qrid = $jsqrid->data->qr_id;
                    $headertf = array();
                    $headertf[] = 'Content-Type: application/json';
                    $headertf[] = 'X-AppVersion: 3.27.0';
                    $headertf[] = "X-Uniqueid: ac94e5d0e7f3f".rand(111,999);
                    $headertf[] = 'X-Location: -6.405821,106.64193';
                    $headertf[] ='Authorization: Bearer '.$tokenGopay;
                    $headertf[] = 'pin:'.$pinGopay.'';
                    $tf = curl('https://api.gojekapi.com/v2/fund/transfer', '{"amount":"1","description":"💰Memek ","qr_id":"'.$qrid.'"}', $headertf);
                    sendMessage($chat_id, $message_id, "Response : \n{$tf[0]}");
                } else if($mc[0] == "help") {
                    sendMessage($chat_id, $message_id, "Saya adalah bot!");
                } else if($mc[0] == "creator") {
                    sendMessage($chat_id, $message_id, "Bot ini dibuat oleh Wildan Fajriansyah");
                }
            }
        }
    }
}
