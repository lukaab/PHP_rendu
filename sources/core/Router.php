<?php

class Router
{
  private array $routes;

  public function __construct()
  {
    $this->routes = [];
  }

  public function get(string $path, string $controllerName, string $methodName): void
  {
    $this->routes[] = [
      "method" => "GET",
      "path" => $path,
      "controllerName" => $controllerName,
      "methodName" => $methodName
    ];
  }

  public function post(string $path, string $controllerName, string $methodName): void
  {
    $this->routes[] = [
      "method" => "POST",
      "path" => $path,
      "controllerName" => $controllerName,
      "methodName" => $methodName
    ];
  }

  public function start(): void
  {
      $method = $_SERVER["REQUEST_METHOD"];
      $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH); // Nettoyer l'URL

      error_log("🔍 DEBUG - URL reçue : " . $_SERVER["REQUEST_URI"]);
  
      foreach ($this->routes as $route) {
          // Transformer `/group/{id}` en `/group/([0-9]+)`
          $routePattern = preg_replace('/\{id\}/', '([0-9]+)', $route["path"]);
          $routePattern = "#^" . $routePattern . "$#";
          error_log("🔍 DEBUG - Route en cours de comparaison : " . $routePattern);
          error_log("🔍 DEBUG - Chemin actuel : " . $path);
  
          if ($method === $route["method"] && preg_match($routePattern, $path, $matches)) {
              $methodName = $route["methodName"];
              $controllerName = $route["controllerName"];
  
              // Si une valeur a été trouvée (comme un ID), on la passe au contrôleur
              array_shift($matches);
              $controllerName::$methodName(...$matches);
              return;
          }
      }
  
      echo "Aucune route trouvée pour : " . $path;
      http_response_code(404);
  }
}
