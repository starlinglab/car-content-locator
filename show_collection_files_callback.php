<?php
// Database connection
include "global.php";
$conn = getdb();

$cmd = isset($_GET['cmd']) ? $_GET['cmd'] : -1;

$type = "singularity";
if ($cmd == "file_list") {
    $title_id = isset($_GET['collection']) ? $_GET['collection'] : -1;
    $sql = "SELECT * FROM usc_files WHERE title_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $title_id);

    $stmt->execute();
    $result = $stmt->get_result();
    $list = [];
    while ($row = $result->fetch_assoc()) {
        $list[] = $row["relative_path"];
    }

    echo json_encode($list);

    die();

}
//http://10.6.25.47/usc/show_collection_files_callback.php?cmd=file_details&filename=%2Fuscsf%2FHardDrives%2F6.USCSF409868.1023%2FRAW%2FAudio%2FDay01%2FK03.WAV
if ($cmd == "file_details") {

    $filename = isset($_GET['filename']) ? substr($_GET['filename'], 1) : -1;

    //Base file information
    $sql = "SELECT * FROM usc_files WHERE relative_path = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $filename);

    $stmt->execute();
    $result1 = $stmt->get_result();
    $result = $result1->fetch_assoc();


    //Add Signualrity info
    $sql = "SELECT * FROM files WHERE path = ?";
    $stmt = $conn->prepare($sql);
    if ($result["encrypted_key"])
        $new_filename = $filename . '.enc';
    else
        $new_filename = $filename;

    $stmt->bind_param("s", $new_filename);
    $stmt->execute();
    $result2 = $stmt->get_result();

    $res2 = $result2->fetch_assoc(); 

    if ($res2) {


        $res2["cid"] = base32_encode($res2["cid"]);
        $result["singularity"] = $res2;

        $sql = "SELECT DISTINCT car_id as carid, files.id as file_id, cars.piece_cid as cid FROM `file_range_car` inner join file_ranges on file_range_id = file_ranges.id inner join files on file_ranges.file_id = files.id inner join cars on car_id=cars.id where file_id = ?";

        $stmt = $conn->prepare($sql);
        $file_id = $res2["id"];
        $stmt->bind_param("s", $file_id);
        $stmt->execute();
        $result3 = $stmt->get_result();
        $sectors = [];
        while ($row = $result3->fetch_assoc()) {
            
            
            $row["deals"]=[];

            $sql = "SELECT * FROM .`deals` where piece_cid=?";
            $stmt5 = $conn->prepare($sql);

            
            $cid = $row["cid"];
            $stmt5->bind_param("s", $cid) ;
            $stmt5->execute();
            $result5 = $stmt5->get_result();
            while ($row3 = $result5->fetch_assoc()) {
                $row3["piece_cid"]="";
                $row["deals"][]=$row3;
            }
            $row["cid"] = base32_encode($row["cid"]);
            $sectors[] = $row;

            
        }

        $result["singularity"]["sectors"] = $sectors;

    }

}
echo json_encode($result);
$stmt->close();
$conn->close();
die();


?>
