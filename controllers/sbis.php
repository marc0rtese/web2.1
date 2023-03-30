<?php
require_once "EDIBaseController.php";
require_once "Logic.php";
require_once '../vendor/autoload.php';
class sbis extends EDIBaseController {
    public $template = "sbis.twig";
    public $title = "Сбис";
    public $loading = false;

    public function get(array $context)
    {
        parent::get($context);
    }

    public function post(array $context) { 
        
        $obj = new Logic();
        $obj -> auth();
        $result = $obj -> sort();
        //echo("Добавлено " . $result['Добавлено'] . " новых документов," . " загружено " . $result['Скачано'] . " документов.");
        $obj -> logoff();
        unset($obj);
        $context['message'] = "Добавлено в базу: " . $result['Добавлено'] . " , загружено оригиналов: " . $result['Скачано'];
        $this->get($context);
    }
}













