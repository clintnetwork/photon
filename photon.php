<?php
/**
 * Photon PHP MVC Framework 1.0
 * Photon is a Lightweight PHP Framework 💡
 * 
 * @author Clint.Network
 * @link https://github.com/clintnetwork/photon
 * @version 1.0
 */
class Photon
{
    private $routes = [];
    private $application_root;
    private $base_route;

    /**
     * You need to have a '_layout.php' in the views folder when $use_layout_view is enabled
     */
    public $use_layout_view = false;
    
    /**
     * If the development mode is enabled, you will visual helps
     */
    public $development_mode = false;

    /**
     * To store anything
     */
    public $baggy;

    /**
     * To store view data
     */
    public static $viewbag;

    /**
     * To store view data
     */
    public static $model;

    public function __construct()
    {
        $this->base_route = dirname($_SERVER["SCRIPT_NAME"]);
        $this->application_root = dirname($_SERVER["SCRIPT_FILENAME"]);
    }

    /**
     * Initialize the framework
     */
    public function ignite()
    {
        $controller_files = $this->load_folder($this->application_root . "/controllers");
        $model_files = $this->load_folder($this->application_root . "/models");

        foreach($controller_files as $controller)
        {
            Photon::inject_file($this->application_root . "/controllers/$controller");
            $controller_name = pathinfo(strtolower($controller), PATHINFO_FILENAME);
            $controller_class = pathinfo(ucfirst($controller), PATHINFO_FILENAME);
            foreach(get_class_methods($controller_class) as $action_name)
            {
                $this->routes[$this->base_route . "/$controller_name/$action_name"] = array("Controller" => $controller_class, "Action" => $action_name);

                if($action_name == "index")
                {
                    $this->routes[$this->base_route . "/$controller_name"] = array("Controller" => $controller_class, "Action" => $action_name);
                }

                $rc = new ReflectionMethod($controller_class, $action_name);
                if (preg_match_all('/@(\w+)\s+(.*)\r?\n/m', $rc->getDocComment(), $matches))
                {
                    $result = array_combine($matches[1], $matches[2]);
                    if(isset($result["route"]))
                    {
                        $this->routes[$this->base_route . trim($result["route"])] = array("Controller" => $controller_class, "Action" => $action_name);
                        unset($this->routes[$this->base_route . "/$controller_name/$action_name"]);
                    }

                    if(isset($result["layout"]))
                    {
                        $this->routes[$this->base_route . trim($result["route"])]["Layout"] = trim($result["layout"]);
                    }
                }
            }
        }

        foreach($model_files as $model)
        {
            $this->inject_file($this->application_root . "/models/$model");
        }

        if($this->development_mode)
        {
            PhotonInject::css();
            PhotonInject::debug();
        }
        
        $has_output = false;
        foreach(array_keys($this->routes) as $route)
        {
            if($route == $_SERVER["REQUEST_URI"])
            {
                $controller_class = $this->routes[$route]["Controller"];
                $action_name = $this->routes[$route]["Action"];
                $layout_view = isset($this->routes[$route]["Layout"]) ? $this->routes[$route]["Layout"] : "_layout";

                if($this->use_layout_view && $layout_view != "null")
                {
                    // Inject the layout view
                    $this->baggy = array("Controller" => $controller_class, "Action" => $action_name);
                    Photon::inject_file($this->application_root . "/views/$layout_view.php");
                }
                else
                {
                    // Execute actions from the controller
                    $tmp_class = new $controller_class();
                    $tmp_class->$action_name();
                }

                $has_output = true;
            }
        }

        if(!$has_output)
        {
            if($this->development_mode)
            {
                PhotonInject::error(404);
            }
            else
            {
                header('HTTP/1.1 404 Not Found');
            }
        }
    }

    public static function render_body()
    {
        $baggy = Photon::get_caller(4)["object"]->baggy;

        $controller_class = $baggy["Controller"];
        $action_name = $baggy["Action"];

        // Execute actions from the controller
        $tmp_class = new $controller_class();
        $tmp_class->$action_name();
    }

    public static function inject_file($file_location)
    {
        include $file_location;
    }

    public static function debug($what)
    {
        echo "<pre>";
        print_r($what);
        echo "</pre>";
    }

    public static function load_folder($folder)
    {
        return array_filter(scandir($folder), function($v, $k)
        {
            return StringManipulation::endsWith($v, ".php");
        }, ARRAY_FILTER_USE_BOTH);
    }

    public static function get_caller($index = 1)
    {
        return debug_backtrace()[$index];
    }
}

class PhotonInject
{
    /**
     * Inject the CSS
     */
    public static function css()
    {
        echo <<<HTML
<style type="text/css">
.photon
{
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', Helvetica, Arial, sans-serif; position: fixed; bottom: 0; right: 0; padding: 10px; color: #333333;
    color: #333333;
}

.photon.error
{
    position: fixed;
    top: 0;
    width: 100%;
    height: 85%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

.photon.error .code
{
    font-size: 20rem;
}

.photon.error .message
{
    font-size: 23pt;
    color: #555555;
    margin: 0;
}

.photon.powered
{
    position: fixed;
    bottom: 0;
    right: 0;
    padding: 10px;
}

.photon.powered .accent
{
    color: #0e5a90;
}
</style>
HTML;
    }

    /**
     * Add a powered message
     */
    public static function debug()
    {
        echo <<<HTML
<div class="photon powered">
    Photon Framework | <span class="accent">Development Mode</span>
</div>
HTML;
    }

    /**
     * Create an error message
     */
    public static function error($code = 500)
    {
        $message = "Internal Server Error";
        if($code == 404)
        {
            $message = "This page or request does not exists.";
        }

        echo <<<HTML
<div class="photon error">
    <span class="code">$code</span>
    <p class="message">$message</p>
</div>
HTML;
    }
}

class StringManipulation
{
    public static function startsWith($haystack, $needle)
    {
         $length = strlen($needle);
         return (substr($haystack, 0, $length) === $needle);
    }
    
    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
    
        return (substr($haystack, -$length) === $needle);
    }
}

function view($view_parameters = null)
{
    $get_caller = Photon::get_caller(2);
    Photon::$model = $view_parameters;
    Photon::inject_file(dirname($_SERVER["SCRIPT_FILENAME"]) . "/views/" . strtolower($get_caller["class"]) . "/" . $get_caller["function"] . ".php");
}

function content($what)
{
    echo $what;
}
?>