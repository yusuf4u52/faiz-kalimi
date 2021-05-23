<?php
include('connection.php');

$query = "SELECT distinct(niyazdate) FROM thalilist where niyazdate is not null";
$values = mysqli_fetch_all(mysqli_query($link, $query));
$a = array();
if(!empty($values)) {
    $a["reserved"] = array_merge(...$values);
} else {
    $a["reserved"] = array();
}
$a["nonthali"] = array(
        // moharram
        "2021-08-09",
        "2021-08-10",
        "2021-08-11",
        "2021-08-12",
        "2021-08-13",
        "2021-08-14",
        "2021-08-15",
        "2021-08-16",
        "2021-08-17",
        "2021-08-18",
        // shaban utlat
        "2022-03-04",
        "2022-03-05",
        "2022-03-06",
        "2022-03-07",
        "2022-03-08",
        "2022-03-09",
        "2022-03-10",
        "2022-03-11",
        "2022-03-12",
        "2022-03-13",
        "2022-03-14",
        "2022-03-15",
        "2022-03-16",
        "2022-03-17",
        "2022-03-18"
);
echo json_encode($a);
