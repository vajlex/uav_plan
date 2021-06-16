<?php  
//  code by Lex Berman (2017-06) http://dbr.nu/bio/ 
$first = $_POST[First];
$last = $_POST[Last];
$date = $_POST[Date];
$geo = $_POST[Geojson];

// code for validation of date, must be at least 7 days in the future
$today = date("Ymd");
$ckdate = DateTime::createFromFormat('!m/d/Y', $date);
$numdate = $ckdate->format('Ymd');

//  random hash generator  (for SUBMISSION ID)
//  from https://gist.github.com/zyphlar/7217f566fc83a9633959 
function getRandomBytes($nbBytes = 32)
{
    $bytes = openssl_random_pseudo_bytes($nbBytes, $strong);
    if (false !== $bytes && true === $strong) {
        return $bytes;
    }
    else {
        throw new \Exception("Unable to generate secure token from OpenSSL.");
    }
}
function generatePassword($length){
    return substr(preg_replace("/[^a-zA-Z0-9]/", "", base64_encode(getRandomBytes($length+1))),0,$length);
}
$SUB_ID = generatePassword(6);

// code for stripping coordinates from geojson 
$geo_array=json_decode($geo, true);
// $coords = $geo_array->type->features->geometry->coordinates[0];
// $test = $geo_array->features->type;
?>
<!DOCTYPE html>
    <!-- UAV Drone Form Demo (2017) by Lex Berman  https://goo.gl/Vyp1Ps -->
    <!-- catching input from from -->

<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>UAV Form Demo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
 
    <!-- <link rel="stylesheet" href="theme/custom.css"> -->   
    <!--formden.js communicates with FormDen server to validate fields and submit via AJAX -->
    <script type="text/javascript" src="https://formden.com/static/cdn/formden.js"></script>
    <!-- Special version of Bootstrap that is isolated to content wrapped in .bootstrap-iso -->
    <link rel="stylesheet" href="https://formden.com/static/cdn/bootstrap-iso.css" />
    <!--Font Awesome (added because you use icons in your prepend/append)-->
    <link rel="stylesheet" href="https://formden.com/static/cdn/font-awesome/4.4.0/css/font-awesome.min.css" />

    <link rel="stylesheet" href="assets/leaflet.css" />
    <script src="assets/leaflet.js"></script>
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="bootstrap/html5shiv.js"></script>
      <script src="bootstrap/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <div class="navbar navbar-default navbar-fixed-top">
      <div class="container">

        <div class="navbar-header">
          <a href="index.html" class="navbar-brand">UAV Request</a>
          <button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-main">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
        </div>

        <div class="navbar-collapse collapse" id="navbar-main">
          <ul class="nav navbar-nav navbar-right">
            <li><a href="https://github.com/vajlex/uav_plan" target="_blank">code</a></li>
          </ul>
        </div>

      </div>
    </div>

<?php

if (($numdate) > ($today+6))
{

  //  SECTION FOR SUCCESSFUL DATE
    echo "
    <div class=\"container\">
      <div class=\"page-header\" id=\"banner\">
        <div class=\"row\">
          <div class=\"col-lg-6\">
            <h3>Your Flight Plan has been Submitted</h3>
            <p class=\"lead\">Thanks!</p>
            <p>
    ";

                echo $first . " " . $last;
                echo "<p>We have received your request to fly on " . $date;
                echo "<p>at these coordinates: ";

                echo"<hr><div class=\"webmap_area\">
                       <div id=\"map\" style=\"width: 600px; height: 260px\">
                       </div>
                     </div><!-- end \"webmap_area\" -->
                ";

//  decode json into php array
//  code thanks to Chris Hacia  https://goo.gl/AM6ZYq
$polygon = json_decode($geo);

// generate a WKT string by looping through the coordinates in the geojson
  if ($geo != '') { 
     $wkt .= "((";
     $set = 1;
     foreach($polygon->features[0]->geometry->coordinates[0] as $coordinates)
     {
       $set; $set++;
       $wkt .= $coordinates[0] . ' ' . $coordinates[1] . ', ';
     }
     $wkt .= "))";

//  echo "\r\n";


    //  create a bbox from first point
    $firstx = $polygon->features[0]->geometry->coordinates[0][0][0];
    $firsty = $polygon->features[0]->geometry->coordinates[0][0][1];
    $max_x = 0;
    $max_y = 0;
    $min_x = 180;
    $min_y = 180;
      $x = $firstx;
       $max_x = ($x + 0.004);
      $y = $firsty;
       $max_y = ($y + 0.004);
      $x2 = $firstx;
       $min_x = ($x2 - 0.004);
      $y2 = $firsty;
       $min_y = ($y2 - 0.004);


// now toss the whole Geojson on a leaflet map
    echo "<script>";
// set up the input var for the leaflet map from the raw geojson
    echo "var polygon = " . $geo . ";";

    echo "var southWest = L.latLng(" . $min_y . ", " . $min_x . "),
    northEast = L.latLng(" . $max_y . ", " .  $max_x . "),
    bounds = L.latLngBounds(southWest, northEast);";

    echo "var map = L.map('map').fitBounds(bounds);";
    echo "

    L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: 'basemap OSM, code Lex Berman',
      maxZoom: 18
      }).addTo(map);

    ";

 //   crunch the geojson object onto the map
 echo "var myLayer = L.geoJson(undefined);
 myLayer.addData(polygon);
 myLayer.addTo(map);
";

    echo "</script>";


  } else {echo "error capturing coordinates";}



  echo "<hr>";

  echo "
  <p>You will be contacted by email with a decision from the UAV Drone Committee.
  <p>If you wish to contact us please use the Submission number: 

  ";
  
  echo ' ' . $SUB_ID;
               
echo "
          </div>
        </div>
     </div>

     </div>   <!-- end container -->

  </body>
</html>

";

} else {
    //  SECTON FOR DATE ERROR
    echo "
    
    <div class=\"container\">

      <div class=\"page-header\" id=\"banner\">
        <div class=\"row\">
          <div class=\"col-lg-6\">
            <h3>Your Flight Plan could not be Submitted</h3>
            <p class=\"lead\">Flight Date Error</p>
            <p>

    ";
                echo  "Hi, " . $first . " " . $last;
                echo "<p>We have received your request to fly on $date";

                echo '<hr>but this date: ' . $date . ' is not valid.<p><p> Please use the BACK button on your browser and select a date at least 7 days from today, then re-submit';
echo "
          </div>
        </div>
     </div>

     </div>   <!-- end container -->

  </body>
</html>

";

}

// sample for adding the stuff to psql

// echo $first;
// echo "<hr>";
// echo $geo;
// $db = pg_connect("host=localhost port=5432 dbname=postgres user=postgres password=myadmin123");  
/* $query = "INSERT INTO book VALUES ('$_POST[bookid]','$_POST[book_name]',  
'$_POST[price]','$_POST[dop]')";  
$result = pg_query($query);   

// connect
  $conn_string = '';
    $conn_string .= ' host=localhost';
    $conn_string .= ' port=5432';
    $conn_string .= ' dbname=' . $psqdb;
    $conn_string .= ' user=' . $psquser;
    $conn_string .= ' password=' . $psqpwd;

$dbconn = @pg_connect($conn_string);
  $stat = pg_connection_status($dbconn);
  if ($stat === PGSQL_CONNECTION_OK) {
      echo 'OK  ';
  } else {
      echo '<p>Connection status bad <p>';
  }

//insert session info
$resultA = pg_query($dbconn, "INSERT INTO session(sid,zip) VALUES('".$sess."', '".$zipc."');");

*/


// pg_close($dbconn);

?>

