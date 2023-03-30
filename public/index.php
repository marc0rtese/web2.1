<?php 
require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

require_once "../controllers/MainController.php";
require_once "../controllers/sbis.php";
require_once "../framework/Router.php";
require_once "../controllers/Controller404.php";
require_once "../controllers/DocumentsController.php";


$loader = new \Twig\Loader\FilesystemLoader('../views');
$twig = new \Twig\Environment($loader);
$twig->addExtension(new \Twig\Extension\DebugExtension());

$pdo = new PDO("mysql:host=localhost;dbname=sbis;charset=utf8", "root", "");

$router = new Router($twig, $pdo);
$router->add("/", MainController::class);
$router->add("/sbis", sbis::class);
$router->add("/documents", DocumentsController::class);
$router->add("/base/Исходящий/df49bc88-a5c5-49c0-8f6d-00180b129349.pdf?", DocumentsController::class);
$router->get_or_default(Controller404::class);

