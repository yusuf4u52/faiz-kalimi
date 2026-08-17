<?php

function helper_getTotalPending($user_thali)
{
  require("_credentials.php");
  require("../users/connection.php");
  $query = "SELECT (Previous_Due + yearly_hub + Zabihat - Paid) AS Total_Pending FROM thalilist where Thali='$user_thali'";
  $result = mysqli_query($link,$query);
  if($result)
  {
    $row = mysqli_fetch_array($result);
    return $row['Total_Pending'];
  }
  echo "no result";
  return -1;
}

function helper_getFirstNameWithSuffix($name)
{
  $firstNameWithSuffix = $name;
  $names = preg_split("/\s+/", $name);
  if(sizeof($names)>=2)
  {
    $firstNameWithSuffix = $names[0]." ".$names[1];
  }
  return $firstNameWithSuffix;
}

?>