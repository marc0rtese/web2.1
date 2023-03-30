<?php
require_once "TwigBaseController.php";

class DocumentsController extends TwigBaseController {
    public $template = "documents.twig";
    public $title = "Документы";

    public function get(array $context)
    {
        parent::get($context);
    }

    public function getContext() : array
    {
        $context = parent::getContext();
        $context['title'] = $this -> title;
        $number = isset($_GET['number']) ? $_GET['number'] : '';
        $date = isset($_GET['date']) ? $_GET['date'] : '';
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        $page = isset($_GET['page']) ? $_GET['page'] : 0;

        $direction = isset($_GET['direction']) ? $_GET['direction'] : '';
        if (($type == '') and ($direction == ''))
        {
            $sql = <<<EOL
            SELECT *
            FROM sbis
            WHERE (:number = '' OR number like CONCAT('%', :number, '%'))
            AND (:date = '' OR date like CONCAT('%', STR_TO_DATE(:date,'%Y-%m-%d'), '%'))
            ORDER BY date DESC
            EOL;
        }
        elseif($type == '')
        {
            $sql = <<<EOL
            SELECT *
            FROM sbis
            WHERE (:number = '' OR number like CONCAT('%', :number, '%'))
            AND (:date = '' OR date like CONCAT('%', STR_TO_DATE(:date,'%Y-%m-%d'), '%'))
            AND (direction = :direction)
            ORDER BY date DESC
            EOL;
        }
        elseif($direction == '')
        {
            $sql = <<<EOL
            SELECT *
            FROM sbis
            WHERE (:number = '' OR number like CONCAT('%', :number, '%'))
            AND (:date = '' OR date like CONCAT('%', STR_TO_DATE(:date,'%Y-%m-%d'), '%'))
            AND (type = :type)
            ORDER BY date DESC
            EOL;
        }
        else
        {
            $sql = <<<EOL
            SELECT *
            FROM sbis
            WHERE (:number = '' OR number like CONCAT('%', :number, '%'))
            AND (:date = '' OR date like CONCAT('%', STR_TO_DATE(:date,'%Y-%m-%d'), '%'))
            AND (type = :type)
            AND (direction = :direction)
            ORDER BY date DESC
            EOL;
        }
        $query = $this->pdo->prepare($sql);
        $query->bindValue("number", $number);
        $query->bindValue("date", $date);
        $query->bindValue("type", $type); 
        $query->bindValue("direction", $direction);
        $query->execute();
        $context['documents'] = $query->fetchAll();
        return $context;
    }
}
