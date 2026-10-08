<?php
function tglIndo($tanggal)
{
    $bulan = [
        1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];
    $hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    
    $ts = strtotime($tanggal);
    $h = $hari[date("w", $ts)];
    $d = date("j", $ts);
    $bln = $bulan[date("n", $ts)];
    $y = date("Y", $ts);
    $jam = date("H:i", $ts);
    
    return "$h, $d $bln $y $jam";
}

function tglIndoShort($tanggal)
{
    $bulan = [
        1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];
    $ts = strtotime($tanggal);
    $d = date("j", $ts);
    $bln = $bulan[date("n", $ts)];
    $y = date("Y", $ts);
    
    return "$d $bln $y";
}

function distance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}
?>
