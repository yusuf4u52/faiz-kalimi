<?php

// Method: POST, PUT, GET etc
// Data: array("param" => "value") ==> index.php?param=value
function CallAPI($itsid,$header,$its_url)
{	
	$opts = array(
	  'http'=>array(
	    'method'=>"GET",
	    'header'=>$header
	  )
	);
	$context = stream_context_create($opts);
	// Open the file using the HTTP headers set above
	$result = file_get_contents($its_url.$itsid, false, $context);
	return json_decode($result,true);
}

function CallAPIForAll()
{
	include('../users/connection.php');
	require '../sms/_credentials.php';
	$query="SELECT ITS_No FROM thalilist where NAME='' or NAME='0'";
	$result = mysqli_query($link,$query);
	if (mysqli_num_rows($result) > 0) {
        // output data of each row
	    while($row = mysqli_fetch_assoc($result)) {
	        $its = $row['ITS_No'];
	        if (!empty($its)) {
	            $datafromits = CallAPI($its,$header,$its_url);
	        }
	        if(!empty($datafromits)) {
		        $datafromits_name = $datafromits['name'];
			    $datafromits_jamaat = $datafromits['jamaat'];
				$update="update thalilist set NAME='".$datafromits_name."' AND WATAN='".$datafromits_jamaat."' where ITS_No='".$its."' ";
				mysqli_query($link,$update);
			}

	    }
	} else {
	    echo "0 results";
	}
	mysqli_close($link);
}
?>