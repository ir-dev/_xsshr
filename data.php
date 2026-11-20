<?php 

// Secrets are not in source control. Each value comes from an environment variable or, failing
// that, from .env_secret next to this file (gitignored; template: .env_secret.example).
function xs_secret($name){
	static $vals = null;
	$env = getenv($name);
	if ($env !== false && $env !== '') return $env;
	if ($vals === null) {
		$vals = array();
		$path = __DIR__ . '/.env_secret';
		if (is_readable($path)) {
			foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
				$line = trim($line);
				if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
				list($k, $v) = explode('=', $line, 2);
				$vals[trim($k)] = trim($v);
			}
		}
	}
	return isset($vals[$name]) ? $vals[$name] : '';
}

define('HOST', xs_secret('XSFOOD_DB_HOST'));
define('USER', xs_secret('XSFOOD_DB_USER'));
define('PASS', xs_secret('XSFOOD_DB_PASS'));
define('DB', xs_secret('XSFOOD_DB_NAME'));
define('MSG91_AUTHKEY', xs_secret('XSFOOD_MSG91_AUTHKEY'));
header("access-control-allow-origin: *");
$response = array();  
if(strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') == 0){
    $content = trim(file_get_contents("php://input"));
    $decoded = json_decode($content, true);
    $response["status"] = "success";
    $type = $decoded['type'];
    if (strcasecmp($type, "fill") == 0){
        $response = insertFoodFill($decoded, $response);   
    }
    else {
        $response = insertFoodFind($decoded, $response);   
    }
}
else {
    //$response['error']=true;
    //$response['method']=isset($_POST['name']);
    //$response['message']='Please choose a file';   
    $type = $_GET['type'];
    if (strcasecmp($type, "fill") == 0){
        $response = getFoodFill($response);
    }
    else if (strcasecmp($type, "find") == 0) {
        $response = getFoodFind($response);
    }
	else if (strcasecmp($type, "sendsms") == 0) {
        $response = sendSmsToNum($response);
    }
    //echo json_encode($response);
}
echo json_encode($response, JSON_NUMERIC_CHECK);

function getFoodFill($response){
    $con = mysqli_connect(HOST,USER,PASS,DB) or die('Unable to Connect...');
    //$sql = "select * xsFoofFill where availabletill >=".date().";";
	$lat = $_GET['lat'];
	$lng = $_GET['lng'];
    $sql = "select *, Distance(Lat, Lng,".$lat.",".$lng.",null) as Dist from xsFoofFill;";
    $result = array();
    $fetch = mysqli_query($con, $sql);
    //$result = mysqli_fetch_array(mysqli_query($con,$sql));
    while($row = mysqli_fetch_assoc($fetch))
    {
        $rarr = array();
        $rarr['Id'] = $row['Id'];
        $rarr['MobileNo'] = $row['MobileNo'];
        $rarr['FoodInfo'] = $row['FoodInfo'];
        $rarr['Address'] = $row['Address'];
        $rarr['pos'] = array();
        $rarr['pos']['lat'] = $row['Lat'];
        $rarr['pos']['lng'] = $row['Lng'];
        $rarr['FoodType'] = $row['FoodType'];
        $rarr['FoodStyle'] = $row['FoodStyle'];
        $rarr['AvailableTill'] = $row['AvailableTill'];
        $rarr['DeviceId'] = $row['DeviceId'];
        $rarr['DeviceType'] = $row['DeviceType'];
		$rarr['Dist'] = $row['Dist'] ?? 0;
        array_push($result, $rarr);
    }
    $response['message'] = "Get records successfully";
    $response['action'] = "GetFill";
    $response['data'] = $result;
    mysqli_close($con);
    return $response;
}

function getFoodFind($response){
    $con = mysqli_connect(HOST,USER,PASS,DB) or die('Unable to Connect...');
    //$sql = "select * xsFoodFind where ExpectedTill >=".date().";";
	$lat = $_GET['lat'];
	$lng = $_GET['lng'];
    $sql = "select *, Distance(Lat, Lng,".$lat.",".$lng.",null) as Dist from xsFoodFind;";
    $result = array();
    $fetch = mysqli_query($con, $sql);
    //$result = mysqli_fetch_array(mysqli_query($con,$sql));
    while($row = mysqli_fetch_assoc($fetch))
    {
        $rarr = array();
        $rarr['Id'] = $row['Id'];
        $rarr['MobileNo'] = $row['MobileNo'];
        $rarr['FoodInfo'] = $row['FoodInfo'];
        $rarr['Address'] = $row['Address'];
        $rarr['pos'] = array();
        $rarr['pos']['lat'] = $row['Lat'];
        $rarr['pos']['lng'] = $row['Lng'];
        $rarr['FoodType'] = $row['FoodType'];
        $rarr['FoodStyle'] = $row['FoodStyle'];
        $rarr['ExpectedTill'] = $row['ExpectedTill'];
        $rarr['DeviceId'] = $row['DeviceId'];
        $rarr['DeviceType'] = $row['DeviceType'];
		$rarr['Dist'] = $row['Dist'] ?? 0;
		if ($rarr['Dist'] < 51 ) {		
		}
        array_push($result, $rarr);
    }
    $response['message'] = "Get find records successfully";
    $response['action'] = "GetFind";
    $response['data'] = $result;
    mysqli_close($con);
    return $response;
}

function insertFoodFill($fdata, $response){
    $con = mysqli_connect(HOST,USER,PASS,DB) or die('Unable to Connect...');
    $availdate = date('Y-m-d H:i:s', strtotime($fdata['availabletill']));
    $sql = "INSERT INTO xsFoofFill (MobileNo, FoodInfo, Address, Lat, Lng, FoodType, FoodStyle, availabletill, DeviceId, DeviceType) VALUES('".$fdata['telno']."','". $fdata['food'] ."','".$fdata['addr']."',".$fdata['pos']['lat']."," .$fdata['pos']['lng'].",'".$fdata['foodtype']."','".$fdata['foodstyle']."','".$availdate."','".$fdata['deviceid']."','".$fdata['devicetype']."');";
    //$sql = "select * from XsFoofFill";
    if (mysqli_query($con, $sql)) {
        $response['message'] = "New record created successfully";
    } else {
        //echo "Error: " . $sql . "<br>" . mysqli_error($con);
        $response["status"] = "error";
        $response['message'] = mysqli_error($con);
    }
    $response['insertedid'] = mysqli_insert_id($con);
    mysqli_close($con);
	findFoodInRange($fdata['pos']['lat'], $fdata['pos']['lng'], $fdata['telno']);
	$response['Dist'] = distancebtw($fdata['pos']['lat'], $fdata['pos']['lng'],$fdata['mypos']['lat'], $fdata['mypos']['lng'], 'K');
    return $response;
}

function insertFoodFind($fdata, $response){
    $con = mysqli_connect(HOST,USER,PASS,DB) or die('Unable to Connect...');
    $availdate = date('Y-m-d H:i:s', strtotime($fdata['availabletill']));
    $sql = "INSERT INTO xsFoodFind (MobileNo, FoodInfo, Address, Lat, Lng, FoodType, FoodStyle, ExpectedTill, DeviceId, DeviceType) VALUES('".$fdata['telno']."','". $fdata['food'] ."','".$fdata['addr']."',".$fdata['pos']['lat']."," .$fdata['pos']['lng'].",'".$fdata['foodtype']."','".$fdata['foodstyle']."','".$availdate."','".$fdata['deviceid']."','".$fdata['devicetype']."');";
    //$sql = "select * from XsFoofFill";
    if (mysqli_query($con, $sql)) {
        $response['message'] = "New Find  record created successfully";
    } else {
        //echo "Error: " . $sql . "<br>" . mysqli_error($con);
        $response["status"] = "error";
        $response['message'] = mysqli_error($con);
    }
    $response['insertedid'] = mysqli_insert_id($con);
	$response['Dist'] = distancebtw($fdata['pos']['lat'], $fdata['pos']['lng'],$fdata['mypos']['lat'], $fdata['mypos']['lng'], 'K');
    mysqli_close($con);
    return $response;
}

function findFoodInRange($lat, $lng, $srctel){
	$con = mysqli_connect(HOST,USER,PASS,DB) or die('Unable to Connect...');
	$sql = "select *, Distance(Lat, Lng,".$lat.",".$lng.",null) as Dist from xsFoodFind where Distance(Lat, Lng,".$lat.",".$lng.",null) < 50;";
    $result = array();
    $fetch = mysqli_query($con, $sql);
    while($row = mysqli_fetch_assoc($fetch))
    {
		$msg = "New food available around ".$row['Dist']. " Kms from your location, Please contact - ".$row['MobileNo']."(".$srctel."). More details - https://www.xsfood.org";
		sendSms($row['MobileNo'], $msg);
    }
    mysqli_close($con);
}

function distancebtw($lat1, $lon1, $lat2, $lon2, $unit) {

	$theta = $lon1 - $lon2;
	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	$dist = acos($dist);
	$dist = rad2deg($dist);
	$miles = $dist * 60 * 1.1515;
	$unit = strtoupper($unit);

	if ($unit == "K") {
		return ($miles * 1.609344);
	} else if ($unit == "N") {
		return ($miles * 0.8684);
    } else {
        return $miles;
    }
}

function sendSms($mob, $msg){
	$curl = curl_init();
	$msg = $msg ?? "New food enterd by an user, https://www.xsfood.org";
	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://api.msg91.com/api/sendhttp.php?sender=XSFOOD&route=4&mobiles=".$mob."&authkey=".urlencode(MSG91_AUTHKEY)."&country=0&message=".$msg,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_SSL_VERIFYHOST => 0,
	  CURLOPT_SSL_VERIFYPEER => 0,
	));

	$smsresp = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);
}

function sendSmsToNum(){
	$nums = $_GET['mobs'];
	$msg = $_GET['msg'];
	$msg = $msg ?? "New food enterd by an user, https://www.xsfood.org";
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://api.msg91.com/api/sendhttp.php?sender=XSFOOD&route=4&mobiles=".$nums."&authkey=".urlencode(MSG91_AUTHKEY)."&country=0&message=".$msg,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_SSL_VERIFYHOST => 0,
	  CURLOPT_SSL_VERIFYPEER => 0,
	));

	$response["msg"] = curl_exec($curl);
	$response["err"] = curl_error($curl);

	curl_close($curl);
	return $response;
}

 ?>