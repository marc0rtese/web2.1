<?php
require_once "EDIBaseController.php";

class Logic extends TwigBaseController{
    public $auth_key = "";
    public $last_update = "";
    public $docAdded = 0;
    public $docDownloaded = 0;

    public function __construct()
    {
        
    }

    public function auth() {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'СБИС.Аутентифицировать',
            'params' => [
                'Параметр' => [
                    'Логин' => $_ENV["LOGIN"],
                    'Пароль' => $_ENV["PASS"]
                ]
            ]
                ];
        $data_string = json_encode ($data, JSON_UNESCAPED_UNICODE);
        $curl = curl_init('https://online.sbis.ru/auth/service/');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json-rpc;charset=utf-8',
        'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($curl);
        curl_close($curl);
        $mass = json_decode($result, true);
        $this -> auth_key = $mass["result"];
        return $mass["result"];
    }

    public function logoff(){
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'СБИС.Выход',
            'params' => [],
            'id' => '0'
                ];
        $data_string = json_encode ($data, JSON_UNESCAPED_UNICODE);
        $curl = curl_init('https://online.sbis.ru/auth/service/');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json-rpc;charset=utf-8',
        'Content-Length: ' . strlen($data_string),
        'X-SBISSessionID:  '. $this -> auth_key)
        );
        $result = curl_exec($curl);
        curl_close($curl);
        return($result);
    }

    public function listdoc()
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'СБИС.СписокДокументов',
            'params' => [
                'Фильтр' => [
                    'ДатаС' => '15.03.2023',
                    'Тип' => 'ФактураИсх'
                ]
            ]
                ];
        $data_string = json_encode ($data, JSON_UNESCAPED_UNICODE);
        $curl = curl_init('https://online.sbis.ru/service/?srv=1');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json-rpc;charset=utf-8',
        'Content-Length: ' . strlen($data_string),
        'X-SBISSessionID: ' . $this -> auth_key)
        );
        $result = curl_exec($curl);
        curl_close($curl);
        $mass = json_decode($result, true);
        return $mass;
    }

    public function sort() 
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'СБИС.СписокИзменений',
            'params' => [
                'Фильтр' => [
                    'ДатаВремяС' => '27.03.2023 00.00.00'
                    ]
                ]
            ];
        $data_string = json_encode ($data, JSON_UNESCAPED_UNICODE);
        $curl = curl_init('https://online.sbis.ru/service/?srv=1');
        $direction = "";
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json-rpc;charset=utf-8',
        'Content-Length: ' . strlen($data_string),
        'X-SBISSessionID: ' . $this -> auth_key)
        );
        $result = curl_exec($curl);
        curl_close($curl);
        $mass = json_decode($result, true);
        //print_r($mass);
        //print_r($mass["result"]);
        $docs = $mass['result']['Документ'];
        //print_r($mass);
        foreach ($docs as &$doc)
        {
            //return $doc;
            if ($doc['Состояние']['Код'] == '7')
            {
                if ($doc['Направление'] == 'Исходящий')
                    $direction = 'fromMe';
                elseif ($doc['Направление'] == 'Входящий')
                    $direction = 'toMe';
                $this -> getpdf2($doc['СсылкаНаPDF'], $doc['Идентификатор'], $direction);
                if (($this->existInBase($doc['Идентификатор'])) == 0)
                {
                    //echo("Сюда пришел");
                    $this -> docAdded += 1;
                    $this->insertSql($doc['Номер'], $doc['Дата'], $doc['Тип'], $doc['Идентификатор'], $doc['Направление']);
                }
                //print_r($this -> existInBase($doc['Идентификатор']));
            }
            break;
            
        }
        if ($mass['result']['Навигация']['ЕстьЕще'] == 'Да')
        {
            $this -> getChangesById($doc['Событие']['0']['Идентификатор']);
        }
        //echo '<pre>';
        //print_r($mass);
        return $res = [
            'Добавлено' => $this -> docAdded,
            'Скачано' => $this ->docDownloaded
        ];
        
        //return $this -> docAdded;
        //echo $result;
    }
//сделать генерацию имени

    public function getChangesById($id)
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'СБИС.СписокИзменений',
            'params' => [
                'Фильтр' => [
                    'ДатаВремяС' => '27.03.2023 00.00.00',
                    'ИдентификаторСобытия' => $id
                    ]
                ]
            ];
        $data_string = json_encode ($data, JSON_UNESCAPED_UNICODE);
        $curl = curl_init('https://online.sbis.ru/service/?srv=1');
        $direction = "";
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json-rpc;charset=utf-8',
        'Content-Length: ' . strlen($data_string),
        'X-SBISSessionID: ' . $this -> auth_key)
        );
        $result = curl_exec($curl);
        curl_close($curl);
        $mass = json_decode($result, true);
        //print_r($mass);
        $docs = $mass['result']['Документ'];
        foreach ($docs as &$doc)
        {
            //return $doc;
            if ($doc['Состояние']['Код'] == '7')
            {
                if ($doc['Направление'] == 'Исходящий')
                    $direction = 'fromMe';
                elseif ($doc['Направление'] == 'Входящий')
                    $direction = 'toMe';
                $this -> getpdf2($doc['СсылкаНаPDF'], $doc['Идентификатор'], $direction);
                if (($this->existInBase($doc['Идентификатор'])) == 0)
                {
                    $this -> docAdded += 1;
                    //echo("Сюда пришел");
                    $this->insertSql($doc['Номер'], $doc['Дата'], $doc['Тип'], $doc['Идентификатор'], $doc['Направление']);
                }
                //print_r($this -> existInBase($doc['Идентификатор']));
            }
            //break;
            
        }
        if ($mass['result']['Навигация']['ЕстьЕще'] == 'Да')
            if ($this -> docAdded < 10)
                $this -> getChangesById($doc['Событие']['0']['Идентификатор']);
    }

    public function getpdf2($uri, $path, $direction) { //работает
    $pathAvailable = false;
    $endless = 0;
    $fileUrl = $uri;
    $saveTo = "";

    if ($direction == 'toMe')
        $saveTo = "../public/base/Входящий/" . $path . ".pdf";
    elseif ($direction == 'fromMe')
        $saveTo = "../public/base/Исходящий/" . $path . ".pdf";

    //проверка существования файла
    while ($pathAvailable == false)
    {
        if (file_exists($saveTo)) {
            //$saveTo = "../base/" . uniqid() . ".pdf";
            return;
        }
        else {
            $pathAvailable = true;
        }
        $endless += 1;
        if ($endless > 99)
            break;
    }

    $fp = fopen($saveTo, 'w+');
    if($fp === false){
        throw new Exception('Could not open: ' . $saveTo);
    }

    $ch = curl_init($fileUrl);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-SBISSessionID: ' . $this -> auth_key)
        );

    curl_exec($ch);

    if(curl_errno($ch)){
        throw new Exception(curl_error($ch));
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $endless = 0;
    while($statusCode != 200){
        sleep(3);
        curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $endless +=1;
        if ($endless > 10)
            break;
    }
    curl_close($ch);
    fclose($fp);

    if($statusCode == 200){
        //echo 'Downloaded!';
        $this -> docDownloaded += 1;
    } else{
        echo "Status Code: " . $statusCode;
    }
    }

    public function insertSql($number, $date, $type, $path, $direction)
    {
        $pdo = new PDO("mysql:host=localhost;dbname=sbis;charset=utf8", "root", "");
        $sql = <<<EOL
        INSERT INTO sbis(date, doc, number, type, direction) VALUES(STR_TO_DATE(:date,'%d.%m.%Y'), :path, :number, :type, :direction)
        EOL;
        $query = $pdo->prepare($sql);
        $query -> bindValue("path", $path . ".pdf");
        $query -> bindValue("number", $number);
        $query -> bindValue("date", $date);
        $query -> bindValue("type", $type);
        $query -> bindValue("direction", $direction);
        //$query -> bindValue("path", $path);
        $query -> execute();
    }

    public function existInBase($path) {
        $pdo = new PDO("mysql:host=localhost;dbname=sbis;charset=utf8", "root", "");
        $sql = <<<EOL
        SELECT EXISTS(SELECT * FROM sbis WHERE doc = :path) as count
        EOL;
        $query = $pdo->prepare($sql);
        $query -> bindValue("path", $path . ".pdf");
        //$query -> bindValue("path", $path);
        $query -> execute();
        $result = $query -> fetchAll();
        return $result['0']['count'];
    }
}