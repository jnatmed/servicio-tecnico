<?php

namespace Paw\Core;

class Config
{
    private array $configs;

    public function __construct() 
    {
        $this->configs['LOG_LEVEL'] = getenv(("LOG_LEVEL") ?? "INFO");
        $path = getenv("LOG_PATH", '/logs/app.log');
        $this->configs['LOG_PATH'] = $this->joinPaths('..', $path);

        $this->configs['DB_ADAPTER'] = getenv('DB_ADAPTER') ?? 'mysql';
        $this->configs['DB_HOSTNAME'] = getenv('DB_HOSTNAME') ?? 'localhost';
        $this->configs['DB_DBNAME']  = getenv('DB_DBNAME') ?? 'database_name';     
        $this->configs['DB_USERNAME'] = getenv('DB_USERNAME') ?? 'root';
        $this->configs['DB_PASSWORD'] = getenv('DB_PASSWORD') ?? '';   
        $this->configs['DB_PORT'] = getenv('DB_PORT') ?? '3306';
        $this->configs['DB_CHARSET'] = getenv('DB_CHARSET') ?? 'utf8';      
        $this->configs['OPENCAGEDATA_API_KEY'] = getenv('OPENCAGEDATA_API_KEY') ?? '';

        $this->configs['TEMPLATE_DIR'] =  getenv('TEMPLATE_DIR') ?? '';
        $this->configs['TEMPLATE_CACHE_DIR'] =  getenv('TEMPLATE_CACHE_DIR') ?? '';
        $this->configs['API_KEY_MAIL'] =  getenv('API_KEY_MAIL') ?? '';
        $this->configs['SECRET_KEY_MAIL'] =  getenv('SECRET_KEY_MAIL') ?? '';
        $this->configs['FROM_MAIL'] =  getenv('FROM_MAIL') ?? '';
        $this->configs['FROM_NAME'] =  getenv('FROM_NAME') ?? '';

        $this->configs['CLIENT_ID'] =  getenv('CLIENT_ID') ?? '';
        $this->configs['CLIENT_SECRET'] =  getenv('CLIENT_SECRET') ?? '';
        $this->configs['REDIRECT_URI'] =  getenv('REDIRECT_URI') ?? '';
        $this->configs['AUTH_URL'] =  getenv('AUTH_URL') ?? '';

        $this->configs['JWT_SECRET'] =  getenv('JWT_SECRET') ?? '';

        $this->configs['LDAP_HOST'] = getenv('LDAP_HOST') ?? '';
        $this->configs['LDAP_PORT'] = getenv('LDAP_PORT') ?? 389;
        $this->configs['LDAP_ADMIN_USER'] = getenv('LDAP_ADMIN_USER') ?? '';
        $this->configs['LDAP_ADMIN_PASSWORD'] = getenv('LDAP_ADMIN_PASSWORD') ?? '';
        $this->configs['LDAP_BASE_DN'] = getenv('LDAP_BASE_DN') ?? '';
        
        $this->configs['APP_ENV'] = getenv('APP_ENV') ?? '';

        $this->configs['GRAPH_TENANT_ID'] = getenv('GRAPH_TENANT_ID') ?? '';
        $this->configs['GRAPH_CLIENT_ID'] = getenv('GRAPH_CLIENT_ID') ?? '';
        $this->configs['GRAPH_CLIENT_SECRET'] = getenv('GRAPH_CLIENT_SECRET') ?? '';
        $this->configs['GRAPH_USER_FROM'] = getenv('GRAPH_USER_FROM') ?? '';


    }

    public function joinPaths()
    {
        $paths = array();
        foreach (func_get_args() as $arg) {
            if($arg != ''){
                $paths[] = $arg;
            }
        }

        $result = preg_replace("#/+#", "/", join("/", $paths));
        return $result;
    }

    public function get($name)
    {
        return $this->configs[$name] ?? null;
    }
}