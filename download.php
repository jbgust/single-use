<?php
/**
 *	This file does the actual downloading
 *	It will take in a query string and return either the file, 
 *	or failure
 *
 *	Expects: download.php?key1234567890
 */
 
	include("variables.php");
	
	// The input string
	$key = trim($_GET['key']);
	$i = trim($_GET['i']);

	// create file to store first download timestamp
    $datesFilename = 'keys/dates';
    if(!file_exists($datesFilename)){
        $fileDate = fopen($datesFilename,'a');
        fclose($file);
    }

	/*
	 *	Retrive the keys
	 */
	$keys = file('keys/keys');
	$dates = file($datesFilename);

	$match = false;
	
	/*
	 *	Loop through the keys to find a match
	 *	When the match is found, remove it
	 */
	foreach($keys as &$one) {
        $currentKey = rtrim($one);
        if($currentKey ==$key) {
			$match = true;

			//Check time expiration for download
			$keyHasTimestamp = false;
            foreach($dates as &$date) {
                $dateByKeyLine = explode("|", $date);

                if($dateByKeyLine[0]==$key){
                    $keyHasTimestamp=true;
                    if(intval(rtrim($dateByKeyLine[1]))+EXPIRATION_LINK_SECONDS < time()) {
                        $match = false;
                        //if time expires remove the key and the timestamp
                        $one = '';
                        $date = '';
                    }
                }
            }

            //first time download, we store the timestamp of the first download
            if(!$keyHasTimestamp){
                array_push($dates, $key."|".time()."\n");
            }

		} else {
            //clean old download that expires
            foreach($dates as &$date) {
                $dateByKeyLine = explode("|", $date);
                if($dateByKeyLine[0]==$currentKey && intval(rtrim($dateByKeyLine[1]))+EXPIRATION_LINK_SECONDS < time()) {
                    $one = '';
                    $date = '';
                }
            }
        }
	}
	
	/*
	 *	Puts the remaining keys back into the file
	 */
	file_put_contents('keys/keys',$keys);
    file_put_contents($datesFilename,$dates);
	
	/*
	 * If we found a match
	 */
	if($match !== false) {
		
		/*
		 *	Forces the browser to download a new file
		 */
		$contenttype = $PROTECTED_DOWNLOADS[$i]['content_type'];
		$filename = $PROTECTED_DOWNLOADS[$i]['suggested_name'];
		$file = $PROTECTED_DOWNLOADS[$i]['protected_path'];
		$remote_file = $PROTECTED_DOWNLOADS[$i]['remote_path'];

		set_time_limit(0);

		// If a remote file is set
		if($remote_file) {

			$file=fopen($remote_file,'r');
			header("Content-Type:text/plain");
			header("Content-Disposition: attachment; filename=\"{$filename}\"");
			fpassthru($file);

		// This is a local file
		} else {
		
			header("Content-Description: File Transfer");
			header("Content-type: {$contenttype}");
			header("Content-Disposition: attachment; filename=\"{$filename}\"");
			header("Content-Length: " . filesize($file));
			header('Pragma: public');
			header("Expires: 0");
			readfile($file);

		}
		
		// Exit
		exit;
	
	} else {
	
	/*
	 * 	We did NOT find a match
	 *	OR the link expired
	 *	OR the file has been downloaded already
	 */

?>

<html>
	<head>
		<title>Download expired</title>
	</head>
	<body>
		<h1>Download expired</h1>
	</body>
</html>

<?php
	} // end matching
?>