<?php
class LoginLogAnalyzer
{
    const numOfItems = 30;

    public $columns = array(
        'plain' => array(
            'country_name',
            'activity_agent',
        ),
        'serialized' => array(
            'user_login',
            'user_password',
        ),
    );

	public function __construct()
	{
        $this->root = dirname(__FILE__);
		require $this->root . '/config.php';

		$this->dbConfig = $config['db'];
		$this->db = $this->createDbHandler();

        $data = $this->analyze();
        $this->show($data);
	}

	public function analyze()
	{
        $log = $this->prepareLog();
		$sql = $this->getSqlForLoginLog();
		$sth = $this->db->query($sql);
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $error = unserialize($row['activity_errors']);
            foreach ($this->columns['serialized'] as $column) {
                if (isset($error[$column])) {
                    if (!isset($log[$column][$error[$column]])) {
                        $log[$column][$error[$column]] = 0;
                    }
                    $log[$column][$error[$column]]++;
                }
            }
            foreach ($this->columns['plain'] as $column) {
                if (isset($row[$column])) {
                    if (!isset($log[$column][$row[$column]])) {
                        $log[$column][$row[$column]] = 0;
                    }
                    $log[$column][$row[$column]]++;
                }
            }
		}
        $log = $this->sortLog($log);

        $sql = "
            SELECT
                count(*) as `records`,
                count(DISTINCT activity_IP) as `ips`
            FROM wp_user_login_log
        ";
		$sth = $this->db->query($sql);
        $count = $sth->fetch(PDO::FETCH_ASSOC);

        $data = array(
            'log' => $log,
            'count' => $count,
        );
        return $data;
	}

    public function createDbHandler() {
		$dsn = $this->getDsn();
		$options = array(
            //reserved for PHP < 5.3.6
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
		);
		$dbh = new PDO($dsn, $this->dbConfig['user'], $this->dbConfig['pass'], $options);
        return $dbh;
    }

    public function getDsn() {
		$dsn = 'mysql:host=' . $this->dbConfig['host'] . ';dbname=' . $this->dbConfig['dbname'] . ';charset=utf8';
        return $dsn;
    }

    public function getSqlForLoginLog() {
		$sql = "SELECT * FROM {$this->dbConfig['table']} WHERE activity_errors IS NOT NULL";
        return $sql;
    }

    public function prepareLog() {
        $log = array();
        foreach ($this->columns as $columnTypes) {
            foreach ($columnTypes as $column) {
                $log[$column] = array();
            }
        }
        return $log;
    }

    public function addLogLines($log, $row) {
        $error = unserialize($row['activity_errors']);
        foreach ($this->columns['serialized'] as $column) {
            if (isset($error[$column])) {
                if (!isset($log[$column][$error[$column]])) {
                    $log[$column][$error[$column]] = 0;
                }
                $log[$column][$error[$column]]++;
            }
        }
        foreach ($this->columns['plain'] as $column) {
            if (isset($row[$column])) {
                if (!isset($log[$column][$row[$column]])) {
                    $log[$column][$row[$column]] = 0;
                }
                $log[$column][$row[$column]]++;
            }
        }
        return $log;
    }

    public function sortLog($log) {
        foreach ($log as $column => $data) {
            asort($log[$column], SORT_NUMERIC);
            $log[$column] = array_reverse($log[$column], true);
            $log[$column] = array_slice($log[$column], 0, self::numOfItems, true);
        }
        return $log;
    }

    public function show($data) {
        require_once $this->root . '/vendor/autoload.php';
        $loader = new Twig_Loader_Filesystem($this->root . '/templates');
        $twig = new Twig_Environment($loader, array(
            'cache' => $this->root . '/compilation_cache',
        ));

        echo $twig->render('index.html', array('data' => $data));
    }
	
}
new LoginLogAnalyzer();

