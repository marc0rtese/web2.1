<?php
require_once "TwigBaseController.php";

class MainController extends TwigBaseController {
    public $template = "main.twig";
    public $title = "Главная";
    public function getContext() : array
    {
        $context = parent::getContext();
        $context['template'] = "main.twig";
        return $context;
    }

    public function post(array $context) {
        $title = $_POST['title'];
        $type = $_POST['type'];
        $info = $_POST['info'];

        
        $this->get($context);
    }
}

