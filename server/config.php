<?php
	#$server = "#######";
    $server = "localhost";
	$database = "tracsis_demo";
	#$dmUsername = "########";
    $dmUsername = "root";
	#$dmPassword = "############";
    $dmPassword = "";

	try {
		$pdo = new PDO('mysql:host='.$server.';dbname='.$database, $dmUsername, $dmPassword);
	} catch (PDOException $e) {
		print "Error!: " . $e->getMessage() . "<br/>";
		die();
	}

	$dmData = 'dm_data';

	function mysql_escape_cheap($str) {
		if (!empty($str) && is_string($str)) {
			return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $str);
		}

		return $str;
	}

	function makeSalt($length = 128) {
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}

    function GET($name=NULL, $value=false, $option="default") {
        $option=false; // Old version depricated part
        $content=(!empty($_GET[$name]) ? trim($_GET[$name]) : (!empty($value) && !is_array($value) ? trim($value) : false));
        if(is_numeric($content))
            return preg_replace("@([^0-9])@Ui", "", $content);
        else if(is_bool($content))
            return ($content?true:false);
        else if(is_float($content))
            return preg_replace("@([^0-9\,\.\+\-])@Ui", "", $content);
        else if(is_string($content))
        {
            if(filter_var ($content, FILTER_VALIDATE_URL))
                return $content;
            else if(filter_var ($content, FILTER_VALIDATE_EMAIL))
                return $content;
            else if(filter_var ($content, FILTER_VALIDATE_IP))
                return $content;
            else if(filter_var ($content, FILTER_VALIDATE_FLOAT))
                return $content;
            else
                return preg_replace("@([^a-zA-Z0-9\+\-\_\*\@\$\!\;\.\?\#\:\=\%\/\ ]+)@Ui", "", $content);
        }
        else false;
    }

    function POST($name=NULL, $value=false, $option="default") {
        $option=false; // Old version depricated part
        $content=(!empty($_POST[$name]) ? trim($_POST[$name]) : (!empty($value) && !is_array($value) ? trim($value) : false));
        if(is_numeric($content))
            return preg_replace("@([^0-9])@Ui", "", $content);
        else if(is_bool($content))
            return ($content?true:false);
        else if(is_float($content))
            return preg_replace("@([^0-9\,\.\+\-])@Ui", "", $content);
        else if(is_string($content))
        {
            if(filter_var ($content, FILTER_VALIDATE_URL))
                return $content;
            else if(filter_var ($content, FILTER_VALIDATE_EMAIL))
                return $content;
            else if(filter_var ($content, FILTER_VALIDATE_IP))
                return $content;
            else if(filter_var ($content, FILTER_VALIDATE_FLOAT))
                return $content;
            else
                return preg_replace("@([^a-zA-Z0-9\+\-\_\*\@\$\!\;\.\?\#\:\=\%\/\ ]+)@Ui", "", $content);
        }
        else false;
    }
?>