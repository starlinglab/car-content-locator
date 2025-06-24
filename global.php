<?php
include("_config.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

function getdb()
{
    global $sql_username,$sql_password,$sql_servername,$sql_dbname;
    
    $conn = new mysqli($sql_servername, $sql_username, $sql_password, $sql_dbname);
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    return $conn;
}


function base32_encode($data) {
    $alphabet = 'abcdefghijklmnopqrstuvwxyz234567';
//    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';  // Base32 Alphabet
    $binary = '';
    $encoded = '';
    
    // Convert the input data to binary
    foreach (@str_split($data) as $char) {
        $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }

    // Pad binary data if necessary to be a multiple of 5 bits
    $binary = str_pad($binary, ceil(strlen($binary) / 5) * 5, '0', STR_PAD_RIGHT);

    // Encode the binary data using the Base32 alphabet
    for ($i = 0; $i < strlen($binary); $i += 5) {
        $chunk = substr($binary, $i, 5);
        $index = bindec($chunk);  // Convert the 5-bit binary chunk to a decimal number
        $encoded .= $alphabet[$index];  // Map the number to the Base32 alphabet
    }

    // Add padding if necessary
    $padding = strlen($encoded) % 8;
    if ($padding > 0) {
        $encoded .= str_repeat('=', 8 - $padding);
    }
    $encoded = str_replace("=", "", "b" . $encoded );
    return $encoded;
}

const FILECOIN_GENESIS_UNIX_EPOCH = 1598306400;

function epocToUnix ($filEpoch) {
  return ($filEpoch * 30) + FILECOIN_GENESIS_UNIX_EPOCH;
}

function getTime($timestamp) {
    //print($timestamp);
    if ($timestamp=="300001598306370") { return ""; }
    if ($timestamp=="1598306400") { return ""; }
    //1598306400
    //print("  ");
    $date = new DateTime();
    $timestampDate = (new DateTime())->setTimestamp($timestamp);
    $diff = $date->diff($timestampDate);
    if ($diff->invert) {
        return $diff->format('%a days ago');
    } else {
        return $diff->format('in %a days');
    }

}

function humanFileSize($bytes, $decimals = 2) {
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    if ($bytes == 0) return '0 B';
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $sizes[$factor];
}