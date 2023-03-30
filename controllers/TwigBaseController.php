<?php
require_once "BaseController.php";

class TwigBaseController extends BaseController {
    public $title = "";
    public $template = "";
    protected \Twig\Environment $twig;
    
    public function setTwig($twig) {
        $this->twig = $twig;
    }
    
    public function getContext() : array
    {
        $context = parent::getContext();
        $context['title'] = $this->title;
        $pdo = new PDO("mysql:host=localhost;dbname=sbis;charset=utf8", "root", "");
        $this->setPDO($pdo);
        $query = $pdo->query("SELECT * FROM sbis ORDER BY date DESC");
        $context['documents'] = $query->fetchAll();
        $query = $pdo->query("SELECT DISTINCT type FROM sbis ORDER BY 1");
        $context['types'] = $query->fetchAll();
        $query = $pdo->query("SELECT DISTINCT direction FROM sbis ORDER BY 1");
        $context['directions'] = $query->fetchAll();
        $query = $pdo->query("SELECT COUNT(*) as count FROM sbis");
        $context['countdoc'] = $query->fetchAll();
        //putenv("LASTID=Zdarova");

        return $context;
    }
    
    public function get(array $context) {
        echo $this->twig->render($this->template, $context);
    }

}