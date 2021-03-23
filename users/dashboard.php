<html>

   <head>
    <title>FusionCharts XT - Simple Column 2D Chart</title>
    <!--
        Step 2:  Include the `fusioncharts.js` file. This file is needed to render the chart.
         Ensure that the path to this JS file is correct. Otherwise, it may lead to JavaScript errors.
    -->
    <script src="javascript/fusioncharts.js"></script>
   </head>
   <body>

<?php
// This is a simple example on how to draw a chart using FusionCharts and PHP.
// We have included includes/fusioncharts.php, which contains functions
// to help us easily embed the charts.
/* Include the `fusioncharts.php` file that contains functions  to embed the charts. */

  include("fusioncharts.php");
  include('connection.php');

  // Form the SQL query that returns the top 10 most populous countries
  $query = "SELECT Thali, (Previous_Due + yearly_hub + Zabihat - Paid) AS Total_Pending FROM thalilist where Active='1' ORDER BY Total_Pending DESC LIMIT 10";

  // Execute the query, or else return the error message.
  $result = mysqli_query($link,$query);

  // If the query returns a valid response, prepare the JSON string
  if ($result) {
    // The `$arrData` array holds the chart attributes and data
    $arrData = array(
      "chart" => array(
          "caption" => "Top 10 Pending Hub",
          "paletteColors" => "#0075c2",
          "bgColor" => "#ffffff",
          "borderAlpha"=> "20",
          "canvasBorderAlpha"=> "0",
          "usePlotGradientColor"=> "0",
          "plotBorderAlpha"=> "10",
          "showXAxisLine"=> "1",
          "xAxisLineColor" => "#999999",
          "showValues" => "0",
          "divlineColor" => "#999999",
          "divLineIsDashed" => "1",
          "showAlternateHGridColor" => "0"
        )
    );

    $arrData["data"] = array();

    // Push the data into the array
    while($row = mysqli_fetch_array($result)) {
      array_push($arrData["data"], array(
          "label" => $row["Thali"],
          "value" => $row["Total_Pending"]
          )
      );
    }

    /*JSON Encode the data to retrieve the string containing the JSON representation of the data in the array. */

    $jsonEncodedData = json_encode($arrData);

    /*Create an object for the column chart using the FusionCharts PHP class constructor. Syntax for the constructor is ` FusionCharts("type of chart", "unique chart id", width of the chart, height of the chart, "div id to render the chart", "data format", "data source")`. Because we are using JSON data to render the chart, the data format will be `json`. The variable `$jsonEncodeData` holds all the JSON data for the chart, and will be passed as the value for the data source parameter of the constructor.*/

    $columnChart = new FusionCharts("column2D", "myFirstChart" , 600, 300, "chart-1", "json", $jsonEncodedData);

    // Render the chart
    $columnChart->render();

    // Close the database connection
    mysqli_close($link);
  }

?>

<div id="chart-1"><!-- Fusion Charts will render here--></div>
   </body>
</html>
