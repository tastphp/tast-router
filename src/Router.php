<?php
namespace TastRouter;

/**
 * Class Router
 * @package TastRouter
 * @author xujiajun
 */
class Router
{
    /**
     * @var array|RouteCollection
     */
    private $routes = [];
    /**
     * @var
     */
    private $parameters;

    /**
     * @param RouteCollection $routeCollection
     */
    public function __construct(RouteCollection $routeCollection)
    {
        $this->routes = $routeCollection;
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return mixed
     */
    public function matchCurrentRequest()
    {
        $requestMethod = (
            isset($_POST['_method'])
            && ($_method = strtoupper($_POST['_method']))
            && in_array($_method, array('PUT', 'DELETE'))
        ) ? $_method : $_SERVER['REQUEST_METHOD'];

        $requestUrl = $_SERVER['REQUEST_URI'];

        if (($pos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $pos);
        }
        return $this->match($requestUrl, $requestMethod);
    }

    /**
     * @param $requestUrl
     * @param string $requestMethod
     * @return mixed
     * @throws \Exception
     */
    public function match($requestUrl, $requestMethod = 'GET')
    {
        $isRegexp = false;
        foreach ($this->routes->all() as $route) {
            $url = $route->getUrl();
            if (in_array($requestUrl, (array)$url)) {
                $route->dispatch();
                return $route;
            }

            if (!in_array($requestMethod, (array)$route->getMethods())) {
                continue;
            }

            $isRegexp = $this->_PregMatch($url, $requestUrl, $route);

            if (!in_array($requestUrl, (array)$url) && $isRegexp == false) {
                continue;
            }

            $route->dispatch();
            return $route;
        }
    }

    /**
     * @param $url
     * @param $requestUrl
     * @param $route
     * @return bool
     */
    private function _PregMatch($url, $requestUrl, $route)
    {
        $replace = [];
        $search = [];
        $requireKeyNames = [];
        $configs = $route->getConfig();
        preg_match_all('/{(\w+)}/', $url, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $requireKey) {
                $pattern = $configs[$requireKey];
                $replace[] = "($pattern)";
                $search[] = '{' . $requireKey . '}';
                $requireKeyNames[] = $requireKey;
            }

            $pattern = str_replace('/', '\/', str_replace($search, $replace, $url));
            preg_match_all("/^$pattern$/", $requestUrl, $matcheParams);
            array_shift($matcheParams);

            if (empty($matcheParams)) {
                die('404 not found!');
            }

            $parameters = [];

            $pos = 0;
            foreach ($matcheParams as $matcheParam) {
                if (empty($matcheParam)) {
                    die('parameter is wrong!');
                }
                $parameterName = $requireKeyNames[$pos];
                $parameters[$parameterName] = $matcheParam[0];
                $pos++;
            }

            $route->setParameters($parameters);
            return true;
        }
    }
}