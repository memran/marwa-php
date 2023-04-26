# Configuration Library
It helps to read configuration file from config directory.

## How to read config file

        $auth = app('config')->file('auth.php')->load();
## Call Config class as static
        use Marwa\Application\Facades\Config;
        $auth = Config::load('auth.php);
