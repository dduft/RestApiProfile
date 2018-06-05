<?php namespace ProcessWire;

require_once dirname(__FILE__) . "/../ApiHelper.php";

class PagesController {
  const AUTH = false;

  public static function index($params) {
    $pages = wire('pages');
    $pa = $pages->find("template!=admin, has_parent!=2, include=all");
    $content = ApiHelper::pagesToJSON($pa);
    return $content;
  }

  public static function show($params) {
    $params = ApiHelper::checkAndSanitizeRequiredParameters($params, ['path|text']);
    $pathArray = explode('/', $params->path);
    $path = count($pathArray) < 3 ? '/' : $params->path;

    $pages = wire('pages');
    $page = $pages->get($path);

    $content = ApiHelper::pageToArray($page);
    return $content;
  }
}
